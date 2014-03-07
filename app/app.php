<?php

/**
 * Main Application File
 */

$app = require_once __DIR__.'/bootstrap.php';

// User System Provider Registration
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
$app->get('/console', "AntiC\Console\Controller\DrugsController::indexAction")->bind('console.drug');
$app->match('/console/drugs/add', "AntiC\Console\Controller\DrugsController::addAction")->method('GET|POST')->bind('console.drug.add');
$app->match('/console/drugs/{ID}', "AntiC\Console\Controller\DrugsController::editAction")->method('GET|POST')->bind('console.drug.edit');

// Protocols Routes
$app->get('/console/protocols', "AntiC\Console\Controller\ProtocolsController::indexAction")->bind('console.protocols');
$app->match('/console/protocols/add', "AntiC\Console\Controller\ProtocolsController::addAction")->method('GET|POST')->bind('console.protocols.add');
$app->match('/console/protocols/{ID}', "AntiC\Console\Controller\ProtocolsController::editAction")->method('GET|POST')->bind('console.protocols.edit');

// Interactions Routes
$app->get('/console/interactions', "AntiC\Console\Controller\InteractionsController::indexAction")->bind('console.interactions');
$app->match('/console/interactions/add', "AntiC\Console\Controller\InteractionsController::addAction")->method('GET|POST')->bind('console.interactions.add');
$app->match('/console/interactions/{ID}', "AntiC\Console\Controller\InteractionsController::editAction")->method('GET|POST')->bind('console.interactions.edit');

// About Routes
$app->get('/console/about', "AntiC\Console\Controller\AboutController::indexAction")->bind('console.about');

// LiveView Routes
$app->get('/', "AntiC\LiveView\Controller\LiveViewController::indexAction");
## Commented out until LiveView is ready for Sprint 4/5
// $app->get('/protocols', "AntiC\LiveView\Controller\LiveViewController::protocolsListAction");
// $app->get('/protocols/{ID}', "AntiC\LiveView\Controller\LiveViewController::viewProtocolAction");
// $app->get('/interactions', "AntiC\LiveView\Controller\LiveViewController::interactionsListAction");
// $app->get('/interactions/{ID}', "AntiC\LiveView\Controller\LiveViewController::viewInteractionAction");
// $app->get('/drugs', "AntiC\LiveView\Controller\LiveViewController::drugsListAction");
// $app->get('/drugs/{ID}', "AntiC\LiveView\Controller\LiveViewController::viewDrugAction");
// $app->get('/about', "AntiC\LiveView\Controller\LiveViewController::aboutAction");

// Install Path
$app->get('/install', function () use ($app){
    if (AntiC\User\Models\User::installModel($app)) {
        return "Database and Default User Created.";
    } else {
        return "Database exists already.";
    }
});

return $app;