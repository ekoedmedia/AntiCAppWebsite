<?php

require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

$app['debug'] = true;

// Application Registers
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'    => 'pdo_mysql',
        'host'      => 'localhost',
        'dbname'    => 'antic',
        'user'      => 'root',
        'password'  => 'root',
        'charset'   => 'utf8',
    ),
));
 
$app->register(new Silex\Provider\SecurityServiceProvider(), array(
    'security.firewalls' => array(
    	'default' => array(
        	'pattern'      => '^/console',
            'anonymous'    => true,
        	'form'         => array(
                'login_path' => '/console/login', 
                'check_path' => '/console/login_check'
            ),
        	'logout'       => array('logout_path' => '/console/logout'),
        	'users'        => $app->share(function() use ($app){
                return $app['user.manager'];
            }),
    	),
    ),
));
$app->register(new Silex\Provider\RememberMeServiceProvider());
$app->register(new Silex\Provider\ServiceControllerServiceProvider()); 
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => array(
        __DIR__.'/../src/AntiC/Console/Views',
        ),
));

$app->register($u = new AntiC\User\Provider\UserServiceProvider());

// Application Error Handler
$app->error(function (\Exception $e, $code) use ($app) {
    switch ($code) {
        case 404:
            $errorFile = 'error/404.html.twig';
            break;
        case 403:
            $errorFile = 'error/403.html.twig';
            break;
        default:
            $errorFile = 'error/error.html.twig';
            break;
    }
    return $app['twig']->render($errorFile);
});

/*******************************/
/* Application Logic Goes Here */
$app->mount('/console', $u);

// Drugs Routes
$app->get('/console', "AntiC\Console\Controller\DrugsController::indexAction");
$app->match('/console/drugs/add', "AntiC\Console\Controller\DrugsController::addAction")->method('GET|POST');
$app->match('/console/drugs/{ID}', "AntiC\Console\Controller\DrugsController::editAction")->method('GET|POST');

// Protocols Routes
$app->get('/console/protocols', "AntiC\Console\Controller\ProtocolsController::indexAction");
$app->match('/console/protocols/add', "AntiC\Console\Controller\ProtocolsController::addAction")->method('GET|POST');
$app->match('/console/protocols/{ID}', "AntiC\Console\Controller\ProtocolsController::editAction")->method('GET|POST');

// Interactions Routes
$app->get('/console/interactions', "AntiC\Console\Controller\InteractionsController::indexAction");
$app->match('/console/interactions/add', "AntiC\Console\Controller\InteractionsController::addAction")->method('GET|POST');
$app->match('/console/interactions/{ID}', "AntiC\Console\Controller\InteractionsController::editAction")->method('GET|POST');

// User Management Routes
$app->get('/console/users', "AntiC\Console\Controller\UsersController::indexAction");
$app->match('/console/users/add', "AntiC\Console\Controller\UsersController::addAction")->method('GET|POST');
$app->match('/console/users/{ID}', "AntiC\Console\Controller\UsersController::editAction")->method('GET|POST');

// About Routes
$app->get('/console/about', "AntiC\Console\Controller\AboutController::indexAction");

$app->run();
