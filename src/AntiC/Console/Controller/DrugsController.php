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

    public function addAction(Application $app)
    {
        return $app['twig']->render('drugs/add.html.twig');
    }

    public function editAction(Request $request, Application $app)
    {
        // Query Database with ID and Return Drug Name and Information to Twig
        return $app['twig']->render('drugs/edit.html.twig', array(
                'drug_name' => $request->get('ID')
            ));
    }

}