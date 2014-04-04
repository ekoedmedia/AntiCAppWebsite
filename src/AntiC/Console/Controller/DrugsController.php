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
    
            $g_name = $request->get('g_name');
            $t_name = $request->get('t_name');
            $risk = $request->get('risk');
            $classification = $request->get('classification');
            $contraindications = $request->get('contraindications');
            $oncology = array();
            foreach ($request->get('oncology') as $value) {
                if (!isset($value['approved']))
                    $approved = "";
                else 
                    $approved = $value['approved'];
                $arrayOfValues = array("cancer_type" => $value["type"], "approved" => $approved);
                $oncology[] = $arrayOfValues;
            }

            $precaution = array();
            foreach ($request->get('precaution') as $value) {
                $arrayOfValues = array("name" => $value["label"], "note" => $value["note"]);
                $precaution[] = $arrayOfValues;
            }

            $pregnancy = $request->get('pregnancy');
            $oraldose = $request->get('uo_dose');
            $breastfeeding = $request->get('breastfeeding');
            $fertility = $request->get('fertility');
            $metabolism = $request->get('metabolism');
            $excretion = $request->get('excretion');
            $available = $request->get('available');
            $administration = $request->get('administration');
            $monitoring = $request->get('monitoring');

            $frequency = $request->get('sideeffect_frequency');
            $sideeffect = $request->get('sideeffect');

            $interact = array();
            foreach ($request->get('interact') as $value) {
                if (empty($value['type'])) continue;

                if (!empty($value['enzymetype'])) {
                    $arrayOfValues = array("interaction" => str_replace('drug', $g_name, $value['type']), "compound" => $value['enzyme'], "enzyme_effect_type" => $value['enzymetype']);
                    $interact[] = $arrayOfValues;
                } else {
                    $arrayOfValues = array("interaction" => str_replace('drug', $g_name, $value['type']), "compound" => $value['name']);
                    $interact[] = $arrayOfValues;
                }
            }

            foreach ($request->get('interactQT') as $value) {
                $arrayOfValues = array("interaction" => str_replace('drug', $g_name, $value['type']), "compound" => "QT-prolonging agents");
                $interact[] = $arrayOfValues;
            }

            foreach ($request->get('interact_other') as $value) {
                if (empty($value)) continue;
                $arrayOfValues = array("interaction" => "Other Interactions", "compound" => $value);
                $interact[] = $arrayOfValues;
            }

            $antineo = $request->get('anti_neoplastic');

            $adjustments = array();
            foreach ($request->get('adjustment') as $key => $value) {
                $arrayOfValues = array(
                    "problem" => $value['name'], 
                    "note" => $value['adjustment'], 
                    "chart_type" => $_FILES['adjustment']['type'][$key]['chart'], 
                    "chart" => $_FILES['adjustment']['tmp_name'][$key]['chart']
                );
                $ajustments[] = $arrayOfValues;
            }

            $last_revision = $request->get('last_revision');


            require 'api/dbConnect/connectStart.php';
            require 'api/put/putDrug.php';
            $drug = array(
                "g_name" => $g_name, 
                "t_name" => $t_name, 
                "risk" => $risk,              
                "classification" => $classification, 
                "pregnancy" => $pregnancy, 
                "breastfeeding" => $breastfeeding,
                "fertility" => $fertility, 
                "metabolism" => $metabolism, 
                "excretion" => $excretion, 
                "available" => $available, 
                "uo_dose" => $oraldose, 
                "last_revision" => $last_revision,
                "contraindications" => $contraindications,
                "monitoring" => $monitoring, 
                "administration" => $administration, 
                "anti_neoplastic" => $antineo, 
                "frequency" => $frequency,
                "sideEffects" => $sideeffect,
                "doseAdjusts" => $ajustments, 
                "drugInteracts" => $interact, 
                "oncUses" => $oncology,
                "precautions" => $precaution
            );
            if (putDrug($drug, $app['user']->getName(), $dbhandle)) {
                $app['session']->getFlashBag()->set('success', "Successfully added drug: ".$g_name);
                return $app->redirect($app['url_generator']->generate('console.drug.edit', array('ID' => $g_name)));
            } else {
                $app['session']->getFlashBag()->set('failure', "An error occured. Please try again.");
            }

            require 'api/get/listEnzymes.php';
            $enzymeList = getEnzymeList($dbhandle);

            return $app['twig']->render('drugs/add.html.twig', array(
                'enzymes' => $enzymeList
            ));
        } else {
            require 'api/get/listEnzymes.php';
            $enzymeList = getEnzymeList();

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


        $interactions = array();
        $qtprolonging = array();
        $othereffects = array();
        foreach ($drug["drugInteracts"] as $value) {
            if ($value["compound"] == "QT-prolonging agents") {
                $qtprolonging[] = $value["interaction"];
            } else if ($value["interaction"] == "Other Interactions") {
                $othereffects[] = $value["compound"];
            } else {
                $interactions[] = $value;
            }
        }


        $who_updated = $drug["who_updated"];

        // Query Database with ID and Return Drug Name and Information to Twig
        return $app['twig']->render('drugs/edit.html.twig', array(
            'drug' => $drug,
            'enzymes' => $enzymeList,
            'last_edited_by' => $who_updated,
            'interactions' => $interactions,
            'qtprolonging' => $qtprolonging,
            'othereffects' => $othereffects
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