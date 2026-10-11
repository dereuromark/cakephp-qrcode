# HTTP caching

The public [image action](../guide/endpoints) returns an ETag and Cache-Control header.
The default is `public, max-age=31536000, immutable`. The URL contains the payload and level, rather than a content hash.

The quoted ETag is SHA-1 of the input content, resolved level and requested extension. It is not a hash of the rendered image bytes or all library settings.
A matching `If-None-Match` returns 304 with an empty body before rendering. Weak validators, comma-separated validator lists and `*` are accepted.

Change the public action's cache policy in application configuration:

```php
\Cake\Core\Configure::write('QrCode.cacheControl', 'public, max-age=86400');
```

A nonempty string overrides the default. For personalized payloads, choose a private policy appropriate to your application, such as `private, no-store`.
The content remains visible in the query string and normal request logs regardless of cache policy.

The admin actions and inline data URI helper do not implement this HTTP caching behavior.
