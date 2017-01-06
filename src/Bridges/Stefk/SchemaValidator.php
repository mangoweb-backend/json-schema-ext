<?php declare(strict_types = 1);

namespace Mangoweb\JsonSchemaExt\Bridges\Stefk;

use JVal;
use Mangoweb\JsonSchemaExt\ISchemaValidator;
use Mangoweb\JsonSchemaExt\SchemaValidationError;
use stdClass;


class SchemaValidator implements ISchemaValidator
{
	/** @var JVal\Validator */
	private $innerValidator;


	public function __construct(JVal\Validator $innerValidator)
	{
		$this->innerValidator = $innerValidator;
	}


	public function validate($value, stdClass $schema): array
	{
		$errors = $this->innerValidator->validate($value, $schema, $schema->id ?? '');

		return array_map(
			function (array $error): SchemaValidationError {
				return new SchemaValidationError(
					$error['path'] ? explode('/', ltrim($error['path'], '/')) : [],
					$error['message']
				);
			},
			$errors
		);
	}


	public function getInnerValidator(): JVal\Validator
	{
		return $this->innerValidator;
	}
}
