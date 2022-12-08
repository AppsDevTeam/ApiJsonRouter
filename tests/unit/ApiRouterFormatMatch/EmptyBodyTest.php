<?php

namespace Unit\ApiRouterFormatMatch;

use Unit\BaseUnit;

class EmptyBodyTest extends BaseUnit {

	public function test() {
		$route = $this->getRoute(null);
		$request = $this->getRequest(null);
		$appRequest = $route->match($request);
		$this->assertNotError($appRequest);
		$this->assertJsonParametersCount(0, $appRequest);
	}

}
