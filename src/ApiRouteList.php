<?php

namespace ADT\ApiJsonRouter;

use Nette;
use Nette\Application\Routers\RouteList;

class ApiRouteList extends RouteList
{
	public function addRoutesBySpecification(array $apiRouteSpecification): self
	{
		foreach ($apiRouteSpecification as $route) {
			$this[] = new ApiRoute($route['path'], $route['presenter'], [
				'methods' => isset($route['action'])
					? [ $route['method'] => $route['action'] ]
					: [ $route['method'] ],
				'parameters' => $route['parameters'] ?? [],
			], $route['body'] ?? null);
		}

		return $this;
	}
}
