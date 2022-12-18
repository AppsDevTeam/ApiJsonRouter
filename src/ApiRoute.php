<?php

namespace ADT\ApiJsonRouter;

use ADT\ApiJsonRouter\Exception\ClientException;
use ADT\ApiJsonRouter\Exception\FormatInputException;
use ADT\ApiJsonRouter\Exception\FormatSchemaException;
use Nette;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Exceptions\ParseException;
use Opis\JsonSchema\Exceptions\SchemaException;
use Opis\JsonSchema\Exceptions\UnresolvedException;
use Opis\JsonSchema\JsonPointer;
use Opis\JsonSchema\Validator;

class ApiRoute extends \Contributte\ApiRouter\ApiRoute
{
	protected ?array $bodySchema;

	/**
	 * @param array|string $path Path being routed
	 * @param string $presenter Presenter
	 * @param array $data See parent constructor
	 * @param ?array $bodySchema JSON schema as returned when parsed by json_decode
	 */
	public function __construct($path, string $presenter, array $data, ?array $bodySchema = null)
	{
		parent::__construct($path, $presenter, $data);
		$this->bodySchema = $bodySchema;
	}

	/**
	 * @throws FormatInputException
	 * @throws FormatSchemaException
	 */
	protected function verifyBodyFormat($body, $schema)
	{
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
			throw new FormatSchemaException(json_encode($schemaErrorMessage, JSON_UNESCAPED_SLASHES));
		}

		if (!$result->isValid()) {
			$formatter = new ErrorFormatter();
			$message = json_encode($formatter->format($result->error()), JSON_UNESCAPED_SLASHES);
			throw new FormatInputException($message);
		}
	}

	/**
	 * @throws FormatInputException
	 * @throws FormatSchemaException
	 */
	public function match(Nette\Http\IRequest $httpRequest): ?array
	{
		$result = parent::match($httpRequest);

		if ($result === NULL || $this->bodySchema === NULL) {
			return $result;
		}

		$rawBody = $httpRequest->getRawBody();
		if (preg_match("/^\s*$/", $rawBody) === 1) {
			$rawBody = 'null';
		}

		$body = json_decode($rawBody);
		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new FormatInputException('Input data is not valid JSON.');
		}
		$this->verifyBodyFormat($body, $this->bodySchema);
		if (isset($this->bodySchema['properties'])) {
			foreach ($this->bodySchema['properties'] as $key => $value) {
				if (isset($body->$key)) {
					$result["_$key"] = $body->$key;
				}
			}
		}

		return $result;
	}

	/**
	 * @throws ClientException
	 */
	public function resolveMethod(Nette\Http\IRequest $request): string
	{
		if (!in_array($request->getMethod(), $this->getMethods(), true)) {
			throw new ClientException('Allowed methods: ' . implode(', ', $this->getMethods()) . '.', Nette\Http\IResponse::S405_MethodNotAllowed);
		}

		return $request->getMethod();
	}
}
