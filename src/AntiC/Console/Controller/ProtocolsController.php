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

    public function addAction(Application $app)
    {
        return $app['twig']->render('protocols/add.html.twig');
    }

    public function editAction(Request $request, Application $app)
    {
        // Query Database with ID and Return Protocol Name and Information to Twig
        return $app['twig']->render('protocols/edit.html.twig', array(
                'protocol_name' => $request->get('ID')
            ));
    }

}