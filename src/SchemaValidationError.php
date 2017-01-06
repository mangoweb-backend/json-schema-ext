<?php declare(strict_types = 1);

namespace Mangoweb\JsonSchemaExt;


class SchemaValidationError
{
	/** @var string[] */
	private $path;

	/** @var string */
	private $message;


	public function __construct(array $path, string $message)
	{
		$this->path = $path;
		$this->message = $message;
	}


	/**
	 * @return array|string[]
	 */
	public function getPath(): array
	{
		return $this->path;
	}


	public function getMessage(): string
	{
		return $this->message;
	}


	public function __toString(): string
	{
		return '/' . implode('/', $this->path) . ": {$this->message}";
	}
}
