<?php

namespace AntiC\Console\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class ProtocolsController
{
    /**
     * Shows the list of protocols
     *
     * @route /console/protocols
     * @param Application
     * @return twig render IF authenticated, redirect to login otherwise.
     */
    public function indexAction(Application $app)
    {
        if (!$app['user']) {
            return $app->redirect($app['url_generator']->generate('user.login'));
        }

        // Test Data
        $protocols = array(
            array("name" => 'Capcetiabine', "id" => '1', "enabled" => 0),
            array("name" => 'Test', "id" => '2', "enabled" => 1),
            array("name" => 'Welcome', "id" => '3', "enabled" => 1)
        );

        return $app['twig']->render('protocols/index.html.twig', array(
            'protocols' => $protocols,
        ));
    }

    /**
     * Shows and processes the add protocols page
     *
     * @route /console/protocols/add
     * @param Application
     * @param Request
     * @return twig render IF authenticated, redirect to login otherwise.
     */
    public function addAction(Application $app, Request $request)
    {
        if (!$app['user']) {
            return $app->redirect($app['url_generator']->generate('user.login'));
        }

        if ($request->isMethod('POST')) {
            /** 
             * @todo Processing of form and using API Functions from JB/Tanvir
             */
        }

        return $app['twig']->render('protocols/add.html.twig');
    }

    /**
     * Shows and processes the edit protocols page
     *
     * @route /console/protocols/{ID}
     * @param Application
     * @param Request
     * @return twig render IF authenticated, redirect to login otherwise.
     */
    public function editAction(Application $app, Request $request)
    {
        if (!$app['user']) {
            return $app->redirect($app['url_generator']->generate('user.login'));
        }

        if ($request->isMethod('POST')) {
            /** 
             * @todo Processing of form and using API Functions from JB/Tanvir
             */
        }

        // Query Database with ID and Return Protocol Name and Information to Twig
        return $app['twig']->render('protocols/edit.html.twig', array(
            'protocol_name' => $request->get('ID')
        ));
    }

}