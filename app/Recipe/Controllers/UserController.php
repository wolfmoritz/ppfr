<?php

declare(strict_types=1);

namespace Recipe\Controllers;

/**
 * User Controller
 */
class UserController extends BaseController
{
    /**
     * Display All Users
     */
    public function allUsers()
    {
        // Get dependencies
        $UserMapper = ($this->dataMapper)('UserMapper');

        $users = $UserMapper->find();

        $this->render('admin/userList.html', ['users' => $users, 'title' => 'All Users']);
    }
}
