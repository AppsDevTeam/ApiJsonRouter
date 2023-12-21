<?php

namespace ADT\ApiJsonRouter;

use Nette;
use Nette\Application\Routers\RouteList;

class ApiRouteList extends RouteList
{
	public function addRoutesBySpecification(array $apiRouteSpecification): self
	{
		foreach ($apiRouteSpecification as $route) {
			if (is_array($route['method'])) {
				if (isset($route['action'])) {
					foreach ($route['method'] as $_method) {
						$methods[$_method] = $route['action'];
					}

				} else {
					$methods = $route['method'];
				}
			} else {
				$methods = isset($route['action'])
					? [ $route['method'] => $route['action'] ]
					: [ $route['method'] ];
			}

			$this[] = new ApiRoute($route['path'], $route['presenter'], [
				'methods' => $methods,
				'parameters' => $route['parameters'] ?? [],
			], $route['body'] ?? null);
		}

		return $this;
	}
}
