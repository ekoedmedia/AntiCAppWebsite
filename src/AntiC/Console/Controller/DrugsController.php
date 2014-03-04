<?php

namespace AntiC\Console\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class DrugsController
{
    /**
     * Shows list of Drugs. Initial screen on login.
     * 
     * @route /console
     * @param Application
     * @return twig render IF authenticated, redirects to login otherwise.
     */
    public function indexAction(Application $app)
    {
        if (!$app['user']) {
            return $app->redirect($app['url_generator']->generate('user.login'));
        }
        if (!$app['user']->getEnabled()) {
            $app['security']->setToken(null);
            $app['session']->getFlashBag()->set('notice', "Your account has been disabled. Please contact site support.");
            return $app->redirect($app['url_generator']->generate('user.login'));
        }

        return $app['twig']->render('drugs/index.html.twig', array(
            'username' => $app['user']->getName()
        ));
        return $app['twig']->render('drugs/index.html.twig');
    }

    /**
     * Shows and processes add drug form.
     *
     * @route /console/drugs/add
     * @param Application
     * @param Request
     * @return twig render IF authenticated, redirects to login otherwise.
     */
    public function addAction(Application $app, Request $request)
    {
        if (!$app['user']) {
            return $app->redirect($app['url_generator']->generate('user.login'));
        }
    
        if ($request->getMethod() == 'POST')
        {
          /*  $array = array(
                'g_name' => $request->get('common-name'),
                't_name' => $request->get('trade-name'),
                'risk' => $request->get('risk-level'),
            );
           */
            error_log($request->get('g_name'));
            error_log($request->get('t_name'));
            error_log($request->get('risk'));
            foreach($request->get('classification') as $classification){
                error_log($classification);
            }
            
            return $app['twig']->render('drugs/add.html.twig');
           # return $array;
        }
        else
        {
            return $app['twig']->render('drugs/add.html.twig');
        }
    }

    /**
     * Shows and processes edit drug form.
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

        // Query Database with ID and Return Drug Name and Information to Twig
        return $app['twig']->render('drugs/edit.html.twig', array(
            'drug_name' => $request->get('ID')
        ));
    }

}