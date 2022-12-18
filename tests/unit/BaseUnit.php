<?php

namespace Unit;

use ADT\ApiJsonRouter\ApiRoute;
use Codeception\Test\Unit;
use Nette\Http\Request;
use Nette\Http\UrlScript;
use UnitTester;

class BaseUnit extends Unit
{
	protected UnitTester $tester;

	public static function getRoute(?array $body)
	{
		return new ApiRoute('/api/item', 'Item', ['methods' => ['GET' => 'getItem']], $body);
	}

	public static function getRequest(?array $body)
	{
		$url = new UrlScript('http://www.example.com/api/item', '/');
		$bodyJson = json_encode($body);
		return new Request($url, null, null, null, null, 'GET', null, null, function () use ($bodyJson) {return $bodyJson;});
	}

	protected function assertNotError($appRequest) {
		$appParameters = $this->getAppRequestParameters($appRequest);
		if (isset($appParameters['error'])) {
			$this->tester->assertArrayNotHasKey('error', $appParameters, 'JsonRouter error: ' . $appParameters['error'] . ' -> ' . $appParameters['message']);
		}
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

}
