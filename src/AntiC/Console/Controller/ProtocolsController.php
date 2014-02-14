<?php

namespace AntiC\Console\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class ProtocolsController
{
    public function indexAction(Application $app)
    {
        return $app['twig']->render('protocols/index.html.twig');
    }

}