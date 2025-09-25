# Gabbro HTTP Library – PSR-7 Inspired, Mutable by Design

This library provides an HTTP foundation inspired by the [PSR-7 HTTP Message Interface](https://www.php-fig.org/psr/psr-7/), but with some important differences in philosophy and implementation.

## Similarities to PSR-7

- **Request / Response Separation**  
  Requests and responses are represented as distinct objects, each with their own header and body handling.

- **Header Handling**  
  Headers are case-insensitive, support multiple values, and preserve insertion order.

- **Stream Abstraction**  
  Request and response bodies can be represented as streams, allowing efficient handling of large payloads without requiring the entire content in memory.

- **Interface Familiarity**  
  The method naming and conceptual model are close to PSR-7.

## Key Differences

- **Mutable Objects**  
  Unlike PSR-7, this library does **not enforce immutability**. Instead of cloning and returning new objects on every modification, you can directly update headers, cookies, etc. in place.

  ```php
  $response->addHeader("Content-Type", "application/json");
  $response->send();
  ```

- **Cookie Registry**  
  A built-in `CookieRegistry` class manages cookies with attribute flags (`Secure`, `HttpOnly`, `SameSite`, etc.), handling both request parsing and response emission.

- **Simplified Interface**  
  The API avoids unnecessary boilerplate (e.g. no requirement to return new instances for trivial changes), making it easier to use in everyday web application code.

## Why Mutable?

There simply is no point to the immutability. An object can always include clone or factory design features without the immutability. Also PSR-7's immutability goes only as far as to the body, where copying the entire body content was deemed to produce to much overhead at which point the remaining immutability makes even less sense.

This library is designed for projects where **clarity and efficiency** are preferred. Headers, cookies, and bodies can be updated directly on the same instance, while still keeping an interface that is clean, predictable, and close to PSR-7 in spirit.

## Example

```php
$request = new ServerRequest();
$registry = new CookieRegistry();
$registry->readFromRequest($request);

if (!$registry->isSet("SomeCookie")) {
    $registry->set("SomeCookie", "Some Value", 3600);
}

$response = $request->getResponse();
$registry->writeToResponse($response);

$response->send();
```

