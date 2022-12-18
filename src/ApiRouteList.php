<?php

namespace ADT\ApiJsonRouter;

use ADT\ApiJsonRouter\Exception\ClientException;
use Nette;
use Nette\Application\Routers\RouteList;

class ApiRouteList extends RouteList
{
	/**
	 * @throws ClientException
	 */
	public function match(Nette\Http\IRequest $httpRequest): ?array
	{
		$match = parent::match($httpRequest);

		if ($match === null) {
			throw new ClientException('The requested endpoint was not found.', 400);
		}
		
		return $match;
	}

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
