<?php

namespace AntiC\User\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use InvalidArgumentException;
use AntiC\User\Manager\UserManager;

/**
 * Controller with actions for handling form-based authentication and user management.
 *
 * @package SimpleUser
 */
class UserController
{
    /** @var UserManager */
    protected $userManager;

    /**
     * Constructor.
     *
     * @param UserManager $userManager
     * @param array $options
     */
    public function __construct(UserManager $userManager, $options = array())
    {
        $this->userManager = $userManager;

        if (!empty($options)) {
            $this->setOptions($options);
        }
    }

    /**
     * Login action.
     *
     * @param Application $app
     * @param Request $request
     * @return Response
     */
    public function loginAction(Application $app, Request $request)
    {
        return $app['twig']->render('authenticate.html.twig', array(
            'error' => $app['security.last_error']($request),
            'last_username' => $app['session']->get('_security.last_username'),
            'allowRememberMe' => isset($app['security.remember_me.response_listener']),
        ));
    }

    /**
     * View Self Action
     * 
     * @route /console/account
     * @param Application $app
     * @param Request $request
     * @return twig rendered template
     * @throws InvalidArgumentException if CMD on POST is invalid
     */
    public function viewAction(Application $app, Request $request) {
        $nameError;
        $passwordError;
        $emailError;
        if ($request->isMethod('POST')) {
            switch ($request->get("cmd")) {
                // Change Name Function
                case "changeName":
                    $user = $app['user'];
                    $user->setName($request->request->get('name'));
                    $nameError = $this->userManager->validate($user);
                    if (empty($nameError)) {
                        $this->userManager->update($user);
                        $app['session']->getFlashBag()->set('success', "Successfully updated name.");
                    }
                    break;
                // Change Email Function
                case "changeEmail":
                    $user = $app['user'];
                    if (password_verify($request->request->get('password'), $user->getPassword())) {
                        $user->setEmail($request->request->get('email'));
                        $emailError = $this->userManager->validate($user);
                        if (empty($emailError)) {
                            $this->userManager->update($user);
                            $app['session']->getFlashBag()->set('success', "Successfully updated email.");
                        }
                    } else {
                        $emailError = array("Provided password was invalid.");
                    }
                    break;
                // Change Password Function
                case "changePassword":
                    $user = $app['user'];
                    $currentPassword = $request->request->get('currentPassword');
                    $newPassword = $request->request->get('newPassword');
                    $confirmPassword = $request->request->get('confirmPassword');
                    if ($newPassword == $confirmPassword && !empty($confirmPassword)) {
                        if (password_verify($currentPassword, $user->getPassword())) {
                            $this->userManager->setUserPassword($user, $confirmPassword);
                            $passwordError = $this->userManager->validate($user);
                            if (empty($passwordError)) {
                                $this->userManager->update($user);
                                $app['session']->getFlashBag()->set('success', "Successfully updated password.");
                            }
                        } else {
                            $passwordError = array("Provided password was invalid.");
                        }
                    } else {
                        $passwordError = array("New Password and Confirm Password do not match.");
                    }
                    break;
                default:
                    throw new InvalidArgumentException("Command not correct.");
                    break;
            }
        }

        return $app['twig']->render('@user/account/settings.html.twig', array(
            'user' => $app['user'],
            'nameError' => $nameError,
            'emailError' => $emailError,
            'passwordError' => $passwordError
        ));
    }

    /**
     * Add User Action.
     *
     * @route /console/user/add
     * @param Application $app
     * @param Request $request
     * @return twig rendered template
     */
    public function addAction(Application $app, Request $request)
    {
        if ($request->isMethod('POST')) {
            /**
             * @todo Write Add Method
             */
        }

        return $app['twig']->render('@user/management/add.html.twig', array(
            'error' => isset($error) ? $error : null
        ));
    }

    /**
     * List Users Action.
     *
     * @route /console/user
     * @param Application $app
     * @param Request $request
     * @return twig rendered template
     */
    public function listAction(Application $app, Request $request)
    {
        $users = $this->userManager->findBy(array());

        // Unable to modify self from User Management (prevents no admins on system)
        $position = array_search($app['user'], $users);
        unset($users[$position]);

        return $app['twig']->render('@user/management/index.html.twig', array(
            'users' => $users,
        ));
    }


    /**
     * @todo Any methods after this are old and need to be changed.
     */

    /**
     * Register action.
     *
     * @param Application $app
     * @param Request $request
     * @return Response
     */
    public function registerAction(Application $app, Request $request)
    {
        if ($request->isMethod('POST')) {
            try {
                $user = $this->createUserFromRequest($request);
                $this->userManager->insert($user);
                $app['session']->getFlashBag()->set('alert', 'Account created.');

                // Log the user in to the new account.
                if (null !== ($current_token = $app['security']->getToken())) {
                    $providerKey = method_exists($current_token, 'getProviderKey') ? $current_token->getProviderKey() : $current_token->getKey();
                    $token = new UsernamePasswordToken($user, null, $providerKey);
                    $app['security']->setToken($token);
                }

                return $app->redirect($app['url_generator']->generate('user.view', array('id' => $user->getId())));

            } catch (InvalidArgumentException $e) {
                $error = $e->getMessage();
            }
        }

        return $app['twig']->render('@user/register.twig', array(
            'layout_template' => $this->layoutTemplate,
            'error' => isset($error) ? $error : null,
            'name' => $request->request->get('name'),
            'email' => $request->request->get('email'),
        ));
    }

    /**
     * @param Request $request
     * @return User
     * @throws InvalidArgumentException
     */
    protected function createUserFromRequest(Request $request)
    {
        if ($request->request->get('password') != $request->request->get('confirm_password')) {
            throw new InvalidArgumentException('Passwords don\'t match.');
        }

        $user = $this->userManager->createUser(
            $request->request->get('email'),
            $request->request->get('password'),
            $request->request->get('name') ?: null);

        $errors = $this->userManager->validate($user);
        if (!empty($errors)) {
            throw new InvalidArgumentException(implode("\n", $errors));
        }

        return $user;
    }

    /**
     * Edit user action.
     *
     * @param Application $app
     * @param Request $request
     * @param int $id
     * @return Response
     * @throws NotFoundHttpException if no user is found with that ID.
     */
    public function editAction(Application $app, Request $request, $id)
    {
        $errors = array();

        $user = $this->userManager->getUser($id);

        if (!$user) {
            throw new NotFoundHttpException('No user was found with that ID.');
        }

        if ($request->isMethod('POST')) {
            $user->setName($request->request->get('name'));
            $user->setEmail($request->request->get('email'));
            if ($request->request->get('password')) {
                if ($request->request->get('password') != $request->request->get('confirm_password')) {
                    $errors['password'] = 'Passwords don\'t match.';
                } else {
                    $this->userManager->setUserPassword($user, $request->request->get('password'));
                }
            }
            if ($app['security']->isGranted('ROLE_ADMIN') && $request->request->has('roles')) {
                $user->setRoles($request->request->get('roles'));
            }

            $errors += $this->userManager->validate($user);

            if (empty($errors)) {
                $this->userManager->update($user);
                $msg = 'Saved account information.' . ($request->request->get('password') ? ' Changed password.' : '');
                $app['session']->getFlashBag()->set('alert', $msg);
            }
        }

        return $app['twig']->render('@user/management/edit.html.twig', array(
            'error' => implode("\n", $errors),
            'user' => $user,
            'available_roles' => array('ROLE_USER', 'ROLE_ADMIN'),
        ));
    }
}