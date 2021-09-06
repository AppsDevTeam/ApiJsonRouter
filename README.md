# ApiJsonRouter

Creates a route that expects Json in request body, checks this body against Json schema supplied to the route and passes top level parameters to actions.

## Examples

### Rotes without generating documentation

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

### With generating documentation

```php
// Configure to redirect errors into error presenter
\ADT\ApiJsonRouter\ApiRouteFormat::setErrorPresenter('Error');
\ADT\ApiJsonRouter\ApiRouteFormat::setErrorAction('handleError');
\ADT\ApiJsonRouter\ApiRouteFormat::setThrowErrors(FALSE);
$apiModule = new RouteList('Api');

// Or to throw \ADT\ApiJsonRouter\FormatSchemaError or \ADT\ApiJsonRouter\FormatInputError
\ADT\ApiJsonRouter\ApiRouteFormat::setThrowErrors(TRUE);
$apiModule = new RouteList('Api');

\ADT\ApiJsonRouter\ApiRouteFormat::addRoutesBySpecification($apiModule, getApiRouteSpecification());

function getApiRouteSpecification() {
    return [
        '/api/item>GET' => [
            'path' => '/api/item',
            'presenter' => 'Item',
            'method' => 'GET',
            'action' => 'getItem',
            'body' => [
                '$schema' => 'https://json-schema.org/draft/2020-12/schema',
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string'],
                    'count' => ['type' => 'int'],
                ],
                'required' => ['name']
            ],
            'title' => 'Get item',
            'description' => 'Call to getting the item',
        ],
    ];
}

// to generate API documentation in Markdown format
$apiSpecification = getApiRouteSpecification();
$apiDocumentation = new \ADT\ApiJsonRouter\ApiDocumentation($apiSpecification);
$apiDocumentation->setTitleLevel(1); // for set another level of generated titles, default 2
$markdownApiDocumentation = $apiDocumentation->getDocumentation();

// e.g. you can use https://github.com/erusev/parsedown
$parsedown = new \Parsedown();
$htmlApiDocumentation = $parsedown->text($markdownApiDocumentation);
```


### Presenter
```php
class ItemPresenter extends Presenter {
    public function actionGetItem($_name, $_count = NULL) {
        $this->sendJson(['name' => $_name]);
    }
}
```

### Error presenter
```php
class ErrorPresenter extends Presenter {
    public function actionHandleError($error, int $code, $message) {
        $this->sendResponse(new JsonStatusResponse(['error' => $error, 'message' => $message], $code));
    }
}
```