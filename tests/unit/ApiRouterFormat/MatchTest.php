<?php

namespace Unit\ApiRouterFormat;

use Nette\Http\Request;
use Nette\Http\UrlScript;
use Unit\BaseUnit;

class MatchTest extends BaseUnit {

	public function testEmptySchema() {
		$route = $this->getRoute(null);

		$request = $this->getRequest([
			'login' => 'freediver',
		]);

		$appRequest = $route->match($request);
		$this->assertNotError($appRequest);
		$this->assertJsonParametersCount(0, $appRequest);
	}

	public function testInvalidBody() {
		$route = $this->getRoute([
			'type' => 'object',
			'properties' => [
				'login' => [
					'type' => 'string',
				],
			],
		]);

		$url = new UrlScript('http://www.example.com/api/item', '/');
		$bodyJson = 'wrong json body';
		$request = new Request($url, null, null, null, null, 'GET', null, null, function () use ($bodyJson) {return $bodyJson;});

		$appRequest = $route->match($request);
		$this->assertRequestHasParamWithValue('error', 'INVALID_FORMAT', $appRequest);
		$this->assertRequestHasParamWithValue('message', 'Input data are not valid JSON', $appRequest);
	}

	public function testBodyAgainstSchemaError() {
		$route = $this->getRoute([
			'type' => 'object',
			'properties' => [
				'login' => [
					'type' => 'string',
					'minLength' => 50,
				],
			],
		]);

		$request = $this->getRequest([
			'login' => 'freediver',
		]);

		$appRequest = $route->match($request);
		$this->assertRequestHasParamWithValue('error', 'INVALID_FORMAT', $appRequest);
		$this->assertRequestHasParamWithValue('message', '{"/login":["Minimum string length is 50, found 9"]}', $appRequest);
	}

	public function testInvalidSchemeError() {
		$route = $this->getRoute([
			'type' => 'object',
			'properties' => [
				'login' => [
					'type' => 'string',
					'minLength' => 'xxx',
				],
			],
		]);

		$request = $this->getRequest([
			'login' => 'freediver',
		]);

		$appRequest = $route->match($request);
		$this->assertRequestHasParamWithValue('error', 'VERIFICATION_ERROR', $appRequest);
		$this->assertRequestHasParamWithValue('message', '{"/properties/login":["minLength must be a non-negative integer"]}', $appRequest);
	}

	public function testUnresolvedError() {
		$route = $this->getRoute([
			'$ref' => 'http://example.com/{folder}/{file}.json',
			'$vars' => [
				'folder' => 'user',
				'file' => 'schema'
			]
		]);

		$request = $this->getRequest([
			'login' => 'freediver',
		]);

		$appRequest = $route->match($request);
		$this->assertRequestHasParamWithValue('error', 'VERIFICATION_ERROR', $appRequest);
		$this->assertRequestHasParamWithValue('message', '{"/":["Unresolved reference: http://example.com/user/schema.json"]}', $appRequest);
	}


	public function testOtherError() {
		$route = $this->getRoute([
			'$id' => 'https://example.com/schemas/address',
			'type' => 'object',
			'properties' => [
				'login' => [
					'$id' => 'https://example.com/schemas/address',
					'type' => 'string',
				],
			],
		]);

		$request = $this->getRequest([
			'login' => 'freediver',
		]);

		$appRequest = $route->match($request);
		$this->assertRequestHasParamWithValue('error', 'VERIFICATION_ERROR', $appRequest);
		$this->assertRequestHasParamWithValue('message', '{"/":"Duplicate schema id: https://example.com/schemas/address#"}', $appRequest);
	}

	public function testSuccess() {
		$route = $this->getRoute([
			'type' => 'object',
			'properties' => [
				'login' => [
					'type' => 'string',
					'maxLength' => 50,
				],
			],
		]);

		$request = $this->getRequest([
			'login' => 'freediver',
		]);

		$appRequest = $route->match($request);
		$this->assertNotError($appRequest);

		$this->assertJsonParametersCount(1, $appRequest);
		$this->assertRequestHasParamWithValue('_login', 'freediver', $appRequest);
	}

}
