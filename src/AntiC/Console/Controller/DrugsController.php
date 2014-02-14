<?php

namespace AntiC\Console\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class DrugsController
{
    public function indexAction(Application $app)
    {
        return $app['twig']->render('drugs/index.html.twig');
    }

}