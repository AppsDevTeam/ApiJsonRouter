<?php

namespace Unit\ApiRouterFormatMatch;

use Unit\BaseUnit;

class ArrayTypeTest extends BaseUnit {

	public function testBasicItems() {
		$route = $this->getRoute([
			'$schema' => 'https://json-schema.org/draft/2020-12/schema',
			'type' => 'object',
			'properties' => [
				'list' => [
					'type' => 'array',
					'items' => ['type' => 'string'],
				],
			],
		]);

		$request = $this->getRequest([
			'list' => [
				'aaa',
				'bbb'
			],
		]);

		$appRequest = $route->match($request);
		$this->assertNotError($appRequest);

		$this->assertJsonParametersCount(1, $appRequest);
		$this->assertRequestHasParamWithValue('_list', ['aaa', 'bbb'], $appRequest);
	}

	public function testContains() {
		$route = $this->getRoute([
			'$schema' => 'https://json-schema.org/draft/2020-12/schema',
			'type' => 'object',
			'properties' => [
				'list' => [
					'type' => 'array',
					'contains' => ['type' => 'string'],
				],
			],
		]);

		$request = $this->getRequest([
			'list' => [
				123,
				'aaa'
			],
		]);

		$appRequest = $route->match($request);
		$this->assertNotError($appRequest);

		$this->assertJsonParametersCount(1, $appRequest);
		$this->assertRequestHasParamWithValue('_list', [123, 'aaa'], $appRequest);
	}

}
