<?php

declare(strict_types=1);

namespace Unit;

use ADT\ApiJsonRouter\ApiDocumentation;

final class ApiDocumentationTest extends BaseUnit
{
	public function testGetDocumentation(): void
	{
		$apiDocumentation = new ApiDocumentation('Api', $this->getSpecification());

		$this->tester->assertNotEmpty($apiDocumentation->getDocumentation());
	}
}
