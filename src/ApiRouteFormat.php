<?php

namespace ADT\ApiJsonRouter;

use Ublaboo\ApiRouter\ApiRoute;
use Nette;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Exceptions\ParseException;
use Opis\JsonSchema\Exceptions\SchemaException;
use Opis\JsonSchema\Exceptions\UnresolvedException;
use Opis\JsonSchema\JsonPointer;
use Opis\JsonSchema\Validator;


class ApiRouteFormat extends ApiRoute
{
	/** @var array|null */
	protected $bodySchema = NULL;

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

	protected function verifyBodyFormat($body, $schema) {
		$validator = new Validator();
		$validator->resolver()->registerRaw(json_encode($schema));

		$schemaErrorMessage = null;
		try {
			$result = $validator->validate($body, json_encode($schema));
		} catch (ParseException $e) {
			$schemaErrorMessage = [JsonPointer::pathToString($e->schemaInfo()->path()) => [$e->getMessage()]];
		} catch (UnresolvedException $e) {
			$schemaErrorMessage = [JsonPointer::pathToString($e->getSchema()->info()->path()) => [$e->getMessage()]];
		} catch (SchemaException $e) {
			$schemaErrorMessage = ['/' => $e->getMessage()];
		}
		if ($schemaErrorMessage) {
			throw new FormatSchemaError(json_encode($schemaErrorMessage, JSON_UNESCAPED_SLASHES));
		}

		if (!$result->isValid()) {
			$formatter = new ErrorFormatter();
			$message = json_encode($formatter->format($result->error()), JSON_UNESCAPED_SLASHES);
			throw new FormatInputError($message);
		}
	}

	public function match(Nette\Http\IRequest $httpRequest): ?Nette\Application\Request {
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
				$parameters = $result->getParameters();
				foreach ($this->bodySchema['properties'] as $key => $value) {
					if (isset($body->$key)) {
						$parameters["_$key"] = $body->$key;
					}
				}
				$result->setParameters($parameters);
			}
		} catch (FormatSchemaError | FormatInputError $e) {
			if (self::$throwErrors) {
				throw $e;
			} else {
				return new Nette\Application\Request(
					self::$errorPresenter,
					Nette\Application\Request::FORWARD,
					[
						'action' => self::$errorAction,
						'error' => $e::ERROR_MESSAGE,
						'code' => $e::ERROR_CODE,
						'message' => $e->getMessage(),
					],
					$httpRequest->getPost(),
					$httpRequest->getFiles(),
					[Nette\Application\Request::SECURED => $httpRequest->isSecured()]
				);
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
