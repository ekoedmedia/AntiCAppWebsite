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

    public function addAction(Application $app)
    {
        return $app['twig']->render('interactions/add.html.twig');
    }

    public function editAction(Request $request, Application $app)
    {
        // Query Database with ID and Return Interactions Name and Information to Twig
        return $app['twig']->render('interactions/edit.html.twig', array(
                'interaction_name' => $request->get('ID')
            ));
    }

}