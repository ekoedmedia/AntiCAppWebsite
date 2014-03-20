<?php

namespace AntiC\LiveView\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class LiveViewController
{
    /**
     * Controller for Homepage of Oralchemotherapy.ca
     *
     * @route /
     * @param Application
     * @return rendered twig template
     */
    public function indexAction(Application $app)
    {
        return $app['twig']->render('index.html.twig');
    }

    /**
     * @route /drugs
     */
    public function drugsListAction(Application $app, Request $request)
    {
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

        return $app['twig']->render('livedrugs/index.html.twig', array(
            'drugs' => $drugs
        ));
    }

    /**
     * @route /drugs/{ID}
     */
    public function viewDrugAction(Application $app, Request $request)
    {
        require_once 'api/get/getDrug.php';
        $drug = getDrug($request->get('ID'));
        return $app['twig']->render('livedrugs/view.html.twig', array(
                'drug' => $drug));
    }

    /**
     * @route /interactions
     */
    public function interactionsListAction(Application $app, Request $request)
    {
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

        return $app['twig']->render('liveinteractions/index.html.twig', array(
            'interactions' => $enzymes,
        ));
    }

    /**
     * @route /interactions/{ID}
     */
    public function viewInteractionAction(Application $app, Request $request)
    {
        require_once 'api/get/getEnzyme.php';
        $enzyme = getEnzyme($request->get('ID'));

        // Query Database with ID and Return Interactions Name and Information to Twig
        return $app['twig']->render('liveinteractions/view.html.twig', array(
            'interaction' => $enzyme
        ));
    }
    /**
     * @route /about
     */
    public function aboutAction(Application $app, Request $request)
    {
        return $app['twig']->render('liveabout/index.html.twig');
    }

}