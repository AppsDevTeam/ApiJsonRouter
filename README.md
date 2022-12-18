# ApiJsonRouter

Creates a route that expects Json in request body, checks this body against Json schema supplied to the route and passes top level parameters to actions.

ApiJsonRouter extends [Contributte api-router](https://github.com/contributte/api-router) thus see the documentation for full routes definition.

[Opis JSON Schema](https://github.com/opis/json-schema) is used for full Json Schema standard validation.

Standard "draft-2020-12" is used for validation as default.

## Installation

```bash
composer require adt/api-json-router
```

## Example

### Router

```php
final class RouterFactory
{
    // Prepare your route specification
    public static function getApiRouteSpecification(): array 
    {
       return [
          '/api/devices>POST' => [
             'path' => '/api/devices/<uuid>/request',
             'presenter' => 'DeviceRequest',
             'method' => 'POST',
             'action' => 'create',
             'parameters' => [
                'uuid' => ['type' => 'string'],
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

    // Create API router
    public static function createRouter(): \ADT\ApiJsonRouter\ApiRouteList
    {
        $apiRouter = new \ADT\ApiJsonRouter\ApiRouteList('Api');
        $apiRouter->addRoutesBySpecification(self::getApiRouteSpecification());
        return $apiRouter;
    }
}
```

### DeviceRequest presenter

```php
class DeviceRequestPresenter extends Presenter 
{
    public function actionCreate(string $uuid, string $_type) 
    {
        // Check if you have access to a specific device
        if ($this->deviceQuery->create()->byUuid($uuid)) {
            $this->sendJsonError(404);
        }
    
        // Create a device request
        $deviceRequest = new DeviceRequest($device, $_type)
        $this->em->persist();
        $this->em->flush();
        
        // Send a response
        $this->sendJson(['uuid' => $deviceRequest->getUuid(), 'type' => $device->getType()]);
    }
}
```

### Docs presenter

```php
class DocsPresenter extends Presenter
{
	public function actionDefault()
	{
		// Generate API documentation in Markdown format
		$apiDocumentation = new ADT\ApiJsonRouter\ApiDocumentation('API Docs', RouterFactory::getApiRouteSpecification());
		$markdownApiDocumentation = $apiDocumentation->getDocumentation();

		// Generate API documentation in HTML format
		// e.g. you can use https://github.com/erusev/parsedown
		die((new \Parsedown())->text($markdownApiDocumentation));
	}
}
```

### Error presenter

```php
class ErrorPresenter extends Presenter
{
	public function renderDefault(Exception $exception)
	{
		if ($exception instanceof ApiException) {
		    $this->sendJsonError($exception->getCode(), $exception->getMessage());
		}

		Debugger::log($exception, ILogger::EXCEPTION);

		$this->sendJsonError(500, 'There was an error processing your request. Please try again later.');
	}
}
```