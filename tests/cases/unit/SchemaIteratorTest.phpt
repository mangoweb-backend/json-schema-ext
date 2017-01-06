<?php declare(strict_types = 1);

namespace MangowebTests\JsonSchemaExt;

use Mangoweb\JsonSchemaExt\SchemaIterator;
use stdClass;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';


/**
 * @testCase
 */
class SchemaIteratorTest extends TestCase
{
	public function testEmpty()
	{
		$iterator = SchemaIterator::createShallow(new stdClass);
		Assert::null($iterator->current());
		Assert::null($iterator->key());
		Assert::false($iterator->valid());
		Assert::same([], iterator_to_array($iterator));
	}


	public function testShallowIteration()
	{
		$schema = (object) [
			'not' => $subSchemaA = (object) [],
			'allOf' => [
				$subSchemaB = (object) [],
			],
			'definitions' => (object) [
				$subSchemaC = (object) [],
				$subSchemaD = (object) [],
			],
		];

		$iterator = SchemaIterator::createShallow($schema);
		Assert::same([$subSchemaA, $subSchemaB, $subSchemaC, $subSchemaD], iterator_to_array($iterator));
	}


	public function testRecursiveIterationWithAcyclicSchema()
	{
		$schema = (object) [
			'not' => $subSchemaA = (object) ['A'],
			'allOf' => [
				$subSchemaB = (object) [
					'definitions' => (object) [
						$subSchemaBA = (object) [
							'not' => $subSchemaBAA = (object) ['BA'],
						],
					],
				],
			],
			'definitions' => (object) [
				$subSchemaC = (object) ['C'],
				$subSchemaD = (object) ['D'],
			],
		];

		$iterator = SchemaIterator::createRecursive($schema);

		Assert::same(
			[$schema, $subSchemaA, $subSchemaB, $subSchemaBA, $subSchemaBAA, $subSchemaC, $subSchemaD],
			iterator_to_array($iterator)
		);
	}


	public function testRecursiveIterationWithCyclicSchema()
	{
		$schema = (object) [
			'not' => $subSchemaA = (object) ['A'],
			'allOf' => [
				$subSchemaB = (object) [
					'definitions' => (object) [
						$subSchemaBA = (object) [
							'not' => & $schema,
						],
					],
				],
			],
			'definitions' => (object) [
				$subSchemaC = (object) ['C'],
				$subSchemaD = (object) ['D'],
			],
		];

		$iterator = SchemaIterator::createRecursive($schema);

		Assert::same(
			[$schema, $subSchemaA, $subSchemaB, $subSchemaBA, 5 => $subSchemaC, $subSchemaD],
			iterator_to_array($iterator)
		);
	}
}


(new SchemaIteratorTest)->run();
