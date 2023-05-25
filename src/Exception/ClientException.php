<?php

declare(strict_types=1);

namespace ADT\ApiJsonRouter\Exception;

use Exception;

class ClientException extends Exception implements ApiJsonRouterException
{
	private ?int $errorCode = null;

	public function __construct(string $message, int $httpCode, ?int $errorCode = null, $previous = null)
	{
		parent::__construct($message, $httpCode, $previous);

		$this->errorCode = $errorCode;
	}

	public function getErrorCode(): ?int
	{
		return $this->errorCode;
	}
}
