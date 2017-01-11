<?php declare(strict_types = 1);

namespace Mangoweb\JsonSchemaExt;

use Nette\Neon\Neon;
use Nette\Utils\FileSystem;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use stdClass;


class SchemaLoader
{
	/** @var string */
	private $cacheDir;

	/** @var bool */
	private $autoRefresh = TRUE;

	/** @var bool */
	private $setId = TRUE;

	/** @var NULL|SchemaPreprocessor */
	private $preprocessor;


	public function __construct(string $cacheDir, ?SchemaPreprocessor $preprocessor = NULL)
	{
		$this->cacheDir = $cacheDir;
		$this->preprocessor = $preprocessor;
	}


	public function setAutoRefresh(bool $on = TRUE): self
	{
		$this->autoRefresh = $on;
		return $this;
	}


	public function setId(bool $on = TRUE): self
	{
		$this->setId = $on;
		return $this;
	}


	public function setPreprocessor(?SchemaPreprocessor $preprocessor): self
	{
		$this->preprocessor = $preprocessor;
		return $this;
	}


	public function loadSchema(string $path, array $pointer = [], bool $required = TRUE): ?stdClass
	{
		$cacheKey = md5($path . "\x00" . implode("\x00", $pointer));
		$cachePath = "{$this->cacheDir}/{$cacheKey}.php";

		if (!$this->autoRefresh && ($schema = @include $cachePath) !== FALSE) { // @ file may not exist
			return $schema;
		}

		return $this->threadSafeCreate(
			$cachePath,
			function () use ($path, $pointer, $required) {
				$schema = $this->buildSchema($path, $pointer, $required);
				$schemaPhpExpr = str_replace('stdClass::__set_state', '(object)', var_export($schema, TRUE));
				return "<?php // source: $path\n\nreturn " . $schemaPhpExpr . ";\n";
			},
			function () use ($cachePath) {
				return @include $cachePath;
			}
		);
	}


	public function loadRawSchema(string $path, array $pointer = [], bool $required = TRUE): ?string
	{
		$cacheKey = md5($path . "\x00" . implode("\x00", $pointer));
		$cachePath = "{$this->cacheDir}/{$cacheKey}.json";

		if (!$this->autoRefresh && ($schema = @file_get_contents($cachePath)) !== FALSE) { // @ file may not exist
			return $schema;
		}

		return $this->threadSafeCreate(
			$cachePath,
			function () use ($path, $pointer, $required) {
				return Json::encode($this->buildSchema($path, $pointer, $required), Json::PRETTY);
			},
			function () use ($cachePath) {
				return @file_get_contents($cachePath);
			}
		);
	}


	private function threadSafeCreate(string $cachePath, callable $create, callable $load)
	{
		FileSystem::createDir(dirname($cachePath));

		$handle = fopen("$cachePath.lock", 'c+');
		if (!$handle || !flock($handle, LOCK_EX)) {
			throw new \RuntimeException();
		}

		if (!is_file($cachePath) || $this->autoRefresh) {
			$data = $create();
			if (file_put_contents("$cachePath.tmp", $data) !== strlen($data) || !rename("$cachePath.tmp", $cachePath)) {
				@unlink("$cachePath.tmp");
				throw new \RuntimeException();
			}
		}

		$result = $load();
		if ($result === FALSE) {
			throw new \RuntimeException();
		}

		flock($handle, LOCK_UN);
		return $result;
	}


//	private function isExpired(string $sourcePath, string $cachePath): bool
//	{
//		if ($this->autoRebuild) {
//			$lastModifiedTime = filemtime($sourcePath);
//			if ($lastModifiedTime > time()) {
//				touch($sourcePath);
//				$lastModifiedTime = filemtime($sourcePath);
//				assert($lastModifiedTime <= time(), "Last modification time of '$sourcePath' must not be in the future");
//			}
//
//			return $lastModifiedTime > filemtime($cachePath);
//		}
//
//		return FALSE;
//	}


	private function buildSchema(string $path, array $pointer = [], bool $required = TRUE): ?stdClass
	{
		$schema = $this->loadNeon($path);

		foreach ($pointer as $segment) {
			if (!isset($schema->$segment)) {
				if ($required) {
					$key = '/' . implode('/', $pointer);
					throw new ImplementationException("Schema file '{$path}' does not contain key {$key}");

				} else {
					return NULL;
				}
			}

			$schema = $schema->$segment;
		}

		if ($this->setId) {
			$schema->id = Helpers::pathToUri($path);
		}

		if ($this->preprocessor !== NULL) {
			$this->preprocessor->applyRecursive($schema);
		}

		return $schema;
	}


	private function loadNeon(string $path): stdClass
	{
		if (!Strings::match($path, '#^([a-z]:)?[/\\\\]#i')) {
			throw new ImplementationException('Only local absolute paths are supported');
		}

		try {
			$rawContent = FileSystem::read($path);
			$decodedNeon = Neon::decode($rawContent);
			$decodedJson = Json::decode(Json::encode($decodedNeon)); // converts arrays to stdClass instances
			return $decodedJson;

		} catch (\Nette\IOException $e) {
			throw new FileNotFoundException($path, "Unable to load schema '$path'", $e);

		} catch (\Nette\Neon\Exception $e) {
			throw new ImplementationException("Unable to parse schema '$path'", 0, $e);
		}
	}
}
