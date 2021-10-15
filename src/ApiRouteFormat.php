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

	public const JSON_TYPE_NUMBER = 'number';
	public const JSON_TYPE_STRING = 'string';
	public const JSON_TYPE_BOOLEAN = 'boolean';
	public const JSON_TYPE_ARRAY = 'array';
	public const JSON_TYPE_OBJECT = 'object';
	public const JSON_TYPE_NULL = 'null';

	/** @var string */
	public static $errorPresenter = 'Error';
	/** @var string */
	public static $errorAction = 'error';
	/** @var bool */
	public static $throwErrors = FALSE;

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
	 * If FALSE, errors will be redirected into self::$errorPresenter, if TRUE, errors will be thrown by the route
	 * Defaults to FALSE
	 * @param bool $throwErrors
	 */
	public static function setThrowErrors(bool $throwErrors): void {
		self::$throwErrors = $throwErrors;
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
			return self::JSON_TYPE_NUMBER;
		} else if (is_string($body)) {
			return self::JSON_TYPE_STRING;
		} else if (is_bool($body)) {
			return self::JSON_TYPE_BOOLEAN;
		} else if (is_array($body)) {
			return self::JSON_TYPE_ARRAY;
		} else if (is_object($body)) {
			return self::JSON_TYPE_OBJECT;
		} else {
			return self::JSON_TYPE_NULL;
		}
	}

	protected function verifyBodyType($body, $types, $propertyPath): int {
		if (!is_array($types)) {
			$types = [$types];
		}
		$bodyType = $this->verifyTypeof($body);
		foreach ($types as $type) {
			if (!is_string($type)) {
				throw new FormatSchemaError("Property type must be an array of strings or string @$propertyPath");
			} else if ($type === $bodyType) {
				return TRUE;
			}
		}
		// No type for which input would be valid found
		throw new FormatInputError("Invalid type '$bodyType' @$propertyPath");
	}

	/**
	 * @param $body mixed Element to check
	 * @param $schema array JSON schema to check validity against
	 * @throws FormatInputError
	 * @throws FormatSchemaError
	 */
	protected function verifyBodyFormat($body, $schema, $propertyPath = 'body') {
		if (!is_array($schema)) {
			throw new FormatSchemaError("Property definition must be an associative array @$propertyPath");
		}
		if (isset($schema['type'])) {
			$this->verifyBodyType($body, $schema['type'], $propertyPath);
		}
		$type = $this->verifyTypeof($body);
		if (isset($schema['enum'])) {
			if (!is_array($schema['enum'])) {
				throw new FormatSchemaError("Enum must be array @$propertyPath");
			}
			$match = FALSE;
			foreach ($schema['enum'] as $enum) {
				if ($body === $enum) {
					$match = TRUE;
					break;
				}
			}
			if (!$match) {
				throw new FormatInputError("Property value doesn't match any value in enum @$propertyPath");
			}
		}
		if ($type === 'object') {
			// Check that object contains all required
			if (isset($schema['required'])) {
				if (!is_array($schema['required'])) {
					throw new FormatSchemaError("Required properties must be array @$propertyPath");
				}
				foreach ($schema['required'] as $required) {
					if (!is_string($required)) {
						throw new FormatSchemaError("Property name must be string @$propertyPath");
					} else if (!isset($body->$required)) {
						throw new FormatInputError("Missing required property '$required' @$propertyPath");
					}
				}
			}
			// Check that all elements match the schema
			foreach ($body as $key => $value) {
				if (isset($schema['properties'][$key])) {
					$this->verifyBodyFormat($value, $schema['properties'][$key], "$propertyPath:$key");
				} else if (isset($schema['additionalProperties'])) {
					if ($schema['additionalProperties'] === FALSE) {
						throw new FormatInputError("Additional properties not allowed @$propertyPath");
					}
					$this->verifyBodyFormat($value, $schema['additionalProperties'], "$propertyPath:$key");
				}
			}
		}
		// Check elements of an array
		else if ($type === 'array') {
			foreach ($body as $key => $value) {
				if (isset($schema['items'])) {
					$this->verifyBodyFormat($value, $schema['items'], "{$propertyPath}[{$key}]");
				}
			}
		}
		// Must not meet "not" subschema
		if (isset($schema['not'])) {
			$passes = FALSE;
			try {
				$this->verifyBodyFormat($body, $schema['not'], $propertyPath);
				// If verify doesn't throw, it passed, thus doesn't satisfy "not" subschema
				$passes = TRUE;
			} catch (FormatInputError $e) {
				// We want an error to verify it doesn't meet the subschema
			}
			if ($passes) {
				throw new FormatInputError("Must not meet 'not' subschema @$propertyPath");
			}
		}
		// Simply must verify against all subschemas
		if (isset($schema['allOf'])) {
			foreach ($schema['allOf'] as $id => $subschema) {
				$this->verifyBodyFormat($body, $subschema, $propertyPath);
			}
		}
		// Must verify against at least one subschema
		if (isset($schema['anyOf'])) {
			$verified = FALSE;
			foreach ($schema['anyOf'] as $id => $subschema) {
				try {
					$this->verifyBodyFormat($body, $subschema, $propertyPath);
					$verified = TRUE;
					break;
				} catch (FormatInputError $e) {
					// Some other subschema could meet instead
				}
			}
			if (!$verified) {
				throw new FormatInputError("Must meet at least one 'anyOf' subschema @$propertyPath");
			}
		}
		// Must verify against exactly one subschema
		if (isset($schema['oneOf'])) {
			$verified = 0;
			foreach ($schema['oneOf'] as $id => $subschema) {
				try {
					$this->verifyBodyFormat($body, $subschema, $propertyPath);
					$verified++;
				} catch (FormatInputError $e) {
					// We want most to not meet
				}
			}
			if ($verified === 0) {
				throw new FormatInputError("Must meet exactly one 'oneOf' subschema (didn't meet any) @$propertyPath");
			} else if ($verified >= 2) {
				throw new FormatInputError("Must meet exactly one 'oneOf' subschema (met more than one) @$propertyPath");
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
			if (self::$throwErrors) {
				throw $e;
			} else {
				return [
					'presenter' => self::$errorPresenter,
					'action' => self::$errorAction,
					'secured' => FALSE,
					'error' => $e::ERROR_MESSAGE,
					'code' => $e::ERROR_CODE,
					'message' => $e->getMessage(),
				];
			}
		}
		return $result;
	}


	public static function addRoutesBySpecification(\Nette\Application\Routers\RouteList $routerApi, array $apiRouteSpecification) {
		foreach ($apiRouteSpecification as $key => $route) {
			$routerApi[] = new ApiRouteFormat($route['path'], $route['presenter'], $route['body'], [
				'methods' => isset($route['action'])
					? [ $route['method'] => $route['action'] ]
					: [ $route['method'] ],
				'parameters' => isset($route['parameters']) ? $route['parameters'] : [],
			]);
		}

		return $routerApi;
	}

}
