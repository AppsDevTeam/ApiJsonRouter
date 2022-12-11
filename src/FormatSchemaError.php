<?php
declare(strict_types=1);

namespace ADT\ApiJsonRouter;

class FormatSchemaError extends \Exception {
	const ERROR_MESSAGE = 'VERIFICATION_ERROR';
	const ERROR_CODE = 500;
}
