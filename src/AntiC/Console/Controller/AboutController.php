<?php

namespace AntiC\Console\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class AboutController
{
    /**
     * Shows contents of about page on app that is update-able.
     * 
     * @route /console/about
     * @param Application
     * @return twig render IF authenticated, redirects to login otherwise.
     */
    public function indexAction(Application $app)
    {
        if (!$app['user']) {
            return $app->redirect($app['url_generator']->generate('user.login'));
        }

        return $app['twig']->render('about/index.html.twig');
    }

}