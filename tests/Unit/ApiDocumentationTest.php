<?php

namespace Unit;

use ADT\ApiJsonRouter\ApiDocumentation;

class ApiDocumentationTest extends BaseUnit
{
	public function testGetDocumentation()
	{
		$apiDocumentation = new ApiDocumentation('Api', $this->getSpecification());

		$this->tester->assertNotEmpty($apiDocumentation->getDocumentation());
	}
}
