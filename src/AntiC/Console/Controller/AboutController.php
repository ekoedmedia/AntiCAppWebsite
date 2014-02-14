<?php

namespace AntiC\Console\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class AboutController
{
    public function indexAction(Application $app)
    {
        return $app['twig']->render('about/index.html.twig');
    }

}