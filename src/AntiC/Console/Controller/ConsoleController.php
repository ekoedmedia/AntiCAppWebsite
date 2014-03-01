<?php

namespace AntiC\Console\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class ConsoleController
{
	public function loginAction(Request $request, Application $app)
	{
		return $app['twig']->render('authenticate.html.twig', array(
        	'error' => $app['security.last_error']($request),
        	'last_username' => $app['session']->get('_security.last_username'),
    	));
	}

	public function accountAction(Application $app)
	{
		return $app['twig']->render('account/settings.html.twig');
	}

}