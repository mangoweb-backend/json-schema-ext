<?php declare(strict_types = 1);

namespace MangowebTests\JsonSchemaExt;

use AssertionError;
use Mangoweb\JsonSchemaExt\SchemaPreprocessor;
use stdClass;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';


/**
 * @testCase
 */
class SchemaPreprocessorTest extends TestCase
{
	public function testApplyNoOp()
	{
		$schema = (object) [
			'properties' => (object) [
				'foo' => (object) [],
				'bar' => (object) [],
			],
			'optional' => 'ignored',
		];

		$preprocessor = new SchemaPreprocessor;
		$preprocessor->apply($schema);

		Assert::true(!isset($schema->required));
		Assert::same('ignored', $schema->optional); // remains untouched
		Assert::true(!isset($schema->additionalProperties));
	}


	public function testApplyRequiredConstraint()
	{
		$preprocessor = new SchemaPreprocessor;
		$preprocessor->requireAllPropertiesByDefault();

		$schemaA = (object) [
			'type' => 'object',
			'properties' => (object) [
				'foo' => (object) [],
				'bar' => (object) [],
			]
		];

		$preprocessor->apply($schemaA);
		Assert::same(['foo', 'bar'], $schemaA->required);
		Assert::true(!isset($schemaA->additionalProperties));

		$schemaB = (object) [
			'type' => 'object',
			'properties' => (object) [
				'foo' => (object) [],
				'bar' => (object) [],
			],
			'required' => [],
		];

		$preprocessor->apply($schemaB);
		Assert::same([], $schemaB->required);
		Assert::true(!isset($schemaB->additionalProperties));

		$schemaC = (object) [
			'properties' => (object) [
				'foo' => (object) [],
				'bar' => (object) [],
			],
		];

		$preprocessor->apply($schemaC);
		Assert::true(!isset($schemaC->required));
		Assert::true(!isset($schemaC->additionalProperties));

		$schemaD = (object) [
			'type' => 'object',
		];

		$preprocessor->apply($schemaD);
		Assert::true(!isset($schemaD->required));
		Assert::true(!isset($schemaD->additionalProperties));
	}


	public function testApplyOptionalConstraint()
	{
		$preprocessor = new SchemaPreprocessor;
		$preprocessor->allowOptionalConstraint();

		$schemaA = (object) [
			'type' => 'object',
			'properties' => (object) [
				'foo' => (object) [],
				'bar' => (object) [],
			],
			'optional' => ['foo'],
		];

		$preprocessor->apply($schemaA);
		Assert::same(['bar'], $schemaA->required);
		Assert::true(!isset($schemaA->optional));
		Assert::true(!isset($schemaA->additionalProperties));
	}


	public function testApplyOptionalConstraintToPartialObject()
	{
		$preprocessor = new SchemaPreprocessor;
		$preprocessor->allowOptionalConstraint();

		$schemaA = (object) [
			'properties' => (object) [
				'foo' => (object) [],
				'bar' => (object) [],
			],
			'optional' => ['foo'],
		];

		$preprocessor->apply($schemaA);
		Assert::same(['bar'], $schemaA->required);
		Assert::true(!isset($schemaA->optional));
		Assert::true(!isset($schemaA->additionalProperties));
	}


	public function testApplyWithBothRequiredAndOptionalConstraintsDefined()
	{
		$schemaA = (object) [
			'type' => 'object',
			'properties' => (object) [
				'foo' => (object) [],
				'bar' => (object) [],
			],
			'required' => [],
			'optional' => [],
		];

		$preprocessor = new SchemaPreprocessor;
		$preprocessor->apply($schemaA);
		Assert::same([], $schemaA->required);
		Assert::same([], $schemaA->optional); // remains untouched
		Assert::true(!isset($schemaA->additionalProperties));

		Assert::exception(function () {
			$schemaB = (object) [
				'type' => 'object',
				'properties' => (object) [
					'foo' => (object) [],
					'bar' => (object) [],
				],
				'required' => [],
				'optional' => [],
			];

			$preprocessor = new SchemaPreprocessor;
			$preprocessor->allowOptionalConstraint();
			$preprocessor->apply($schemaB);
		}, AssertionError::class, 'At most one of \'required\' and \'optional\' constraints can be defined');
	}


	public function testApplyDisallowAdditionalProperties()
	{
		$schema = (object) [
			'type' => 'object',
			'properties' => (object) [
				'foo' => (object) [],
				'bar' => (object) [],
			]
		];

		$preprocessor = new SchemaPreprocessor;
		$preprocessor->disallowAdditionalPropertiesByDefault();
		$preprocessor->apply($schema);

		Assert::true(!isset($schema->required));
		Assert::false($schema->additionalProperties);
	}


	public function testApplyKeyRemoval()
	{
		$schema = (object) [
			'type' => 'object',
			'properties' => (object) [
				'foo' => (object) [],
				'bar' => (object) [],
			]
		];

		$preprocessor = new SchemaPreprocessor;
		$preprocessor->setRemovedKeys(['type']);
		$preprocessor->apply($schema);

		Assert::true(!isset($schema->type));
	}


	public function testApplyGlobalDefinitionsA()
	{
		$schema = (object) [
			'type' => 'object',
			'properties' => (object) [
				'foo' => (object) ['$ref' => '#/definitions/defB'],
				'bar' => (object) [],
			]
		];

		$preprocessor = new SchemaPreprocessor;
		$preprocessor->setGlobalDefinitions([
			'defA' => (object) ['type' => 'string'],
			'defB' => (object) ['$ref' => '#/definitions/defA'],
			'defC' => (object) ['type' => 'number'],
		]);

		$preprocessor->apply($schema);

		Assert::equal(
			(object) ['type' => 'string'],
			$schema->definitions->defA
		);

		Assert::equal(
			(object) ['$ref' => '#/definitions/defA'],
			$schema->definitions->defB
		);

		Assert::true(!isset($schema->definitions->defC));
	}


	public function testApplyGlobalDefinitionsB()
	{
		$schema = (object) [
			'definitions' => (object) [
				'defX' => (object) [],
			],
			'type' => 'object',
			'properties' => (object) [
				'foo' => (object) ['$ref' => '#/definitions/defB'],
				'bar' => (object) [],
			]
		];

		$preprocessor = new SchemaPreprocessor;
		$preprocessor->setGlobalDefinitions([
			'defA' => (object) ['type' => 'string'],
			'defB' => (object) ['$ref' => '#/definitions/defA'],
			'defC' => (object) ['type' => 'number'],
		]);

		$preprocessor->apply($schema);

		Assert::equal(
			(object) ['type' => 'string'],
			$schema->definitions->defA
		);

		Assert::equal(
			(object) ['$ref' => '#/definitions/defA'],
			$schema->definitions->defB
		);

		Assert::equal(
			(object) [],
			$schema->definitions->defX
		);

		Assert::true(!isset($schema->definitions->defC));
	}


	public function testApplyRecursive()
	{
		$schema = (object) [
			'type' => 'object',
			'properties' => (object) [
				'a' => & $schema, // cyclic
				'b' => (object) [
					'type' => ['object', 'null'],
					'properties' => (object) [
						'c' => (object) [
							'type' => 'object',
						],
						'd' => (object) [],
					],
					'optional' => ['d'],
				],
			]
		];

		$preprocessor = new SchemaPreprocessor;
		$preprocessor->allowOptionalConstraint();
		$preprocessor->requireAllPropertiesByDefault();
		$preprocessor->disallowAdditionalPropertiesByDefault();

		$preprocessor->applyRecursive($schema);

		Assert::true(!isset($schema->properties->b->optional));

		Assert::same(['a', 'b'], $schema->required);
		Assert::same(['c'], $schema->properties->b->required);

		Assert::same(FALSE, $schema->additionalProperties);
		Assert::same(FALSE, $schema->properties->b->additionalProperties);;
		Assert::same(FALSE, $schema->properties->b->properties->c->additionalProperties);
		Assert::equal(new stdClass, $schema->properties->b->properties->c->properties);
	}
}


(new SchemaPreprocessorTest)->run();
