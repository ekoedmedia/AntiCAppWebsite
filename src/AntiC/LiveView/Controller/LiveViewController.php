<?php

namespace AntiC\LiveView\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class LiveViewController
{
    /**
     * Controller for Homepage of Oralchemotherapy.ca
     *
     * @route /
     * @param Application
     * @return rendered twig template
     */
    public function indexAction(Application $app)
    {
        return $app['twig']->render('index.html.twig');
    }

    /**
     * @route /drugs
     */

    /**
     * @route /drugs/{ID}
     */

    /**
     * @route /interactions
     */

    /**
     * @route /interactions/{ID}
     */

}