<?php declare(strict_types = 1);

namespace Mangoweb\JsonSchemaExt\Bridges\JustinRainbow;

use JsonSchema;
use Mangoweb\JsonSchemaExt\ISchemaValidator;
use Mangoweb\JsonSchemaExt\SchemaValidationError;
use stdClass;


class SchemaValidator implements ISchemaValidator
{
	/** @var JsonSchema\Validator */
	private $innerValidator;

	/** @var JsonSchema\SchemaStorage */
	private $schemaStorage;


	public function __construct(JsonSchema\Constraints\Factory $factory)
	{
		$this->innerValidator = new JsonSchema\Validator($factory);
		$this->schemaStorage = $factory->getSchemaStorage();
	}


	public function validate($value, stdClass $schema): array
	{
		if (isset($schema->id)) {
			$this->schemaStorage->addSchema($schema->id, $schema);
		}

		$this->innerValidator->reset();
		$this->innerValidator->check($value, $schema);

		return array_map(
			function (array $error): SchemaValidationError {
				return new SchemaValidationError(
					(new JsonSchema\Entity\JsonPointer("#$error[pointer]"))->getPropertyPaths(),
					$error['message']
				);
			},
			$this->innerValidator->getErrors()
		);
	}


	public function getInnerValidator(): JsonSchema\Validator
	{
		return $this->innerValidator;
	}
}
