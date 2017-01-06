<?php declare(strict_types = 1);

namespace Mangoweb\JsonSchemaExt;

use Nette\Utils\Strings;


/**
 * @internal
 */
class Helpers
{
	public static function pathToUri(string $path): string
	{
		return 'file://' . Strings::replace(strtr($path, '\\', '/'), '#^[A-Z]:#i', '/$0');
	}


	public static function uriToPath(string $uri): string
	{
		return Strings::replace(Strings::after($uri, 'file://'), '#^/(?=[A-Z]:)#i', '');
	}
}
