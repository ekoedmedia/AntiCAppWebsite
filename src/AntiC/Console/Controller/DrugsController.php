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

    public function addAction(Request $request, Application $app)
    {        
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

    public function editAction(Request $request, Application $app)
    {
        // Query Database with ID and Return Drug Name and Information to Twig
        return $app['twig']->render('drugs/edit.html.twig', array(
                'drug_name' => $request->get('ID')
            ));
    }

}