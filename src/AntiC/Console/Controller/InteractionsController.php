<?php

namespace AntiC\Console\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class InteractionsController
{
    /**
     * Shows the list of interactions
     *
     * @route /console/interactions
     * @param Application
     * @return twig render IF authenticated, redirect to login otherwise.
     */
    public function indexAction(Application $app)
    {
        if (!$app['user']) {
            return $app->redirect($app['url_generator']->generate('user.login'));
        }

        // Test Data
        $interactions = array(
            array("name" => 'CYP 450 1A2', "id" => '1', "enabled" => 0),
            array("name" => 'Test', "id" => '2', "enabled" => 1),
            array("name" => 'Welcome', "id" => '3', "enabled" => 1)
        );

        return $app['twig']->render('interactions/index.html.twig', array(
            'interactions' => $interactions,
        ));
    }

    /**
     * Shows and processes add interactions form.
     *
     * @route /console/interactions/add
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
             * @todo using API Functions from JB/Tanvir
             */
            error_log("Substrates:");
            foreach($request->get('substrate') as $substrate)
            {
                error_log($substrate);
            }
            error_log("Inhibitors:");
            foreach($request->get('inhibitor') as $inhibitor)
            {
                error_log($inhibitor);
            }
            error_log("Inducers:");
            foreach($request->get('inducer') as $inducer)
            {
                error_log($inducer);
            }
        }

        return $app['twig']->render('interactions/add.html.twig');
    }

    /**
     * Shows and processes edit interactions form.
     *
     * @route /console/drugs/{ID}
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

        // Query Database with ID and Return Interactions Name and Information to Twig
        return $app['twig']->render('interactions/edit.html.twig', array(
            'interaction_name' => $request->get('ID')
        ));
    }

}