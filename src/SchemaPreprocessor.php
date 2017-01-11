<?php declare(strict_types = 1);

namespace Mangoweb\JsonSchemaExt;

use stdClass;


class SchemaPreprocessor
{
	/** @var bool */
	private $allowOptionalConstraint = FALSE;

	/** @var bool */
	private $requireAllPropertiesByDefault = FALSE;

	/** @var bool */
	private $disallowAdditionalPropertiesByDefault = FALSE;

	/** @var array|string[] */
	private $removedKeys = [];

	/** @var array|stdClass[] */
	private $globalDefinitions = [];


	public function apply(stdClass $schema): stdClass
	{
		$this->applyToTopSchema($schema);
		$this->applyToSubSchema($schema);

		return $schema;
	}


	public function applyRecursive(stdClass $schema): stdClass
	{
		$this->applyToTopSchema($schema);
		foreach (SchemaIterator::createRecursive($schema) as $subSchema) {
			$this->applyToSubSchema($subSchema);
		}

		return $schema;
	}


	public function allowOptionalConstraint(bool $allow = TRUE): self
	{
		$this->allowOptionalConstraint = $allow;
		return $this;
	}


	public function requireAllPropertiesByDefault(bool $require = TRUE): self
	{
		$this->requireAllPropertiesByDefault = $require;
		return $this;
	}


	public function disallowAdditionalPropertiesByDefault(bool $disallow = TRUE): self
	{
		$this->disallowAdditionalPropertiesByDefault = $disallow;
		return $this;
	}


	public function setRemovedKeys(array $removedKeys): self
	{
		$this->removedKeys = $removedKeys;
		return $this;
	}


	public function setGlobalDefinitions(array $definitions): self
	{
		$this->globalDefinitions = $definitions;
		return $this;
	}


	private function applyToTopSchema(stdClass $schema): void
	{
		if ($this->globalDefinitions) {
			$definitions = $schema->definitions ?? new stdClass();
			$refs = $this->getReferencedDefinitions($schema);

			do {
				$refsCountBefore = count($refs);
				foreach ($this->globalDefinitions as $defKey => $defSchema) {
					if (!isset($definitions->$defKey) && isset($refs["#/definitions/$defKey"])) {
						$definitions->$defKey = $defSchema;
						$refs += $this->getReferencedDefinitions($defSchema);
					}
				}

			} while (count($refs) > $refsCountBefore);

			if (count((array) $definitions) > 0) {
				$schema->definitions = $definitions;
			}
		}
	}


	private function applyToSubSchema(stdClass $schema): void
	{
		$isObject = isset($schema->type) && in_array('object', (array) $schema->type, TRUE);
		$isPartialObject = isset($schema->properties);

		if ($isPartialObject) {
			if ($this->allowOptionalConstraint && isset($schema->optional)) {
				assert(is_array($schema->optional), 'The \'optional\' constraint must be an array of property names');
				assert(!isset($schema->required), 'At most one of \'required\' and \'optional\' constraints can be defined');
				$schema->required = array_values(array_diff(array_keys((array) $schema->properties), $schema->optional));
				unset($schema->optional);

			} elseif ($isObject && $this->requireAllPropertiesByDefault && !isset($schema->required)) {
				$schema->required = array_keys((array) $schema->properties);
			}
		}


		if ($isObject) {
			if ($this->disallowAdditionalPropertiesByDefault && !isset($schema->additionalProperties)) {
				$schema->additionalProperties = FALSE;
				if (!isset($schema->properties)) {
					$schema->properties = new stdClass(); // required to make additionalProperties actually work
				}
			}
		}

		foreach ($this->removedKeys as $key) {
			unset($schema->$key);
		}
	}


	private function getReferencedDefinitions(stdClass $schema): array
	{
		$refs = [];
		foreach (SchemaIterator::createRecursive($schema) as $subSchema) {
			if (isset($subSchema->{'$ref'}) && is_string($subSchema->{'$ref'})) {
				$refs[$subSchema->{'$ref'}] = TRUE;
			}
		}

		return $refs;
	}
}
