# ApiJsonRouter

Creates a route that expects Json in request body, checks this body against Json schema supplied to the route and passes top level parameters to actions.

ApiJsonRouter extends [Contributte Api-router](https://github.com/contributte/api-router) thus see the documentation for full routes definition.

[Opis JSON Schema](https://github.com/opis/json-schema) is used for full Json Schema standard validation.

## Examples

### Rotes without generating documentation

```php
\ADT\ApiJsonRouter\ApiRouteFormat::setErrorPresenter('Error');
\ADT\ApiJsonRouter\ApiRouteFormat::setErrorAction('handleError');
$apiModule = new RouteList('Api');
$apiModule[] = new ApiRouteFormat('/api/baskets/<id>/add-product', 'Basket', [
    '$schema' => 'https://json-schema.org/draft/2020-12/schema',
    'type' => 'object',
    'properties' => [
        'productId' => ['type' => 'number'],
        'count' => ['type' => 'number'],
    ],
    'required' => ['productId']
], [
    'methods' => ['PATCH' => 'addProduct']
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
        '/api/baskets>PATCH' => [
            'path' => '/api/baskets/<id>/add-product',
            'presenter' => 'Basket',
            'method' => 'PATCH',
            'action' => 'addProduct',
            'body' => [
                '$schema' => 'https://json-schema.org/draft/2020-12/schema',
                'type' => 'object',
                'properties' => [
                    'productId' => ['type' => 'number'],
                    'count' => ['type' => 'number'],
                ],
                'required' => ['productId']
            ],
            'title' => 'Add product into basket',
            'description' => 'Add product into basket with specific count (default 1)',
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
class BasketPresenter extends Presenter {
    public function actionAddProduct(int $id, int $_productId, int $_count = 1) {
        // Add product into basket
        // ...
        
        // Send response
        $this->sendJson(['message' => 'Product was added']);
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