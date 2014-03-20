<?php

namespace AntiC\Console\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

        require_once 'api/get/listEnzymes.php';
        $enzymeList = getEnzymeList();

        $enzymes = array();
        foreach ($enzymeList as $enzyme) {
            $enzymes[] = array(
                "name" => $enzyme['name'],
                "id" => $enzyme['name'],
                "enabled" => $enzyme['deleted'],
            );
        }

        return $app['twig']->render('interactions/index.html.twig', array(
            'interactions' => $enzymes,
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
     * @route /console/interactions/{ID}
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

        require_once 'api/get/getEnzyme.php';
        $enzyme = getEnzyme($request->get('ID'));

        if (is_numeric($enzyme["who_updated"])) {
            $userManager = new UserManager($app['db'], $app);
            $user = $userManager->getUser($enzyme["who_updated"]);
            $user = $user->getName();
        } else {
            $user = $enzyme["who_updated"];
        }

        // Query Database with ID and Return Interactions Name and Information to Twig
        return $app['twig']->render('interactions/edit.html.twig', array(
            'interaction' => $enzyme
        ));
    }

    /**
     * Calls API to Show and Hide Drug
     *
     * @route /console/interactions/{ID}/showhide
     * @param Application
     * @param Request
     * @return 1 or 0 depending on results of DB call, redirect to login otherwise
     */
    public function showHideAction(Application $app, Request $request)
    {
        if (!$app['user']) {
            return $app->redirect($app['url_generator']->generate('user.login'));
        }

        if ($request->isMethod('POST')) {
            require 'api/delete/deleteEnzyme.php';
            $id = $app['user']->getName();
            $enzymeId = $request->get('ID');
            $showHide = $request->get('enabled');
            $response = deleteEnzyme($enzymeId, $id, $showHide);
            if ($response) {
                $response = "1";
            } else {
                $response = "Error: Something went wrong";
            }
        } else {
            $response = "Error: Not a valid Request";
        }

        return new Response($response);
    }

}