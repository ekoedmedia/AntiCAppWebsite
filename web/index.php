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
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => array(
        __DIR__.'/../src/AntiC/Console/Views',
        ),
));

// TEMPORARILY DISABLED JUST TO CREATE PAGES
// 
// $app->register(new Silex\Provider\SecurityServiceProvider(), array(
//     'security.firewalls' => array(
//     	'admin' => array(
//         	'pattern'      => '^/console',
//             'anonymous'    => true,
//         	'form'         => array('login_path' => '/', 'check_path' => '/login_check'),
//         	'logout'       => array('logout_path' => '/logout'),
//         	'users'        => $app->share(function() use ($app){
//                 return new AntiC\User\Provider\UserProvider($app['db']);
//             }),
//     	),
//     ),
//     'security.access_rules' => array(
//         array('^/console', 'ROLE_ADMIN'), 
//         array('^/', ''),
//     )
// ));

// Application Error Handler
$app->error(function (\Exception $e, $code) use ($app) {
    switch ($code) {
        case 404:
            $errorFile = 'error/404.html.twig';
            break;
        default:
            $errorFile = 'error/error.html.twig';
            break;
    }
    return $app['twig']->render($errorFile);
});

// Application Logic Goes Here
$app->get('/', "AntiC\Console\Controller\ConsoleController::indexAction");
$app->get('/console', "AntiC\Console\Controller\DrugsController::indexAction");
$app->match('/console/account', "AntiC\Console\Controller\ConsoleController::accountAction")->method('GET|POST');
$app->get('/console/protocols', "AntiC\Console\Controller\ProtocolsController::indexAction");
$app->get('/console/interactions', "AntiC\Console\Controller\InteractionsController::indexAction");
$app->get('/console/about', "AntiC\Console\Controller\AboutController::indexAction");

$app->run();
