# QueryBuilder

### Usage whith connection estabilished before, in some other system's part.
```php
use Requtize\QueryBuilder\Connection;
use Requtize\QueryBuilder\QueryBuilder\QueryBuilderFactory;
use Requtize\QueryBuilder\ConnectionAdapters\PdoBridge;

// Somewhere in our application we have created PDO instance
$pdo = new PDO('dns...');

// Build Connection object with PdoBridge ad Adapter
$conn = new Connection(new PdoBridge($pdo));

// Pass this connection to Factory
$qbf = new QueryBuilderFactory($conn);

// Now we can use the factory as QueryBuilder - it creates QueryBuilder
// object every time we use some of method from QueryBuilder and returns it.
$result = $qbf->from('table')->where('cost', '>', 120)->all();
```

# @todo

- Table alias without using RAW, like:
```php
$qb->from('table')->tableAlias('table', 'alias')
```
- Column alias without using a RAW.

- Scopes - reusable predefined groups of statements.
```php
$scopes = new ScopesContainer;
$scopes->register('scrope-name', function($qb) {
    $qb->where('add_date', '<', 'NOW()');
});

$qbf->setScopes($scopes);

// ...

$qbf->from('table')->scope('scope-name')->all();
```
