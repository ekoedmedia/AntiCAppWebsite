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
        return $app['twig']->render('livedrugs/index.html.twig');
    }

    /**
     * @route /drugs/{ID}
     */
    public function viewDrugAction(Application $app, Request $request)
    {
        #TODO DB integration
        #require_once 'api/get/getDrug.php';
        #$drug = getDrug($request->get('ID'));
        return $app['twig']->render('livedrugs/view.html.twig', array(
                'drug' => $request->get('ID')));
    }

    /**
     * @route /interactions
     */
    public function interactionsListAction(Application $app, Request $request)
    {
        //TODO DB integration
//        require_once 'api/get/listEnzymes.php';
//        $enzymeList = getEnzymeList();
//
//        $enzymes = array();
//        foreach ($enzymeList as $enzyme) {
//            $enzymes[] = array(
//                "name" => $enzyme['name'],
//                "id" => $enzyme['name'],
//                "enabled" => $enzyme['deleted'],
//            );
//        }
//
//        return $app['twig']->render('interactions/index.html.twig', array(
//            'interactions' => $enzymes,
//        ));
        
        $enzymes = array();
        $enzymes[0] = array(
            "name" => "TestName",
            "id" => "TestName",
            "enabled" => true,
        );
        $enzymes[1] = array(
            "name" => "TestName2",
            "id" => "TestName2",
            "enabled" => true,
        );
        return $app['twig']->render('liveinteractions/index.html.twig', array(
            'interactions' => $enzymes,
            ));
    }

    /**
     * @route /interactions/{ID}
     */
    public function viewInteractionAction(Application $app, Request $request)
    {
        #TODO DB integration
        #require_once 'api/get/getInteraction.php';
        #$drug = getDrug($request->get('ID'));
        return $app['twig']->render('liveinteractions/view.html.twig', array(
                'interaction' => $request->get('ID')));
    }
    /**
     * @route /about
     */
    public function aboutAction(Application $app, Request $request)
    {
        return $app['twig']->render('liveabout/index.html.twig');
    }

}