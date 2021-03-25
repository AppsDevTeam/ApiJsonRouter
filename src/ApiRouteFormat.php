<?php

namespace ADT\ApiJsonRouter;

use Contributte\ApiRouter\ApiRoute;
use Nette;

class FormatSchemaError extends \Exception {
	const ERROR_MESSAGE = 'VERIFICATION_ERROR';
	const ERROR_CODE = 500;
}

class FormatInputError extends \Exception {
	const ERROR_MESSAGE = 'INVALID_FORMAT';
	const ERROR_CODE = 400;
}

class ApiRouteFormat extends ApiRoute
{
	/** @var array|null */
	protected $bodySchema = NULL;

	/** @var string */
	public static $errorPresenter = 'Error';
	/** @var string */
	public static $errorAction = 'error';

	/**
	 * @param string $errorPresenter
	 */
	public static function setErrorPresenter(string $errorPresenter): void {
		self::$errorPresenter = $errorPresenter;
	}

	/**
	 * @param string $errorAction
	 */
	public static function setErrorAction(string $errorAction): void {
		self::$errorAction = $errorAction;
	}

	/**
	 * ApiRouteFormat constructor.
	 * @param $path Path being routed
	 * @param string|null $presenter Presenter
	 * @param ?array $bodySchema JSON schema as returned when parsed by json_decode
	 * @param array $data See parent constructor
	 */
	public function __construct($path, string $presenter = NULL, ?array $bodySchema = NULL, array $data = []) {
		parent::__construct($path, $presenter, $data);
		$this->bodySchema = $bodySchema;
	}

	protected function verifyTypeof($body): string {
		if (is_int($body) || is_float($body)) {
			return 'number';
		} else if (is_string($body)) {
			return 'string';
		} else if (is_bool($body)) {
			return 'boolean';
		} else if (is_array($body)) {
			return 'array';
		} else if (is_object($body)) {
			return 'object';
		} else {
			return 'null';
		}
	}

	protected function verifyBodyType($body, $types): int {
		if (!is_array($types)) {
			$types = [$types];
		}
		$bodyType = $this->verifyTypeof($body);
		foreach ($types as $type) {
			if (!is_string($type)) {
				throw new FormatSchemaError('Property type must be an array of strings or string');
			} else if ($type === $bodyType) {
				return TRUE;
			}
		}
		// No type for which input would be valid found
		throw new FormatInputError('Invalid type on input');
	}

	/**
	 * @param $body mixed Element to check
	 * @param $schema array JSON schema to check validity against
	 * @throws FormatInputError
	 * @throws FormatSchemaError
	 */
	protected function verifyBodyFormat($body, $schema) {
		if (!is_array($schema)) {
			throw new FormatSchemaError('Property definition must be an associative array');
		}
		if (isset($schema['type'])) {
			$this->verifyBodyType($body, $schema['type']);
		}
		$type = $this->verifyTypeof($body);
		if (isset($schema['enum'])) {
			if (!is_array($schema['enum'])) {
				throw new FormatSchemaError('Enum must be array');
			}
			$match = FALSE;
			foreach ($schema['enum'] as $enum) {
				if ($body === $enum) {
					$match = TRUE;
					break;
				}
			}
			if (!$match) {
				throw new FormatSchemaError('Property value doesn\'t match any value in enum');
			}
		}
		if ($type === 'object') {
			// Check that object contains all required
			if (isset($schema['required'])) {
				if (!is_array($schema['required'])) {
					throw new FormatSchemaError('Required properties must be array');
				}
				foreach ($schema['required'] as $required) {
					if (!is_string($required)) {
						throw new FormatSchemaError('Property name must be string');
					} else if (!isset($body->$required)) {
						throw new FormatInputError('Missing a required property');
					}
				}
			}
			// Check that all elements match the schema
			foreach ($body as $key => $value) {
				if (isset($schema['properties'][$key])) {
					$this->verifyBodyFormat($value, $schema['properties'][$key]);
				} else if (isset($schema['additionalProperties'])) {
					if ($schema['additionalProperties'] === FALSE) {
						throw new FormatInputError('Additional properties not allowed');
					}
					$this->verifyBodyFormat($value, $schema['additionalProperties']);
				}
			}
		}
		// Check elements of an array
		else if ($type === 'array') {
			foreach ($body as $key => $value) {
				if (isset($schema['items'])) {
					$this->verifyBodyFormat($value, $schema->items);
				}
			}
		}
	}

	public function match(Nette\Http\IRequest $httpRequest): ?array {
		$result = parent::match($httpRequest);

		if ($result === NULL || $this->bodySchema === NULL) {
			return $result;
		}

		$rawBody = $httpRequest->getRawBody();
		if (preg_match("/^\s*$/", $rawBody) === 1) {
			$rawBody = 'null';
		}

		// $this->bodyFormat must be an array because of typing
		try {
			$body = json_decode($rawBody, FALSE, 512);
			if (json_last_error() !== JSON_ERROR_NONE) {
				throw new FormatInputError('Input data are not valid JSON');
			}
			$this->verifyBodyFormat($body, $this->bodySchema);
			if (isset($this->bodySchema['properties'])) {
				foreach ($this->bodySchema['properties'] as $key => $value) {
					if (isset($body->$key)) {
						$result["_$key"] = $body->$key;
					}
				}
			}
		} catch (FormatSchemaError | FormatInputError $e) {
			return [
				'presenter' => self::$errorPresenter,
				'action' => self::$errorAction,
				'secured' => FALSE,
				'error' => $e::ERROR_MESSAGE,
				'code' => $e::ERROR_CODE,
			];
		}
		return $result;
	}


	public static function addRoutesBySpecification(\Nette\Application\Routers\RouteList $routerApi, array $apiRouteSpecification) {
		foreach ($apiRouteSpecification as $key => $route) {
			$routerApi[] = new ApiRouteFormat($route['path'], $route['presenter'], $route['body'], [
				'methods' => isset($route['action'])
					? [ $route['method'] => $route['action'] ]
					: [ $route['method'] ]
			]);
		}

		return $routerApi;
	}

}
