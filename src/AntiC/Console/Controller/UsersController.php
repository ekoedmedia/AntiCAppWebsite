<?php

/**
 * @todo To be Removed
 */

namespace AntiC\Console\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class UsersController
{
    public function indexAction(Application $app)
    {
        return $app['twig']->render('users/index.html.twig');
    }

    public function addAction(Application $app)
    {
        return $app['twig']->render('users/add.html.twig');
    }

    public function editAction(Request $request, Application $app)
    {
        // Query Database with ID and Return User Name and Information to Twig
        return $app['twig']->render('users/edit.html.twig', array(
            'user_name' => $request->get('ID')
        ));
    }

}