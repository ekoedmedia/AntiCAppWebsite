<?php

namespace AntiC\Console\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AntiC\User\Manager\UserManager;

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

        require_once 'api/get/listDrugs.php';
        $drugsList = getDrugList();

        $drugs = array();
        foreach ($drugsList as $drug) {
            $drugs[] = array(
                "commonName" => $drug['g_name'],
                "tradeName" => $drug['t_name'],
                "id" => $drug['g_name'],
                "enabled" => $drug['deleted'],
            );
        }

        return $app['twig']->render('drugs/index.html.twig', array(
            'drugs' => $drugs
        ));
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
        error_log($request->getMethod());
        if (!$app['user']) {
            return $app->redirect($app['url_generator']->generate('user.login'));
        }
    
        if ($request->isMethod('POST'))
        {
            //Parse out the data and print it to the error log for now.
            /* @todo Integration with API */
            error_log($request->get('g_name'));
            error_log($request->get('t_name'));
            error_log($request->get('risk'));
            foreach($request->get('classification') as $classification){
                error_log($classification);
            }
            foreach($request->get('contraindications') as $row){
                error_log($row);
            }
            $oncology = $request->get('oncology');
            $approved = $request->get('approved');
            for($i = 0; $i < count($oncology); $i++)
            {
                error_log($oncology[$i] . ' ' . $approved[$i]);
            }
            $precaution_name = $request->get('precaution_name');
            $precaution_note = $request->get('precaution_note');
            for($i = 0; $i < count($precaution_name); $i++)
            {
                error_log($precaution_name[$i] . ' ' . $precaution_note[$i]);
            }
            error_log($request->get('breastfeeding'));
            error_log($request->get('fertility'));
            error_log($request->get('metabolism'));
            error_log($request->get('uo_dose'));
            error_log($request->get('excretion'));
            error_log($request->get('available'));
            error_log($request->get('administration'));
            error_log($request->get('monitoring'));
            error_log($request->get('sideeffect_frequency'));
            $sideeffect_effect = $request->get('sideeffect_effect');
            $sideeffect_severe = $request->get('sideeffect_severe');
            for($i = 0; $i < count($sideeffect_effect); $i++)
            {
                error_log($sideeffect_effect[$i] . ' ' . $sideeffect_severe[$i]);
            }
            $interact_concomit = $request->get('interact_concomit');
            $interact_concomit_cyp = $request->get('interact_concomit_cyp');
            $interact_concomit_cyp_type = $request->get('interact_concomit_cyp_type');
            for($i = 0; $i < count($interact_concomit); $i++)
            {
                error_log($interact_concomit[$i] . ' ' . $interact_concomit_cyp[$i] 
                        . ' ' . $interact_concomit_cyp_type[$i]);
            }
            $interact_incr_this = $request->get('interact_incr_this');
            $interact_incr_this_cyp = $request->get('interact_incr_this_cyp');
            $interact_incr_this_cyp_type = $request->get('interact_incr_this_cyp_type');
            for($i = 0; $i < count($interact_incr_this); $i++)
            {
                error_log($interact_incr_this[$i] . ' ' . $interact_incr_this_cyp[$i] 
                        . ' ' . $interact_incr_this_cyp_type[$i]);
            }
            $interact_decr_this = $request->get('interact_decr_this');
            $interact_decr_this_cyp = $request->get('interact_decr_this_cyp');
            $interact_decr_this_cyp_type = $request->get('interact_decr_this_cyp_type');
            for($i = 0; $i < count($interact_decr_this); $i++)
            {
                error_log($interact_decr_this[$i] . ' ' . $interact_decr_this_cyp[$i]
                        . ' ' . $interact_decr_this_cyp_type[$i]);
            }
            $interact_both_this = $request->get('interact_both_this');
            $interact_both_this_cyp = $request->get('interact_both_this_cyp');
            $interact_both_this_cyp_type = $request->get('interact_both_this_cyp_type');
            for($i = 0; $i < count($interact_both_this); $i++)
            {
                error_log($interact_both_this[$i] . ' ' . $interact_both_this_cyp[$i] 
                        . ' ' . $interact_both_this_cyp_type[$i]);
            }
            $interact_incr = $request->get('interact_incr');
            $interact_incr_cyp = $request->get('interact_incr_cyp');
            $interact_incr_cyp_type = $request->get('interact_incr_cyp_type');
            for($i = 0; $i < count($interact_incr); $i++)
            {
                error_log($interact_incr[$i] . ' ' . $interact_incr_cyp[$i] 
                        . ' ' . $interact_incr_cyp_type[$i]);
            }
            $interact_decr = $request->get('interact_decr');
            $interact_decr_cyp = $request->get('interact_decr_cyp');
            $interact_decr_cyp_type = $request->get('interact_decr_cyp_type');
            for($i = 0; $i < count($interact_decr); $i++)
            {
                error_log($interact_decr[$i] . ' ' . $interact_decr_cyp[$i] 
                        . ' ' . $interact_decr_cyp_type[$i]);
            }
            $interact_both = $request->get('interact_both');
            $interact_both_cyp = $request->get('interact_both_cyp');
            $interact_both_cyp_type = $request->get('interact_both_cyp_type');
            for($i = 0; $i < count($interact_both); $i++)
            {
                error_log($interact_both[$i] . ' ' . $interact_both_cyp[$i] 
                        . ' ' . $interact_both_cyp_type[$i]);
            }
            error_log($request->get('anti-neoplastic'));
            foreach($request->get('interact_other') as $row){
                error_log($row);
            }
            $adjust_problem = $request->get('adjust_problem');
            $adjust_note = $request->get('adjust_note');
            $adjust_chart = $request->get('adjust_chart');
            for($i = 0; $i < count($adjust_problem); $i++)
            {
                error_log($adjust_problem[$i] . ' ' . $adjust_note[$i] 
                        . ' ' . $adjust_chart[$i]);
            }

            require 'api/get/listEnzymes.php';
            $enzymeList = getEnzymeList($dbhandle);

            return $app['twig']->render('drugs/add.html.twig', array(
                'enzymes' => $enzymeList
            ));
           # return $array;
        } else {
            require 'api/get/listEnzymes.php';
            $enzymeList = getEnzymeList($dbhandle);

            return $app['twig']->render('drugs/add.html.twig', array(
                'enzymes' => $enzymeList
            ));
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

        require 'api/dbConnect/connectStart.php';
        require 'api/get/getDrug.php';
        $drug = getDrug($request->get('ID'), $dbhandle);
        require 'api/get/listEnzymes.php';
        $enzymeList = getEnzymeList($dbhandle);

        if (is_numeric($drug["who_updated"])) {
            $userManager = new UserManager($app['db'], $app);
            $user = $userManager->getUser($drug["who_updated"]);
            $user = $user->getName();
        } else {
            $user = $drug["who_updated"];
        }

        // Query Database with ID and Return Drug Name and Information to Twig
        return $app['twig']->render('drugs/edit.html.twig', array(
            'drug' => $drug,
            'enzymes' => $enzymeList,
            'last_edited_by' => $user,
        ));
    }

    /**
     * Calls API to Show and Hide Drug
     *
     * @route /console/drugs/{ID}/showhide
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
            require 'api/delete/deleteDrug.php';
            $id = $app['user']->getName();
            $drugId = $request->get('ID');
            $showHide = $request->get('enabled');
            $response = showHideDrug($drugId, $id, $showHide);
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