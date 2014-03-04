<?php

require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

$app['debug'] = true;

// Application Registers
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'    => 'pdo_mysql',
        'host'      => 'localhost',
        'dbname'    => 'cancerdrugdb',
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
                'check_path' => '/console/login_check',
                'default_target_path' => '/console'
            ),
        	'logout'       => array(
                'logout_path' => '/console/logout',
                'target' => '/console/login'
            ),
        	'users'        => $app->share(function() use ($app){
                return $app['user.manager'];
            }),
    	),
    ),
));
$app['security.encoder.digest'] = $app->share(function ($app) {
    return new Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder(12);
});

$app->register(new Silex\Provider\RememberMeServiceProvider());
$app->register(new Silex\Provider\ServiceControllerServiceProvider()); 
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => array(
        __DIR__.'/../src/AntiC/Console/Views',
        __DIR__.'/../src/AntiC/LiveView/Views',
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
    //return $app['twig']->render($errorFile); // Temporarily commented out to display stack dump
});

/*******************************/
/* Application Logic Goes Here */

// User Authentication and Manager by UserServiceProvider
// This is a heavily modified version of the library: SimpleUser
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

// About Routes
$app->get('/console/about', "AntiC\Console\Controller\AboutController::indexAction");

// LiveView Routes
$app->get('/', "AntiC\LiveView\Controller\LiveViewController::indexAction");

// Install Path
$app->get('/install', function () use ($app){
    if (AntiC\User\Models\User::installModel($app)) {
        return "Database and Default User Created.";
    } else {
        return "Database exists already.";
    }
});

$app->run();
