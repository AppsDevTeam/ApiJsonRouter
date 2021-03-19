# ApiJsonRouter

Creates a route that expects Json in request body, checks this body against Json schema supplied to the route and passes top level parameters to actions.

Example route
```php
\ADT\ApiJsonRouter\ApiRouteFormat::setErrorPresenter('Error');
\ADT\ApiJsonRouter\ApiRouteFormat::setErrorAction('handleError');
$apiModule = new RouteList('Api');
$apiModule[] = new ApiRouteFormat('/api/item', 'Item', [
    '$schema' => 'https://json-schema.org/draft/2020-12/schema',
    'type' => 'object',
    'properties' => [
        'name' => ['type' => 'string'],
        'count' => ['type' => 'int'],
    ],
    'required' => ['name']
], [
    'methods' => ['GET' => 'getItem']
]);
```

Example presenter
```php
class ItemPresenter extends Presenter {
    public function actionGetItem($_name, $_count = NULL) {
        $this->sendJson(['name' => $_name]);
    }
}
```

Example error presenter
```php
class ErrorPresenter extends Presenter {
    public function actionHandleError($error, int $code) {
        $this->sendResponse(new JsonStatusResponse(['error' => $error], $code));
    }
}
```