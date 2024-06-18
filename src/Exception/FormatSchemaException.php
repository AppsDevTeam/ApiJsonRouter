<?php

declare(strict_types=1);

namespace ADT\ApiJsonRouter\Exception;

use Exception;

class FormatSchemaException extends Exception implements ApiJsonRouterException
{
	public function __construct(string $message)
	{
		parent::__construct($message, 500);
	}
}
