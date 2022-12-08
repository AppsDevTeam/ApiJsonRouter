<?php

namespace Unit;

use ADT\ApiJsonRouter\ApiRouteFormat;
use Codeception\Test\Unit;
use Nette\Http\Request;
use Nette\Http\UrlScript;
use UnitTester;

class BaseUnit extends Unit {

	protected UnitTester $tester;

	public static function getRoute(?array $body) {
		return new ApiRouteFormat('/api/item', 'Item', $body, ['methods' => ['GET' => 'getItem']]);
	}

	public static function getRequest(?array $body) {
		$url = new UrlScript('http://www.example.com/api/item', '/');
		$bodyJson = json_encode($body);
		return new Request($url, null, null, null, null, 'GET', null, null, function () use ($bodyJson) {return $bodyJson;});
	}

	protected function assertJsonParametersCount($expectedCount, $appRequest) {
		$jsonBodyParamsCount = 0;
		foreach ($appRequest as $param => $value) {
			if ($param[0] == '_' && $param[1] != '_') {
				$jsonBodyParamsCount++;
			}
		}

		$this->tester->assertEquals($expectedCount, $jsonBodyParamsCount);
	}

	protected function assertRequestHasParamWithValue($expectedParamName, $expectedParamValue, $appRequest) {
		$this->tester->assertArrayHasKey($expectedParamName, $appRequest);
		$this->tester->assertEquals($expectedParamValue, $appRequest[$expectedParamName]);
	}

}
