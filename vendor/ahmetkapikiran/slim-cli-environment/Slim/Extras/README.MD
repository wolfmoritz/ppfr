Run Slim Framework From Command-line
=========

How To use?
--------------

```php
$cliEnvironment = new \Slim\Extras\Environment();

$app = new \Slim\Slim(array(
    'environment' => $cliEnvironment
));
$app->get(
    '/hello/:name',
    function ($name) {
        echo "Hello, $name";
    }
);
$app->run();
```

Command-line
```sh
php test.php hello Ahmet
#Output
Hello, Ahmet
```