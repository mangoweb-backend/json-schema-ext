<?php declare(strict_types = 1);

namespace MangowebTests\JsonSchemaExt;

use Mangoweb\JsonSchemaExt\ExampleBuilder;
use Mangoweb\JsonSchemaExt\InvalidExampleException;
use Mangoweb\JsonSchemaExt\ISchemaValidator;
use Mangoweb\JsonSchemaExt\SchemaValidationError;
use Tester\Assert;
use Tester\TestCase;
use function Eloquent\Phony\mock;


require __DIR__ . '/../../bootstrap.php';


/**
 * @testCase
 */
class ExampleBuilderTest extends TestCase
{
	public function testBuildExample()
	{
		$validator = mock(ISchemaValidator::class)->get();
		$builder = new ExampleBuilder($validator);

		$schemaA = (object) [
			'example' => 123,
			'foo' => 'bar',
		];

		Assert::same(123, $builder->buildExample($schemaA));

		$schemaB = (object) [
			'oneOf' => [$schemaA],
		];

		Assert::same(123, $builder->buildExample($schemaB));

		$schemaC = (object) [
			'allOf' => [$schemaA, $schemaB],
		];

		Assert::same(123, $builder->buildExample($schemaC));

		$schemaD = (object) [
			'allOf' => [
				(object) [
					'properties' => (object) [
						'a' => (object) ['example' => 'A'],
						'b' => (object) ['example' => 'B'],
					],
				],
				(object) [
					'properties' => (object) [
						'a' => (object) ['example' => 'A2'],
						'c' => (object) ['example' => 'C'],
					],
				],
			],
		];

		Assert::equal(
			(object) ['a' => 'A', 'b' => 'B', 'c' => 'C'],
			$builder->buildExample($schemaD)
		);

		$schemaE = (object) [
			'items' => (object) [
				'properties' => (object) [
					'a' => (object) ['example' => 'A'],
					'b' => (object) ['example' => 'B'],
				],
			],
			'minItems' => 2,
		];

		Assert::equal(
			[(object) ['a' => 'A', 'b' => 'B'], (object) ['a' => 'A', 'b' => 'B']],
			$builder->buildExample($schemaE)
		);

		$schemaF = (object) [
			'items' => [
				(object) ['example' => 'A'],
				(object) ['example' => 'B'],
			],
			'minItems' => 2,
		];

		Assert::equal(
			['A', 'B'],
			$builder->buildExample($schemaF)
		);
	}


	public function testBuildInvalidExample()
	{
		$validatorHandle = mock(ISchemaValidator::class);
		$validatorHandle->validate->returns([
			new SchemaValidationError([], 'invalid type'),
			new SchemaValidationError(['foo', 'bar'], 'another error'),
		]);

		$validator = $validatorHandle->get();
		$builder = new ExampleBuilder($validator);

		$schema = (object) [
			'type' => 'array',
			'example' => 123,
		];

		$ex = Assert::exception(
			function () use ($builder, $schema) {
				$builder->buildExample($schema);
			},
			InvalidExampleException::class,
			"Generated example does NOT validate against the corresponding schema:\n" .
			"  /: invalid type\n" .
			"  /foo/bar: another error"
		);

		Assert::same(123, $ex->getExample());
		Assert::same($schema, $ex->getSchema());
	}
}


(new ExampleBuilderTest)->run();
