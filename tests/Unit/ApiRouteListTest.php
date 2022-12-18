<?php

namespace Unit;

use ADT\ApiJsonRouter\ApiRouteList;

class ApiRouteListTest extends BaseUnit
{
	public function testAddRoutesBySpecification()
	{
		$apiRouteList = new ApiRouteList('Api');
		$apiRouteList->addRoutesBySpecification([
			'/api/devices>POST' => [
				'path' => '/api/devices/<uuid>/request',
				'presenter' => 'DeviceRequest',
				'method' => 'POST',
				'action' => 'create',
				'parameters' => [
					'uuid' => ['type' => 'string', 'requirement' => '.+'],
				],
				'body' => [
					'type' => 'object',
					'properties' => [
						'type' => ['type' => 'string'],
					],
					'required' => ['type']
				],
				'title' => 'Create a request',
				'description' => 'Create a request for a specific device.',
			]
		]);

		$this->tester->assertIsArray(
			$apiRouteList->match(
				$this->getRequest(
					['type' => 'test'],
					'/api/devices/83ca7b69-7dbc-4a5e-96c0-03d3f46ec5eb/request',
					'POST'
				)
			)
		);
	}
}
