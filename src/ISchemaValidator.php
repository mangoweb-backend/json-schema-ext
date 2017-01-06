<?php declare(strict_types = 1);

namespace Mangoweb\JsonSchemaExt;

use stdClass;


/**
 * @todo Add support for coercion mode?
 */
interface ISchemaValidator
{
	/**
	 * @param  mixed    $value
	 * @param  stdClass $schema
	 * @return array|SchemaValidationError[]
	 */
	public function validate($value, stdClass $schema): array;
}
