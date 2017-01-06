<?php declare(strict_types = 1);

namespace Mangoweb\JsonSchemaExt;

use Nette\Utils\Json;
use stdClass;


class ExampleBuilder
{
	/** @var NULL|ISchemaValidator */
	private $validator;


	public function __construct(?ISchemaValidator $validator)
	{
		$this->validator = $validator;
	}


	public function buildExample(stdClass $schema)
	{
		if (isset($schema->example)) {
			$this->checkValidity($schema->example, $schema);
			return $schema->example;
		}

		$example = NULL;

		if (isset($schema->oneOf)) {
			$example = $this->mergeExamples(
				$example,
				$this->buildExample($schema->oneOf[0])
			);
		}

		if (isset($schema->anyOf)) {
			$example = $this->mergeExamples(
				$example,
				$this->buildExample($schema->anyOf[0])
			);
		}

		if (isset($schema->allOf)) {
			foreach ($schema->allOf as $subSchema) {
				$example = $this->mergeExamples(
					$example,
					$this->buildExample($subSchema)
				);
			}
		}

		if (isset($schema->properties)) {
			$example2 = new stdClass();
			foreach ($schema->properties as $propertyName => $propertySchema) {
				$example2->$propertyName = $this->buildExample($propertySchema);
			}

			$example = $this->mergeExamples($example, $example2);
		}

		if (isset($schema->items)) {
			if ($schema->items instanceof stdClass) {
				$example = $this->mergeExamples(
					$example,
					array_fill(
						0,
						max(1, $schema->minItems ?? 0),
						$this->buildExample($schema->items)
					)
				);

			} elseif (is_array($schema->items)) {
				$example2 = [];
				foreach ($schema->items as $itemIndex => $itemSchema) {
					$example2[$itemIndex] = $this->buildExample($itemSchema);
				}

				$example = $this->mergeExamples($example, $example2);
			}
		}

		$this->checkValidity($example, $schema);
		return $example;
	}


	private function mergeExamples($a, $b)
	{
		if ($a === NULL || $a === $b || Json::encode($a) === Json::encode($b)) {
			return $b;

		} elseif ($a instanceof stdClass && $b instanceof stdClass) {
			return (object) ((array) $a + (array) $b);

		} else {
			throw new ImplementationException();
		}
	}


	private function checkValidity($example, stdClass $schema)
	{
		if ($this->validator === NULL) {
			return;
		}

		$errors = $this->validator->validate($example, $schema);
		if (count($errors) > 0) {
			throw new InvalidExampleException($example, $schema, $errors);
		}
	}
}
