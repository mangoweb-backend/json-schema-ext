<?php declare(strict_types = 1);

namespace Mangoweb\JsonSchemaExt;

use stdClass;
use Throwable;


class InvalidExampleException extends \LogicException
{
	/** @var mixed */
	private $example;

	/** @var stdClass */
	private $schema;

	/** @var array|SchemaValidationError[] */
	private $errors;


	/**
	 * @param mixed                         $example
	 * @param stdClass                      $schema
	 * @param array|SchemaValidationError[] $errors
	 * @param NULL|Throwable                $previous
	 */
	public function __construct($example, stdClass $schema, array $errors, ?Throwable $previous = NULL)
	{
		$fmtErrors = implode("\n  ", $errors);
		$message = "Generated example does NOT validate against the corresponding schema:\n  $fmtErrors";
		parent::__construct($message, 0, $previous);

		$this->example = $example;
		$this->schema = $schema;
		$this->errors = $errors;
	}


	public function getExample()
	{
		return $this->example;
	}


	public function getSchema(): stdClass
	{
		return $this->schema;
	}


	/**
	 * @return array|SchemaValidationError[]
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}
}
