# QueryBuilder

Query Builder is a fast, simple, methods-chaining, dependency-free library to create SQL Queries simple and fast to write, extend and manage. Supports databases which are supported by PDO. Can be also used as Database Abstraction Layer.

## Installation - via composer.json
```json
"requtize/query-builder": "dev-master"
```

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

# Query Builder Methods

### Table selection
```php
// Set table to operate on.
$qbf->table('table');
$qbf->table('table', 'next-table');
$qbf->table('table', 'next-table', 'and-another');
$qbf->table([ 'table', 'next-table', 'and-another' ]);
// Alias to table() method.
$qbf->from(...);
```

### Selects
```php
// Selects
$qbf->select('*');
$qbf->select('column');
$qbf->select('column1', 'column2', 'column3');
$qbf->select([ 'column1', 'column2', 'column3' ]);
// Select DISTINCT
$qbf->selectDistinct('*');
$qbf->selectDistinct('column');
$qbf->selectDistinct('column1', 'column2', 'column3');
$qbf->selectDistinct([ 'column1', 'column2', 'column3' ]);
```
### Wheres
If method not starts with "or*, multiple calls will join it as "AND".
```php
$qbf->where('name', 'Adam')
    ->where('name', '=', 'Adam')
    ->orWhere('name', 'Adam')
    ->orWhere('name', '=', 'Adam')
    ->whereNot('name', 'Adam')
    ->whereNot('name', '=', 'Adam')
    ->orWhereNot('name', 'Adam')
    ->orWhereNot('name', '=', 'Adam')
    ->whereIn('name' [ 'Adam', 'Eva' ])
    ->whereNotIn('name' [ 'Adam', 'Eva' ])
    ->orWhereIn('name' [ 'Adam', 'Eva' ])
    ->orWhereNotIn('name' [ 'Adam', 'Eva' ])
    ->whereBetween('age', 10, 20)
    ->orWhereBetween('age', 10, 20)
    ->whereNull('sex')
    ->whereNotNull('sex')
    ->orWhereNull('sex')
    ->orWhereNotNull('sex');
```
Wheres (all methods above) can also take Closure as first argument. This can make sub-criterias. Sub-criterias will be joined to main query using joined from used method. As argument of the anonymous function is object NestedCriteria that allows You to use all the where() methods above.
```php
$qbf->where(function ($query) {
    $query->where('id', 1)
        ->whereNot('status', 2);
});
```
Wheres can also take as first argument RAW query section in two ways. First - all full criteria (column name, operator and value), or only columns/table-column value n first parameter, and value as second.
```php
$qbf->where($qbf->raw('name'), 'Adam');
$qbf->where($qbf->raw('name = "Adam"'));
```

### Joins
```php
// Simple INNER JOIN
$qbf->join('table', 'name', '=', 'Adam', 'inner')
    // INNER JOIN as Closure with advanced ON criteria
    ->join('table', function ($join) {
        $join->on('name', 'Adam')
             ->on('name', '=', 'Adam')
             ->orOn('name', 'Adam')
             ->orOn('name', '=', 'Adam');
    })
    ->leftJoin('table', 'name', '=', 'Adam')
    ->leftJoin('table', function ($join) {
        // ...
    })
    ->rightJoin('table', 'name', '=', 'Adam')
    ->rightJoin('table', function ($join) {
        // ...
    })
    ->innerJoin('table', 'name', '=', 'Adam')
    ->innerJoin('table', function ($join) {
        // ...
    });
```

### Resutls set
```php
$qbf->all(); // Returns all results.
$qbf->first(); // Returns first result.
$qbf->count($column);
$qbf->max($column);
$qbf->min($column);
$qbf->sum($column);
$qbf->avg($column);
```

### Inserts
```php
$qbf->from('table')->insert([ 'name' => 'Adam' ]);
$qbf->insert([ 'name' => 'Adam' ], 'table');
$qbf->from('table')->insertIgnore([ 'name' => 'Adam' ]);
$qbf->insertIgnore([ 'name' => 'Adam' ], 'table');
$qbf->from('table')->replace([ 'id' => 12, 'name' => 'Adam' ]);
$qbf->replace([ 'id' => 12, 'name' => 'Adam' ], 'table');
```

If insert call operate on table with AUTO_INCREMENT column, method will return inserted ID of row. Also You can use another method (after call `insert()` method) to do the same thing:

```php
$qbf->getLastId();

```

### Update
```php
$qbf
    ->from('table')
    ->where('name', 'John')
    ->update([ 'name' => 'Adam' ]);
$qbf
    ->where('name', 'John')
    ->update([ 'name' => 'Adam' ], 'table');
$qbf
    ->from('table')
    ->where('name', 'John')
    ->updateOrInsert([ 'name' => 'Adam' ]);
$qbf
    ->where('name', 'John')
    ->updateOrInsert([ 'name' => 'Adam' ], 'table');
```

### Delete
```php
$qbf
    ->where('name', 'Adam')
    ->delete('table');
$qbf
    ->from('table')
    ->where('name', 'Adam')
    ->delete();
```

### RAW values
In most of all methods and parameters You can pass the RAW value as argument. To do this You have to only use raw() method and pass the result of this method to any argument You want.
```php
$qbf->where($qbf->raw('name'), $qbf->raw('Adam'));
$qbf->select($qbf->raw('name'));
$qbf->table($qbf->raw('table'));
// ...and so on...
```

### Raw query
```php
// SELECT Query.
$rows = $qbf->query('SELECT * FROM table WHERE name = :name', [
    ':name' => 'Adam'
]);
// UPDATE, INSERT, DELETE, etc.
$affectedCount = $qbf->exec('UPDATE table SET id = :id WHERE name = :name', [
    ':id'   => 15,
    ':name' => 'Adam'
]);
```

### Sub-Queries and Nested Queries
In some cases You might need to create SubQuery to provide some special functionality. Use the *subQuery()* method to do this thing. Examples of usage:
```php
$subQuery = $qbf
    ->select('name')
    ->from('persons')
    ->where('id', 15);

$query = $qbf
    ->select('table.*')
    ->from('table')
    ->select($qbf->subQuery($subQuery, 'alias1'));

$nestedQuery = $qbf
    ->select('*')
    ->from($qbf->subQuery($query, 'alias2'));
```
Generated query by Query Builder.
```sql
SELECT *
FROM (
    SELECT `table`.*,
    (
        SELECT `name`
        FROM `persons`
        WHERE `id` = 15
    ) AS alias1
    FROM `table`
) AS alias2
```

## Get compiled Query
If You want to preview a query, before execution, or for debugging intensions, You may want use *getQuery()* method, which returns *Query* object, with compiled query (with placeholders), array of bindings and the *PDO* instance. This object contains all data of current *Query Builder* instance.
```php
$qbf->getQuery($type = 'select', array $parameters = []);
```

## #API
```php
// Returns passed PDO object.
$qbf->getPdo();

// Returns all Query Segments created in this instance of Query Builder
$qbf->getQuerySegments();

// Or only selected segment
$qbf->getQuerySegment('where');

// Sets and gets EventDispatcher
$qbf->getEventDispatcher();
$qbf->setEventDispatcher(Requtize\QueryBuilder\Event\EventDispatcherInterface $eventDispatcher);

// Sets FetchMode for PDO. IF PDOs Fetch Mode requires many arguments, just pass this to this method as next arguments.
$qbf->setFetchMode($mode...);
// Sets Fetch mode to Object.
$qbf->asObject($className, $classConstructorArgs = []);

// Gets and sets DB connection object.
$qbf->setConnection(Requtize\QueryBuilder\Connection $connection);
$qbf->getConnection();

// Gets Db schema
$qbf->getSchema();

// Create new QueryBuilder instance.
$qbf->newQuery(Requtize\QueryBuilder\Connection $connection = null);

// Forks query. Copies all Query Segments, settings to new object and returns new object. Allows to create new Query, but with earlier defined criterias.
$qbf->forkQuery();
```
# @todo

- Where LIKE

```PHP
$qb->like('column', 'value');
// WHERE column LIKE '%value%'
$qb->like('column', 'value', 'left|start');
// WHERE column LIKE '%value'
$qb->like('column', 'value', 'right|end');
// WHERE column LIKE 'value%'
```



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

- Chunks of returned rows
Must work only when there's no defined LIMIT statement!
```php
$qb->where('column', 1)->chunk(30, function (array $chunk) {
    foreach($chunk as $row)
    {
        // Do something with $row...
    }
});
```

- Inserting data as aggregated collection

```php
$qb->insert([
    [ 'id' => 1, 'col' => 'val' ],
    [ 'id' => 1, 'col' => 'val' ],
    [ 'id' => 1, 'col' => 'val' ],
    [ 'id' => 1, 'col' => 'val' ]
], true, 'table');
```

- Fulltext search
