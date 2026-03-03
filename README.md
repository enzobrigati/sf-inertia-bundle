# Inertia.js Symfony Bundle

Server-side adapter for **Symfony 7/8** and **Inertia.js v2**.

Inspired by the [Laravel adapter](https://github.com/inertiajs/inertia-laravel) and the [community Symfony bundle](https://github.com/SkipTheDragon/inertia-bundle).

---

## Installation

```bash
composer require enzobrigati/inertia-bundle
```

If the package is not yet on Packagist, add the repository to your `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/enzobrigati/inertia-bundle.git"
    }
  ]
}
```

The bundle is auto-registered via Symfony Flex. If not, add it manually:

```php
// config/bundles.php
return [
    // ...
    EnzoBrigati\InertiaBundle\InertiaBundle::class => ['all' => true],
];
```

---

## Configuration

```yaml
# config/packages/inertia.yaml
inertia:
  root_view: 'base.html.twig'           # Root Twig template (default: base.html.twig)
  component_resolver: 'inertia.component_resolver.default'
  component_locator: 'inertia.component_locator.default'

  exception:
    enabled: true                        # Handle exceptions for Inertia requests

  csrf:
    enabled: true                        # CSRF protection for Inertia requests
    token_name: 'X-Inertia-CSRF-TOKEN'
    header_name: 'X-XSRF-TOKEN'
    cookie:
      name: 'XSRF-TOKEN'
      expire: 0
      path: '/'
      domain: ~
      secure: false
      raw: false
      samesite: 'lax'

  modal:
    redirect_to_base_url: true           # Redirect modal form submissions to base URL
```

---

## Root template

Create a Twig template that serves as the HTML shell for your Inertia app:

```twig
{# templates/base.html.twig #}
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{ inertiaHead(page) }}
    {% block stylesheets %}{% endblock %}
</head>
<body>
    {{ inertia(page) }}
    {% block javascripts %}{% endblock %}
</body>
</html>
```

The `inertia(page)` function renders the `<div id="app" data-page="...">` container. The `page` variable is automatically passed by the bundle.

---

## Basic usage

### Rendering pages

Inject `InertiaInterface` and call `render()`:

```php
use EnzoBrigati\InertiaBundle\Service\InertiaInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserController
{
    #[Route('/users', name: 'users.index')]
    public function index(InertiaInterface $inertia): Response
    {
        return $inertia->render('Users/Index', [
            'users' => $userRepository->findAll(),
        ]);
    }
}
```

For Inertia requests (XHR with `X-Inertia` header), the bundle returns a JSON response. For initial page loads, it renders the root Twig template with the page data.

### Sharing data

Share props globally across all Inertia responses (useful for auth user, flash messages, etc.):

```php
// In a subscriber, middleware, or controller
$inertia->share('auth', [
    'user' => $security->getUser(),
]);
```

### View data

Pass data to the root Twig template (not to the frontend component):

```php
$inertia->viewData('title', 'My Page Title');
```

### External redirects

Force a full page redirect (outside of Inertia's SPA navigation):

```php
return $inertia->location('/external-page');
```

---

## Props

The bundle provides several prop types for controlling how data is sent to the frontend.

```php
use EnzoBrigati\InertiaBundle\InertiaProp;

return $inertia->render('Dashboard', [
    // Always included, even in partial reloads
    'flash' => InertiaProp::always($flashMessages),

    // Lazy-evaluated via callback
    'stats' => InertiaProp::callback(fn () => $statsService->compute()),

    // Only included when explicitly requested via partial reload
    'filters' => InertiaProp::optional(fn () => $filterService->getAll()),

    // Loaded asynchronously after initial render
    'chart' => InertiaProp::defer(fn () => $chartService->getData(), 'charts'),

    // Merged with existing client-side data (for infinite scroll / pagination)
    'items' => InertiaProp::merge($paginator->getItems()),

    // Deep merge for nested structures
    'settings' => InertiaProp::deepMerge($settingsArray),
]);
```

| Type | Behavior |
|------|----------|
| `basic` | Default. Sent on every full request. |
| `callback` | Lazy-evaluated. The closure runs only when the prop is resolved. |
| `always` | Always included, even in partial reload requests. |
| `optional` | Excluded from full requests. Only included when explicitly requested in partial reloads. |
| `defer` | Loaded asynchronously. Excluded from initial render, fetched by the frontend in a separate request. Supports grouping. |
| `merge` | Merged with existing client-side data during partial reloads. Useful for infinite scroll. |
| `deepMerge` | Like `merge`, but deeply merges nested structures. |

---

## Asset versioning

Set a version identifier to trigger full page reloads when assets change:

```php
$inertia->version(md5_file('build/manifest.json'));
```

When the frontend sends a request with an outdated version, the bundle responds with `409 Conflict` and an `X-Inertia-Location` header, causing the frontend to do a full page reload.

---

## Validation errors

The bundle ships with a `ValidationFailedResponseFactory` that automatically catches `ValidationFailedException`, flashes the errors, and redirects back. Errors are available on the frontend via the `errors` prop.

```php
// Errors are automatically shared via the errors prop provider
// In your Vue/React component:
// props.errors.name → "Name is required"
```

To use error bags (multiple forms on the same page), send the `X-Inertia-Error-Bag` header from the frontend.

---

## CSRF protection

Enabled by default. The bundle:

1. Sets an `XSRF-TOKEN` cookie on every Inertia response
2. Expects the token back in the `X-XSRF-TOKEN` header on subsequent requests
3. Validates the token via Symfony's `CsrfTokenManager`

Configure Axios to send the cookie automatically:

```js
axios.defaults.xsrfCookieName = 'XSRF-TOKEN';
axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';
```

---

## Component resolvers and locators

### Component resolver

Transforms the component name passed to `render()` into a path. Two built-in resolvers:

- **`DefaultComponentResolver`** (default) — Returns the component name as-is.
- **`ControllerNameComponentResolver`** — Generates a path based on the controller class name.

### Component locator

Validates that the component file exists. Two built-in locators:

- **`DefaultComponentLocator`** (default) — Always returns `true` (no validation).
- **`DirectoryComponentLocator`** — Checks a directory on disk for the component file.

```yaml
# Example: validate that .vue files exist
services:
  app.inertia.component_locator:
    class: EnzoBrigati\InertiaBundle\ComponentLocator\DirectoryComponentLocator
    arguments:
      $filesystem: '@filesystem'
      $directory: '%kernel.project_dir%/assets/Pages'
      $extension: '.vue'

inertia:
  component_locator: 'app.inertia.component_locator'
```

---

## Custom response factories

Handle custom exceptions in Inertia requests by implementing `InertiaResponseFactoryInterface`:

```php
use EnzoBrigati\InertiaBundle\ResponseFactory\InertiaResponseFactoryInterface;

class AccessDeniedResponseFactory implements InertiaResponseFactoryInterface
{
    public static function getPriority(): int
    {
        return 0;
    }

    public function isHandling(Request $request, Throwable $throwable): ?Throwable
    {
        return $throwable instanceof AccessDeniedException ? $throwable : null;
    }

    public function handle(Request $request, Throwable $throwable): Response
    {
        return new RedirectResponse('/login');
    }
}
```

The class is auto-tagged via `InertiaResponseFactoryInterface` autoconfiguration.

---

## Custom prop providers

Share props globally by implementing `InertiaPropProviderInterface`:

```php
use EnzoBrigati\InertiaBundle\PropProvider\InertiaPropProviderInterface;

class AppPropProvider implements InertiaPropProviderInterface
{
    public function getInertiaProps(InertiaHeaders $headers, InertiaFlash $flash): array
    {
        return [
            'appName' => InertiaProp::always('My App'),
        ];
    }
}
```

Auto-tagged via autoconfiguration. No manual service registration needed.

---

## Modals (InertiaUI Modal)

The bundle includes built-in support for [InertiaUI Modal](https://inertiaui.com/inertia-modal) — stackable modals and slideovers for Inertia.js. The frontend packages (Vue/React) work unchanged; this bundle provides the server-side handling.

### How it works

When the frontend opens a modal, it sends a request with special headers (`X-InertiaUI-Modal`, `X-InertiaUI-Modal-Base-Url`, `X-InertiaUI-Modal-Use-Router`). The bundle:

1. Builds the modal page data
2. Dispatches an internal sub-request to the base URL (the "background" page)
3. Injects the modal data as a `_inertiaui_modal` shared prop
4. Returns the base page response with the modal data embedded

This way, the frontend receives both the background page and the modal content in a single response.

### Basic usage

```php
use EnzoBrigati\InertiaBundle\Service\InertiaInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserController
{
    #[Route('/users', name: 'users.index')]
    public function index(InertiaInterface $inertia): Response
    {
        return $inertia->render('Users/Index', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/users/{id}/edit', name: 'users.edit')]
    public function edit(Request $request, User $user, InertiaInterface $inertia): Response
    {
        return $inertia->modal('Users/Edit', [
            'user' => $user,
        ])->baseUrl('/users')->toResponse($request);
    }
}
```

### Setting the base URL

The base URL is the "background" page behind the modal. The bundle resolves it in this priority order:

1. `X-InertiaUI-Modal-Base-Url` header (sent by the frontend)
2. `Referer` header
3. Explicit `->baseUrl()` value

If all candidates match the current request path, `null` is returned (prevents infinite loops), and the modal data is returned standalone.

```php
// Explicit base URL
$inertia->modal('Users/Edit', ['user' => $user])
    ->baseUrl('/users')
    ->toResponse($request);
```

### Response scenarios

| Scenario | What happens |
|----------|-------------|
| **XHR + router enabled + base URL** | Sub-request to base URL, modal data embedded in `_inertiaui_modal` prop, URL spoofed to modal URL |
| **XHR + router disabled** | Modal data returned as standalone JSON with `meta` key |
| **Initial page load (no X-Inertia)** | Sub-request to base URL, full HTML response with modal data embedded in `data-page` |
| **Redirect after form submission** | `ModalRedirectSubscriber` replaces redirect target with base URL |

### Redirect handling

When a modal form is submitted and the controller returns a redirect, the `ModalRedirectSubscriber` automatically replaces the redirect target with the modal's base URL. This ensures the user returns to the background page after the modal closes.

This behavior is enabled by default. Disable it in configuration:

```yaml
inertia:
  modal:
    redirect_to_base_url: false
```

### Modal config and visit objects

For frontend integration, the bundle provides value objects that match the InertiaUI Modal JavaScript API:

```php
use EnzoBrigati\InertiaBundle\Modal\ModalConfig;
use EnzoBrigati\InertiaBundle\Modal\ModalVisit;

// Modal configuration (passed to the frontend)
$config = ModalConfig::new()
    ->slideover()
    ->closeButton()
    ->closeExplicitly()
    ->maxWidth('2xl')          // sm, md, lg, xl, 2xl, 3xl, 4xl, 5xl, 6xl, 7xl
    ->paddingClasses('p-6')
    ->panelClasses('bg-white rounded-lg')
    ->center();                // bottom, center, left, right, top

// Modal visit configuration
$visit = ModalVisit::new()
    ->method('POST')
    ->navigate()
    ->data(['key' => 'value'])
    ->headers(['X-Custom' => 'header'])
    ->config($config)                                        // or a callable
    ->queryStringArrayFormat(QueryStringArrayFormat::Brackets);

// Both serialize to arrays for JSON responses
$config->toArray();
$visit->toArray();
```

---

## Testing

```bash
composer test
```

Runs the PHPUnit test suite (220 tests).

```bash
composer fix:phpstan
```

Runs PHPStan static analysis.

---

## License

MIT
