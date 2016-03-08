<?php

use Illuminate\Container\Container as Container2;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\Eloquent\Model;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Container;
use Slim\Views\Twig;
use Slim\Views\TwigExtension;

require 'vendor/autoload.php';
$settings = array(
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'test',
    'username' => 'root',
    'password' => '',
    'collation' => 'utf8_general_ci',
    'prefix' => 'www_',
    'charset' => 'utf8'
);

// Bootstrap Eloquent ORM
$container = new Container2;
$connFactory = new ConnectionFactory($container);
$conn = $connFactory->make($settings);
$resolver = new ConnectionResolver();

$resolver->addConnection('default', $conn);
$resolver->setDefaultConnection('default');
Model::setConnectionResolver($resolver);

$slimContainer = new Container;

// Register component on container
$slimContainer['view'] = function ($c) {
    $view = new Twig('templates', [
        'debug' => true,
        'cache' => 'cache',
        'autoreload' => true,
    ]);
    $view->addExtension(new TwigExtension(
        $c['router'], $c['request']->getUri()
    ));

    return $view;
};
$app = new \Slim\App($slimContainer);

$app->config('debug', true);

/**
 * Homepage
 */
$app->get('/', function(ServerRequestInterface $request, $response, $args) {
    $articles = new \app\models\Goods();
    var_dump($articles);
    exit;
});

$app->get('/users', function(ServerRequestInterface $request, $response, $args) {
    return $this->view->render($response, 'users.html', [
            'items' => ['hiscaler', 'john', 'cat']
    ]);
});

$app->get('/hello/{name}', function(ServerRequestInterface $request, $response, $args) {
    return $this->view->render($response, 'profile.html', [
            'name' => $args['name'],
            'age' => $args['age']
    ]);
})->setName('hello');

$app->run();
