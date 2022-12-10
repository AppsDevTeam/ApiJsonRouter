<?php
declare(strict_types=1);

namespace ADT\ApiJsonRouter;

class FormatInputError extends \Exception {
	const ERROR_MESSAGE = 'INVALID_FORMAT';
	const ERROR_CODE = 400;
}
