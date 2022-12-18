<?php

namespace Unit;

use ADT\ApiJsonRouter\Exception\FormatInputException;
use ADT\ApiJsonRouter\Exception\FormatSchemaException;
use Nette\Http\Request;
use Nette\Http\UrlScript;

class ApiRouteTest extends BaseUnit
{
	public function testEmptySchema()
	{
		$route = $this->getRoute(null);

		$request = $this->getRequest([
			'login' => 'freediver',
		]);

		$appRequest = $route->match($request);

		$this->assertJsonParametersCount(0, $appRequest);
	}

	public function testInvalidBody()
	{
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
		$request = new Request($url, method: 'GET', rawBodyCallback: function () use ($bodyJson) {
			return $bodyJson;
		});

		$this->assertThrowsWithMessage(
			FormatInputException::class,
			'Input data is not valid JSON.',
			function () use ($route, $request) {
				$route->match($request);
			}
		);
	}

	public function testBodyAgainstSchemaError()
	{
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

		$this->assertThrowsWithMessage(
			FormatInputException::class,
			'{"/login":["Minimum string length is 50, found 9"]}',
			function () use ($route, $request) {
				$route->match($request);
			}
		);
	}

	public function testInvalidSchemeError()
	{
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

		$this->assertThrowsWithMessage(
			FormatSchemaException::class,
			'{"/properties/login":["minLength must be a non-negative integer"]}',
			function () use ($route, $request) {
				$route->match($request);
			}
		);
	}

	public function testUnresolvedError()
	{
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

		$this->assertThrowsWithMessage(
			FormatSchemaException::class,
			'{"/":["Unresolved reference: http://example.com/user/schema.json"]}',
			function () use ($route, $request) {
				$route->match($request);
			}
		);
	}


	public function testOtherError()
	{
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

		$this->assertThrowsWithMessage(
			FormatSchemaException::class,
			'{"/":"Duplicate schema id: https://example.com/schemas/address#"}',
			function () use ($route, $request) {
				$route->match($request);
			}
		);
	}

	public function testSuccess()
	{
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

		$this->assertJsonParametersCount(1, $appRequest);
		$this->assertRequestHasParamWithValue('_login', 'freediver', $appRequest);
	}
}
