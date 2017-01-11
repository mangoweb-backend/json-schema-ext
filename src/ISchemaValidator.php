<?php declare(strict_types = 1);

namespace Mangoweb\JsonSchemaExt;

use stdClass;


interface ISchemaValidator
{
	/** {type: object} should expect instance of stdClass */
	public const MODE_MAP_SUPPORTS_STD_CLASS = 0b0001;

	/** {type: object} should expect array */
	public const MODE_MAP_SUPPORTS_ARRAY = 0b0010;

	/** {type: integer} should match only integers */
	public const MODE_TYPE_CHECK_STRICT = 0b0000;

	/** {type: integer} should match integers and numeric strings */
	public const MODE_TYPE_CHECK_LOOSE = 0b0100;

	/** default mode = respect standard */
	public const MODE_STANDARD = self::MODE_MAP_SUPPORTS_STD_CLASS | self::MODE_TYPE_CHECK_STRICT;


	/**
	 * @param  mixed    $value
	 * @param  stdClass $schema
	 * @param  int      $mode
	 * @return array|SchemaValidationError[]
	 */
	public function validate($value, stdClass $schema, int $mode = self::MODE_STANDARD): array;
}
