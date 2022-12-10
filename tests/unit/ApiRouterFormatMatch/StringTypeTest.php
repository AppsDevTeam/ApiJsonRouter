<?php

namespace Unit\ApiRouterFormatMatch;

use Unit\BaseUnit;

class StringTypeTest extends BaseUnit {

	public function testMinLength() {
		$route = $this->getRoute([
			'$schema' => 'https://json-schema.org/draft/2020-12/schema',
			'type' => 'object',
			'properties' => [
				'login' => [
					'type' => 'string',
					'minLength' => 5,
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

	public function testMinLengthFail() {
		$route = $this->getRoute([
			'$schema' => 'https://json-schema.org/draft/2020-12/schema',
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

	public function testMinLengthWrongSchema() {
		$route = $this->getRoute([
			'$schema' => 'https://json-schema.org/draft/2020-12/schema',
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

	public function testMaxLength() {
		$route = $this->getRoute([
			'$schema' => 'https://json-schema.org/draft/2020-12/schema',
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

	public function testMaxLengthFail() {
		$route = $this->getRoute([
			'$schema' => 'https://json-schema.org/draft/2020-12/schema',
			'type' => 'object',
			'properties' => [
				'login' => [
					'type' => 'string',
					'maxLength' => 5,
				],
			],
		]);

		$request = $this->getRequest([
			'login' => 'freediver',
		]);

		$appRequest = $route->match($request);
		$this->assertRequestHasParamWithValue('error', 'INVALID_FORMAT', $appRequest);
		$this->assertRequestHasParamWithValue('message', '{"/login":["Maximum string length is 5, found 9"]}', $appRequest);
	}

	public function testMaxLengthWrongSchema() {
		$route = $this->getRoute([
			'$schema' => 'https://json-schema.org/draft/2020-12/schema',
			'type' => 'object',
			'properties' => [
				'login' => [
					'type' => 'string',
					'maxLength' => 'xxx',
				],
			],
		]);

		$request = $this->getRequest([
			'login' => 'freediver',
		]);

		$appRequest = $route->match($request);
		$this->assertRequestHasParamWithValue('error', 'VERIFICATION_ERROR', $appRequest);
		$this->assertRequestHasParamWithValue('message', '{"/properties/login":["maxLength must be a non-negative integer"]}', $appRequest);
	}

	public function testPattern() {
		$route = $this->getRoute([
			'$schema' => 'https://json-schema.org/draft/2020-12/schema',
			'type' => 'object',
			'properties' => [
				'phone' => [
					'type' => 'string',
					'pattern' => '^(\([0-9]{3}\))?[0-9]{3}-[0-9]{4}$',
				],
			],
		]);

		$request = $this->getRequest([
			'phone' => '(888)555-1212',
		]);

		$appRequest = $route->match($request);
		$this->assertNotError($appRequest);

		$this->assertJsonParametersCount(1, $appRequest);
		$this->assertRequestHasParamWithValue('_phone', '(888)555-1212', $appRequest);
	}

	public function testPatternFail() {
		$route = $this->getRoute([
			'$schema' => 'https://json-schema.org/draft/2020-12/schema',
			'type' => 'object',
			'properties' => [
				'phone' => [
					'type' => 'string',
					'pattern' => '^(\([0-9]{3}\))?[0-9]{3}-[0-9]{4}$',
				],
			],
		]);

		$request = $this->getRequest([
			'phone' => '(888)555-1212 ext. 532',
		]);

		$appRequest = $route->match($request);
		$this->assertRequestHasParamWithValue('error', 'INVALID_FORMAT', $appRequest);
		$this->assertRequestHasParamWithValue('message', '{"/phone":["The string should match pattern: ^(\\\\([0-9]{3}\\\\))?[0-9]{3}-[0-9]{4}$"]}', $appRequest);
	}

}
