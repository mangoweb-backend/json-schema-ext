<?php declare(strict_types = 1);

namespace Mangoweb\JsonSchemaExt;

use Throwable;


class FileNotFoundException extends \RuntimeException
{
	/** @var string */
	private $path;


	public function __construct(string $path, string $message, ?Throwable $previous = NULL)
	{
		parent::__construct($message, 0, $previous);

		$this->path = $path;
	}


	public function getPath(): string
	{
		return $this->path;
	}
}
