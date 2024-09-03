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
						if (isset($route['action'][$_method])) {
							$methods[$_method] = $route['action'][$_method];
						}
						else {
							$methods[$_method] = $route['action'];
						}
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
				'parameters' => $route['parameters'][$this->getMethod()] ?? $route['parameters'] ?? [],
			], $route['body'][$this->getMethod()] ?? $route['body'] ?? null);
		}

		return $this;
	}

	private function getMethod(): string
	{
		$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
		if (
			$method === 'POST'
			&& preg_match('#^[A-Z]+$#D', $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? '')
		) {
			$method = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];
		}
		return $method;
	}
}
