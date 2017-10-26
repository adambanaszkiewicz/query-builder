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

## Installation

### Via composer.json

```json
{
    "require": {
        "requtize/query-builder": "dev-master"
    }
}
```

### Via Composer CLI

```cli
composer require requtize/query-builder:dev-master
```

## Query Builder Methods

- table($tableName) - Set table to operate on.
- where($column, $condition) ->
- where($column, $operator, $condition) - Defines where query section. If used multiple times, next ones will be joined as "AND".
- all() - Returns all founded records.
- first() - Returns only one, first record from result set.
- And so on...

# @todo

- Table alias without using RAW, like:
```php
$qb->from('table')->tableAlias('table', 'alias')
```
- Column alias without using a RAW.

- Scopes - reusable predefined groups of statements.
```php
$scopes = new ScopesContainer;
$scopes->register('scope-name', function($qb, $arg1, $arg2) {
    if($arg1)
        $qb->where('add_date', '<', 'NOW()');
    if($arg2)
        $qb->where('add_date', '>=', 'NOW()');
});

$qbf->setScopes($scopes);

// ...

$qbf->from('table')->scopeName('arg1', 'arg2')->all();
```

- Wheres groups.
```php
$qbf->from('table')
    ->where('column', 1)
    ->orWhere(function ($qb) {
        $qb
            ->where('col2', 2)
            ->where('col3', 3);
    })
    ->orWhere(function ($qb) {
        $qb
            ->where('col4', 4)
            ->where('col5', 5);
    });
```
Result:
```sql
SELECT *
FROM table
WHERE column = 1
OR (
    col2 = 2 AND col3 = 3
)
OR (
    col4 = 4 AND col5 = 5
)
```
