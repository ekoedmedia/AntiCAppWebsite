<?php

namespace AntiC\Console\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class InteractionsController
{
    public function indexAction(Application $app)
    {
        return $app['twig']->render('interactions/index.html.twig');
    }

}