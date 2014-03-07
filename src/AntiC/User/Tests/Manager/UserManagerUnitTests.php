<?php

namespace AntiC\User\Tests\Manager;

use AntiC\User\Manager\UserManager;

/**
 * Unit Tests User Manager
 *
 * @author Jeremy Smereka <jeremy.smereka@ekoed.com>
 */
class UserManagerUnitTests extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AntiC\User\Manager\UserManager;
     */
    private $manager;

    /**
     * Constructor creates Manager to test with
     */
    public function __construct()
    {
        $app = $this->createApplication();
        $this->manager = new UserManager($app['db'], $app);
    }

    /**
     * Creates Application to use
     *
     * @return Application
     */
    public function createApplication()
    {
        return require __DIR__.'/../../../../web/index.php';
    }

    /**
     * Tests loading of User by Username (Email)
     */
    public function testLoadUserByUsername()
    {
        $emailAddress = "test@test.test";

        $user = $this->manager->loadUserByUsername($emailAddress);
        $this->assertEquals("test", $user->getName());
    }

}