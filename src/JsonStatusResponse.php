<?php

namespace ADT\ApiJsonRouter;

use Nette;
use Nette\Application\IResponse;

/**
 * Class JsonErrorResponse
 * @package App\Model\Routing
 * Nastaví status kód a přidá do těla daný payload (především s popisem chyby, která nastala)
 */
class JsonStatusResponse implements IResponse
{
	/** @var mixed */
	protected $payload;

	/** @var int */
	protected $code = 404;

	public function __construct($payload, int $code) {
		$this->payload = $payload;
		$this->code = $code;
	}

	/**
	 * Sends response to output with given status code.
	 */
	public function send(Nette\Http\IRequest $httpRequest, Nette\Http\IResponse $httpResponse): void {
		$httpResponse->setContentType('application/json', 'utf-8');
		$httpResponse->setCode($this->code);
		echo Nette\Utils\Json::encode($this->payload);
	}
}
