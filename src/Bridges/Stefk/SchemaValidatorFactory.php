<?php declare(strict_types = 1);

namespace Mangoweb\JsonSchemaExt\Bridges\Stefk;

use Closure;
use JVal;
use Mangoweb\JsonSchemaExt\Helpers;
use Mangoweb\JsonSchemaExt\SchemaLoader;
use Nette\Utils\Strings;


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
		$preFetchHook = Closure::fromCallable([$this, 'preFetchHook']);
		$innerValidator = JVal\Validator::buildDefault($preFetchHook);

		return new SchemaValidator($innerValidator);
	}


	private function preFetchHook(string $uri): string
	{
		if (!Strings::startsWith($uri, 'file:///')) {
			throw new \JVal\Exception\Resolver\UnfetchableUriException([$uri, "Only local URIs are supported", E_ERROR]);
		}

		$rawSchema = $this->loader->loadRawSchema(Helpers::uriToPath($uri));
		return 'data://application/json;base64,' . base64_encode($rawSchema);
	}
}
