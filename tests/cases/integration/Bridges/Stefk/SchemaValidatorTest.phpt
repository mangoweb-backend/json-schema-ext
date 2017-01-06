<?php declare(strict_types = 1);

namespace MangowebTests\JsonSchemaExt\Bridges\Stefk;

use Mangoweb\JsonSchemaExt\Bridges\Stefk\SchemaValidator;
use Mangoweb\JsonSchemaExt\Bridges\Stefk\SchemaValidatorFactory;
use Mangoweb\JsonSchemaExt\SchemaLoader;
use Mangoweb\JsonSchemaExt\SchemaValidationError;
use Nette\Neon\Neon;
use Nette\Utils\FileSystem;
use stdClass;
use Tester;
use Tester\Assert;
use Tester\TestCase;
use const MangowebTests\JsonSchemaExt\TEMP_DIR;

require __DIR__ . '/../../../../bootstrap.php';


/**
 * @testCase
 */
class SchemaValidatorTest extends TestCase
{
	/** @var string */
	private $tempDir;

	/** @var SchemaLoader */
	private $schemaLoader;


	protected function setUp()
	{
		parent::setUp();
		$this->tempDir = TEMP_DIR;
		$this->schemaLoader = new SchemaLoader($this->tempDir);
	}


	public function testValidateSimpleSchema()
	{
		$validator = $this->createValidator();

		$schemaA = (object) ['type' => 'string'];

		Assert::same([], $validator->validate('abc', $schemaA));
		Assert::equal(
			[new SchemaValidationError([], 'instance must be of type string')],
			$validator->validate(123, $schemaA)
		);

		$schemaB = (object) [
			'type' => 'object',
			'properties' => (object) [
				'a' => (object) ['type' => 'string'],
				'b' => (object) ['type' => 'integer'],
			],
		];

		Assert::same([], $validator->validate(new stdClass, $schemaB));
		Assert::equal(
			[
				new SchemaValidationError(['a'], 'instance must be of type string'),
				new SchemaValidationError(['b'], 'instance must be of type integer')
			],
			$validator->validate(
				(object) [
					'a' => 123,
					'b' => '123'
				], $schemaB
			)
		);
	}


	public function testValidateWithInternalRefs()
	{
		$validator = $this->createValidator();

		$schema = (object) [
			'id' => 'file://foo.txt',
			'definitions' => (object) [
				'defA' => (object) ['type' => 'string'],
			],
			'properties' => (object) [
				'a' => (object) ['$ref' => '#/definitions/defA'],
			],
		];

		Assert::same([], $validator->validate(new stdClass, $schema));
		Assert::equal(
			[new SchemaValidationError(['a'], 'instance must be of type string')],
			$validator->validate((object) ['a' => 123], $schema)
		);
	}


	public function testValidateWithExternalRefs()
	{
		$validator = $this->createValidator();

		$schemaA = (object) [
			'definitions' => (object) [
				'defA' => (object) ['type' => 'string'],
			],
		];

		$schemaB = (object) [
			'properties' => (object) [
				'a' => (object) ['$ref' => 'a.neon#/definitions/defA'],
			],
		];

		$schemaA = $this->persistSchema($schemaA, "{$this->tempDir}/a.neon");
		$schemaB = $this->persistSchema($schemaB, "{$this->tempDir}/b.neon");

		Assert::equal(
			[new SchemaValidationError(['a'], 'instance must be of type string')],
			$validator->validate((object) ['a' => 123], $schemaB)
		);
	}


	private function persistSchema(stdClass $schema, string $path): stdClass
	{
		FileSystem::write($path, Neon::encode($schema, Neon::BLOCK));
		return $this->schemaLoader->loadSchema($path);
	}


	private function createValidator(): SchemaValidator
	{
		$factory = new SchemaValidatorFactory($this->schemaLoader);
		$validator = $factory->create();

		return $validator;
	}
}


(new SchemaValidatorTest)->run();
