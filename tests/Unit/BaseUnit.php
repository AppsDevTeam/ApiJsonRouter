<?php

namespace Unit;

use ADT\ApiJsonRouter\ApiRoute;
use Codeception\AssertThrows;
use Codeception\Test\Unit;
use Nette\Http\Request;
use Nette\Http\UrlScript;
use UnitTester;

class BaseUnit extends Unit
{
	use AssertThrows;

	protected UnitTester $tester;

	public static function getRoute(?array $body)
	{
		return new ApiRoute('/api/item', 'Item', ['methods' => ['GET' => 'getItem']], $body);
	}

	public static function getRequest(?array $body, string $path = '/api/item', string $method = 'GET')
	{
		$url = new UrlScript('http://www.example.com' . $path, '/');
		$bodyJson = json_encode($body);
		return new Request($url, null, null, null, null, $method, null, null, function () use ($bodyJson) {return $bodyJson;});
	}

	protected function assertJsonParametersCount($expectedCount, $appRequest) {
		$appParameters = $this->getAppRequestParameters($appRequest);
		$jsonBodyParamsCount = 0;
		foreach ($appParameters as $param => $value) {
			if ($param[0] == '_' && $param[1] != '_') {
				$jsonBodyParamsCount++;
			}
		}

		$this->tester->assertEquals($expectedCount, $jsonBodyParamsCount);
	}

	protected function assertRequestHasParamWithValue($expectedParamName, $expectedParamValue, $appRequest) {
		$appParameters = $this->getAppRequestParameters($appRequest);
		$this->tester->assertArrayHasKey($expectedParamName, $appParameters);
		$this->tester->assertEquals($expectedParamValue, $appParameters[$expectedParamName]);
	}

	/**
	 * This method is for better merging into branches with old Nette
	 */
	protected function getAppRequestParameters($appRequest) {
		return $appRequest;
	}

	protected function getSpecification(): array
	{
		return [
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
		];
	}
}
