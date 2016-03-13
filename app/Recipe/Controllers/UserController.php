<?php
namespace Recipe\Controllers;

/**
 * User Controller
 */
class UserController
{
    private $app;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->app = \Slim\Slim::getInstance();
    }

    /**
     * Display All Users
     */
    public function allUsers()
    {
        // Get mapper and twig template engine
        $dataMapper = $this->app->dataMapper;
        $UserMapper = $dataMapper('UserMapper');
        $twig = $this->app->twig;

        $users = $UserMapper->find();

        $twig->display('admin/userList.html', ['users' => $users, 'title' => 'All Users']);
    }
}
