<?php declare(strict_types = 1);

namespace MangowebTests\JsonSchemaExt\Bridges\JustinRainbow;

use Mangoweb\JsonSchemaExt\Bridges\JustinRainbow\SchemaValidator;
use Mangoweb\JsonSchemaExt\Bridges\JustinRainbow\SchemaValidatorFactory;
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
			[new SchemaValidationError([], 'Integer value found, but a string is required')],
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
				new SchemaValidationError(['a'], 'Integer value found, but a string is required'),
				new SchemaValidationError(['b'], 'String value found, but an integer is required')
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
			[new SchemaValidationError(['a'], 'Integer value found, but a string is required')],
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
			[new SchemaValidationError(['a'], 'Integer value found, but a string is required')],
			$validator->validate((object) ['a' => 123], $schemaB)
		);
	}


	public function testValidateWithArraysInsteadOfStdClass()
	{
		$validator = $this->createValidator();

		$schema = (object) [
			'type' => 'object',
		];

		Assert::equal(
			[new SchemaValidationError([], 'Array value found, but an object is required')],
			$validator->validate([], $schema)
		);

		Assert::same(
			[],
			$validator->validate([], $schema, $validator::MODE_MAP_SUPPORTS_ARRAY)
		);
	}


	public function testValidatorCoercionIntOk()
	{
		$validator = $this->createValidator();

		$schema = (object) [
			'type' => 'integer',
		];

		Assert::equal(
			[new SchemaValidationError([], 'String value found, but an integer is required')],
			$validator->validate('123', $schema)
		);

		Assert::same(
			[],
			$validator->validate('123', $schema, $validator::MODE_TYPE_CHECK_LOOSE)
		);
	}


	public function testValidatorCoercionIntFail()
	{
		$validator = $this->createValidator();

		$schema = (object) [
			'type' => 'integer',
		];

		Assert::equal(
			[new SchemaValidationError([], 'String value found, but an integer is required')],
			$validator->validate('123x', $schema)
		);

		Assert::equal(
			[new SchemaValidationError([], 'String value found, but an integer is required')],
			$validator->validate('123x', $schema, $validator::MODE_TYPE_CHECK_LOOSE)
		);
	}


//  waiting on https://github.com/justinrainbow/json-schema/pull/345 to be merged
//	public function testValidatorCoercionBoolOk()
//	{
//		$validator = $this->createValidator();
//
//		$schema = (object) [
//			'type' => 'boolean',
//		];
//
//		Assert::equal(
//			[new SchemaValidationError([], 'String value found, but a boolean is required')],
//			$validator->validate('0', $schema)
//		);
//
//		Assert::same(
//			[],
//			$validator->validate('0', $schema, $validator::MODE_TYPE_CHECK_LOOSE)
//		);
//	}


	private function persistSchema(stdClass $schema, string $path): stdClass
	{
		FileSystem::write($path, Neon::encode($schema, Neon::BLOCK));
		return $this->schemaLoader->loadSchema($path);
	}


	private function createValidator(): SchemaValidator
	{
		$validator = new SchemaValidator($this->schemaLoader);

		return $validator;
	}
}


(new SchemaValidatorTest)->run();
