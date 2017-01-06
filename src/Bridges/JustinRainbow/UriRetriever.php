<?php declare(strict_types = 1);

namespace Mangoweb\JsonSchemaExt\Bridges\JustinRainbow;

use JsonSchema;
use Mangoweb\JsonSchemaExt\FileNotFoundException;
use Mangoweb\JsonSchemaExt\Helpers;
use Mangoweb\JsonSchemaExt\ImplementationException;
use Mangoweb\JsonSchemaExt\SchemaLoader;
use Nette\Utils\Strings;
use stdClass;


class UriRetriever implements JsonSchema\UriRetrieverInterface
{
	/** @var SchemaLoader */
	private $loader;


	/**
	 * @param SchemaLoader $loader
	 */
	public function __construct(SchemaLoader $loader)
	{
		$this->loader = $loader;
	}


	/**
	 * @param  string      $uri
	 * @param  NULL|string $baseUri
	 * @return stdClass
	 * @throws JsonSchema\Exception\ResourceNotFoundException
	 */
	public function retrieve($uri, $baseUri = NULL): stdClass
	{
		$resolvedUri = $this->resolveUri($uri, $baseUri);

		$pointer = new JsonSchema\Entity\JsonPointer($resolvedUri);
		$fetchUri = $pointer->getFilename();
		$propertyPathSegments = $pointer->getPropertyPaths();

		if (!Strings::startsWith($fetchUri, 'file:///')) {
			throw new JsonSchema\Exception\ResourceNotFoundException("Only local URIs are supported, '$fetchUri' given");
		}

		$localSchemaPath = Helpers::uriToPath($fetchUri);

		try {
			return $this->loader->loadSchema($localSchemaPath, $propertyPathSegments);

		} catch (FileNotFoundException $e) {
			throw new JsonSchema\Exception\ResourceNotFoundException("File '$localSchemaPath' not found", 0, $e);
		}
	}


	private function resolveUri(string $uri, ?string $baseUri): string
	{
		try {
			$resolver = new JsonSchema\Uri\UriResolver();
			$resolvedUri = $resolver->resolve($uri, $baseUri);

		} catch (JsonSchema\Exception\UriResolverException $e) {
			throw new ImplementationException('Unable to resolve \'$ref\'. Top schema is probably missing \'id\' property', 0, $e);
		}

		if (!$resolvedUri) {
			throw new ImplementationException('Unable to resolve \'$ref\'. Top schema is probably missing \'id\' property');
		}

		return $resolvedUri;
	}
}
