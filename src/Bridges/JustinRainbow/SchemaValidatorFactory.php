<?php declare(strict_types = 1);

namespace Mangoweb\JsonSchemaExt\Bridges\JustinRainbow;

use JsonSchema;
use Mangoweb\JsonSchemaExt\SchemaLoader;


class SchemaValidatorFactory
{
	/** @var SchemaLoader */
	private $loader;


	public function __construct(SchemaLoader $loader)
	{
		$this->loader = $loader;
	}


	public function create(): SchemaValidator
	{
		$retriever = new UriRetriever($this->loader);
		$factory = new JsonSchema\Constraints\Factory(NULL, $retriever);

		return new SchemaValidator($factory);
	}
}
