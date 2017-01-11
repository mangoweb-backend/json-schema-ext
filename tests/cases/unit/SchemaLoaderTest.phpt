<?php declare(strict_types = 1);

namespace MangowebTests\JsonSchemaExt;

use Mangoweb\JsonSchemaExt\FileNotFoundException;
use Mangoweb\JsonSchemaExt\ImplementationException;
use Mangoweb\JsonSchemaExt\SchemaLoader;
use Mangoweb\JsonSchemaExt\SchemaPreprocessor;
use Nette\Neon\Neon;
use Nette\Utils\FileSystem;
use stdClass;
use Tester\Assert;
use Tester\TestCase;
use function Eloquent\Phony\mock;

require __DIR__ . '/../../bootstrap.php';


/**
 * @testCase
 */
class SchemaLoaderTest extends TestCase
{
	/** @var string */
	private $tempDir;

	/** @var SchemaLoader */
	private $loader;


	protected function setUp()
	{
		parent::setUp();
		$this->tempDir = TEMP_DIR;

		$preprocessor = mock(SchemaPreprocessor::class)->get();
		$this->loader = new SchemaLoader($this->tempDir, $preprocessor);
	}


	public function testLoadSchemaWithDisabledAutoRefresh()
	{
		$this->loader->setAutoRefresh(FALSE);
		$this->loader->setId(FALSE);
		$schemaPath = "{$this->tempDir}/my-schema.neon";

		$schemaA = $this->createSchemaA();
		$this->writeSchema($schemaPath, $schemaA);
		Assert::equal($schemaA, $this->loader->loadSchema($schemaPath));

		$schemaB = $this->createSchemaB();
		$this->writeSchema($schemaPath, $schemaB);
		Assert::equal($schemaA, $this->loader->loadSchema($schemaPath));
	}


	public function testLoadSchemaWithEnabledAutoRefresh()
	{
		$this->loader->setAutoRefresh(TRUE);
		$this->loader->setId(FALSE);
		$schemaPath = "{$this->tempDir}/my-schema.neon";

		$schemaA = $this->createSchemaA();
		$this->writeSchema($schemaPath, $schemaA);
		Assert::equal($schemaA, $this->loader->loadSchema($schemaPath));

		$schemaB = $this->createSchemaB();
		$this->writeSchema($schemaPath, $schemaB);
		Assert::equal($schemaB, $this->loader->loadSchema($schemaPath));
	}


	public function testLoadSchemaWithPointer()
	{
		$this->loader->setAutoRefresh(FALSE);
		$this->loader->setId(FALSE);
		$schemaPath = "{$this->tempDir}/my-schema.neon";

		$schemaA = $this->createSchemaA();
		$this->writeSchema($schemaPath, $schemaA);
		Assert::equal(
			$schemaA->properties->a,
			$this->loader->loadSchema($schemaPath, ['properties', 'a'])
		);

		$schemaA = $this->createSchemaA();
		$this->writeSchema($schemaPath, $schemaA);
		Assert::equal(
			$schemaA->properties->b,
			$this->loader->loadSchema($schemaPath, ['properties', 'b'])
		);
	}


	public function testLoadSchemaWithPointerToMissingObject()
	{
		$this->loader->setAutoRefresh(FALSE);
		$schemaPath = "{$this->tempDir}/my-schema.neon";

		$this->writeSchema($schemaPath, $this->createSchemaA());
		Assert::null($this->loader->loadSchema($schemaPath, ['missing'], FALSE));

		Assert::exception(
			function () use ($schemaPath) {
				$this->loader->loadSchema($schemaPath, ['missing', 'object']);
			},
			ImplementationException::class,
			'Schema file \'%a%/my-schema.neon\' does not contain key /missing/object'
		);
	}


	public function testLoadMissingSchema()
	{
		Assert::exception(
			function () {
				$schemaPath = "{$this->tempDir}/missing.neon";
				$this->loader->loadSchema($schemaPath);
			},
			FileNotFoundException::class,
			'Unable to load schema \'%a%/missing.neon\''
		);
	}


	public function testLoadInvalidSchema()
	{
		Assert::exception(
			function () {
				$schemaPath = "{$this->tempDir}/invalid.neon";
				FileSystem::write($schemaPath, '{');
				$this->loader->loadSchema($schemaPath);
			},
			ImplementationException::class,
			'Unable to parse schema \'%a%/invalid.neon\''
		);
	}


	public function testLoadRawSchemaWithDisabledAutoRefresh()
	{
		$this->loader->setAutoRefresh(FALSE);
		$this->loader->setId(FALSE);
		$schemaPath = "{$this->tempDir}/my-schema.neon";

		$schemaA = $this->createSchemaA();
		$this->writeSchema($schemaPath, $schemaA);
		Assert::equal(json_encode($schemaA, JSON_PRETTY_PRINT), $this->loader->loadRawSchema($schemaPath));

		$schemaB = $this->createSchemaB();
		$this->writeSchema($schemaPath, $schemaB);
		Assert::equal(json_encode($schemaA, JSON_PRETTY_PRINT), $this->loader->loadRawSchema($schemaPath));
	}


	public function testLoadRawSchemaWithEnabledAutoRefresh()
	{
		$this->loader->setAutoRefresh(TRUE);
		$this->loader->setId(FALSE);
		$schemaPath = "{$this->tempDir}/my-schema.neon";

		$schemaA = $this->createSchemaA();
		$this->writeSchema($schemaPath, $schemaA);
		Assert::equal(json_encode($schemaA, JSON_PRETTY_PRINT), $this->loader->loadRawSchema($schemaPath));

		$schemaB = $this->createSchemaB();
		$this->writeSchema($schemaPath, $schemaB);
		Assert::equal(json_encode($schemaB, JSON_PRETTY_PRINT), $this->loader->loadRawSchema($schemaPath));
	}


	private function createSchemaA(): stdClass
	{
		return (object) [
			'type' => 'object',
			'properties' => (object) [
				'a' => (object) ['type' => 'number'],
				'b' => (object) ['type' => 'array'],
			],
		];
	}


	private function createSchemaB(): stdClass
	{
		return (object) [
			'type' => 'array',
		];
	}


	private function writeSchema($path, stdClass $schema): void
	{
		FileSystem::write($path, Neon::encode($schema, Neon::BLOCK));
	}
}


(new SchemaLoaderTest)->run();
