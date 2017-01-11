<?php declare(strict_types = 1);

namespace Mangoweb\JsonSchemaExt\Bridges\JustinRainbow;

use JsonSchema;
use Mangoweb\JsonSchemaExt\ISchemaValidator;
use Mangoweb\JsonSchemaExt\SchemaLoader;
use Mangoweb\JsonSchemaExt\SchemaValidationError;
use stdClass;


class SchemaValidator implements ISchemaValidator
{
	/** @var UriRetriever */
	private $uriRetriever;

	/** @var JsonSchema\SchemaStorage */
	private $schemaStorage;

	/** @var array|JsonSchema\Validator[] (mode => JsonSchema\Validator)  */
	private $innerValidators = [];


	public function __construct(SchemaLoader $loader)
	{
		$this->uriRetriever = new UriRetriever($loader);
		$this->schemaStorage = new JsonSchema\SchemaStorage($this->uriRetriever);
	}


	public function validate($value, stdClass $schema, int $mode = self::MODE_STANDARD): array
	{
		if (isset($schema->id)) {
			$this->schemaStorage->addSchema($schema->id, $schema);
		}

		$innerValidator = $this->getInnerValidator($mode);
		$innerValidator->reset();

		if ($mode & self::MODE_TYPE_CHECK_LOOSE) {
			$innerValidator->coerce($value, $schema); // TODO: deep clone?

		} else {
			$innerValidator->check($value, $schema);
		}

		return array_map(
			function (array $error): SchemaValidationError {
				return new SchemaValidationError(
					(new JsonSchema\Entity\JsonPointer("#$error[pointer]"))->getPropertyPaths(),
					$error['message']
				);
			},
			$innerValidator->getErrors()
		);
	}


	protected function getInnerValidator(int $mode): JsonSchema\Validator
	{
		if (!isset($this->innerValidators[$mode])) {
			$checkMode = ($mode & self::MODE_MAP_SUPPORTS_ARRAY)
				? JsonSchema\Constraints\Constraint::CHECK_MODE_TYPE_CAST
				: JsonSchema\Constraints\Constraint::CHECK_MODE_NORMAL;

			$factory = new JsonSchema\Constraints\Factory($this->schemaStorage, $this->uriRetriever, $checkMode);
			$this->innerValidators[$mode] = new JsonSchema\Validator($factory);
		}

		return $this->innerValidators[$mode];
	}
}
