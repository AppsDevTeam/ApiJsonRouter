<?php

namespace Unit\ApiRouterFormatMatch;

use Unit\BaseUnit;

class BasicTypesTest extends BaseUnit {

	public function test() {
		$route = $this->getRoute([
			'$schema' => 'https://json-schema.org/draft/2020-12/schema',
			'type' => 'object',
			'properties' => [
				'count' => ['type' => 'number'],
				'name' => ['type' => 'string'],
				'hasAccount' => ['type' => 'boolean'],
			],
			'required' => ['name']
		]);

		$request = $this->getRequest([
			'name' => 'Foo Baz',
			'count' => 123,
			'hasAccount' => false,
		]);

		$appRequest = $route->match($request);
		$this->assertNotError($appRequest);

		$this->assertJsonParametersCount(3, $appRequest);

		$this->assertRequestHasParamWithValue('_name', 'Foo Baz', $appRequest);
		$this->assertRequestHasParamWithValue('_count', 123, $appRequest);
		$this->assertRequestHasParamWithValue('_hasAccount', false, $appRequest);
	}

}
