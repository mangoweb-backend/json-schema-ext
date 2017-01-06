<?php declare(strict_types = 1);

namespace Mangoweb\JsonSchemaExt;

use Generator;
use Iterator;
use RecursiveCallbackFilterIterator;
use RecursiveIterator;
use RecursiveIteratorIterator;
use SplObjectStorage;
use stdClass;


class SchemaIterator implements RecursiveIterator
{
	const SCHEMA_KEYS = ['additionalItems', 'additionalProperties', 'items', 'not'];
	const SCHEMA_ARRAY_KEYS = ['items', 'allOf', 'anyOf', 'oneOf'];
	const SCHEMA_OBJECT_KEYS = ['definitions', 'properties', 'patternProperties', 'dependencies'];

	/** @var stdClass */
	private $schema;

	/** @var Generator */
	private $generator;

	/** @var int */
	private $key = 0;

	/** @var bool */
	private $includeSelf = FALSE;


	private function __construct(stdClass $schema)
	{
		$this->schema = $schema;
		$this->rewind();
	}


	/**
	 * Creates shallow iterator which iterates over all direct sub-schemas.
	 *
	 * @param  stdClass $schema
	 * @return Iterator|stdClass[]
	 */
	public static function createShallow(stdClass $schema): Iterator
	{
		return new self($schema);
	}


	/**
	 * Creates recursive iterator which iterates over all sub-schemas.
	 *
	 * @param  stdClass $schema
	 * @return Iterator|stdClass[]
	 */
	public static function createRecursive(stdClass $schema): Iterator
	{
		$uniqueStorage = new SplObjectStorage();
		$uniqueFilter = function (stdClass $schema) use ($uniqueStorage) {
			if ($uniqueStorage->contains($schema)) {
				return FALSE;

			} else {
				$uniqueStorage->attach($schema);
				return TRUE;
			}
		};

		$schemaIterator = new self($schema);
		$schemaIterator->includeSelf = TRUE;

		return new RecursiveIteratorIterator(
			new RecursiveCallbackFilterIterator(
				$schemaIterator,
				$uniqueFilter
			),
			RecursiveIteratorIterator::SELF_FIRST
		);
	}

	/**
	 * Creates recursive iterator which iterates over all sub-schemas.
	 * Supports only acyclic schemas.
	 *
	 * @param  stdClass $schema
	 * @return Iterator|stdClass[]
	 */
	public static function createRecursiveForAcyclicSchema(stdClass $schema): Iterator
	{
		$schemaIterator = new self($schema);
		$schemaIterator->includeSelf = TRUE;

		return new RecursiveIteratorIterator(
			$schemaIterator,
			RecursiveIteratorIterator::SELF_FIRST
		);
	}


	public function rewind(): void
	{
		$this->generator = $this->createInnerGenerator();
	}


	public function current(): ?stdClass
	{
		return $this->generator->current();
	}


	public function key(): ?int
	{
		return $this->generator->valid() ? $this->key++ : NULL;
	}


	public function next(): void
	{
		$this->generator->next();
	}


	public function valid(): bool
	{
		return $this->generator->valid();
	}


	public function hasChildren(): bool
	{
		return TRUE;
	}


	public function getChildren(): self
	{
		$schema = $this->generator->current();
		$iterator = new self($schema);
		$iterator->key = &$this->key;

		return $iterator;
	}


	private function createInnerGenerator(): Generator
	{
		if ($this->includeSelf) {
			yield $this->schema;
		}

		foreach (self::SCHEMA_KEYS as $key) {
			if (isset($this->schema->$key) && $this->schema->$key instanceof stdClass) {
				yield $this->schema->$key;
			}
		}

		foreach (self::SCHEMA_ARRAY_KEYS as $key) {
			if (isset($this->schema->$key) && is_array($this->schema->$key)) {
				foreach ($this->schema->$key as $subSchema) {
					if ($subSchema instanceof stdClass) {
						yield $subSchema;
					}
				}
			}
		}

		foreach (self::SCHEMA_OBJECT_KEYS as $key) {
			if (isset($this->schema->$key) && $this->schema->$key instanceof stdClass) {
				foreach ($this->schema->$key as $subSchema) {
					if ($subSchema instanceof stdClass) {
						yield $subSchema;
					}
				}
			}
		}
	}
}
