<?php

declare(strict_types=1);

namespace ADT\ApiJsonRouter\Exception;

use Exception;
use Nette\Http\IResponse;

class FormatInputException extends Exception implements ApiJsonRouterException
{
	public function __construct(string $message)
	{
		parent::__construct($message, IResponse::S400_BadRequest);
	}
}
