<?php declare(strict_types = 1);

namespace MangowebTests\JsonSchemaExt;

use Mangoweb\JsonSchemaExt\Helpers;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';


/**
 * @testCase
 */
class HelpersTest extends TestCase
{
	/**
	 * @dataProvider providePathToUriData
	 */
	public function testPathToUri(string $path, string $expectedUri)
	{
		Assert::same($expectedUri, Helpers::pathToUri($path));
	}


	public function providePathToUriData()
	{
		yield ['/var/www/schema.neon', 'file:///var/www/schema.neon'];
		yield ['C:\\www\\schema.neon', 'file:///C:/www/schema.neon'];
	}


	/**
	 * @dataProvider provideUriToPathData
	 */
	public function testUriToPath(string $uri, string $expectedPath)
	{
		Assert::same($expectedPath, Helpers::uriToPath($uri));
	}


	public function provideUriToPathData()
	{
		yield ['file:///var/www/schema.neon', '/var/www/schema.neon'];
		yield ['file:///C:/www/schema.neon', 'C:/www/schema.neon'];
	}
}


(new HelpersTest)->run();
