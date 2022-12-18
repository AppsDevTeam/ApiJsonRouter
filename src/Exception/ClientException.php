<?php
declare(strict_types=1);

namespace ADT\ApiJsonRouter\Exception;

use Exception;

class ClientException extends Exception implements ApiJsonRouterException
{
	public function __construct(string $message, int $code)
	{
		parent::__construct($message, $code);
	}
}
