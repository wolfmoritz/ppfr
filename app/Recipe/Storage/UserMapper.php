<?php

declare(strict_types=1);

namespace Recipe\Storage;

use Piton\ORM\DataMapperAbstract;
use Piton\ORM\DomainObject;

/**
 * User Mapper
 */
class UserMapper extends DataMapperAbstract
{
    protected $table = 'pp_user';
    protected $primaryKey = 'user_id';
    protected $modifiableColumns = ['last_login_date'];
    protected $defaultSelect = 'select us.*, concat(us.first_name, \' \', us.last_name) user_name, concat(us.first_name, \'-\', us.last_name) user_url from pp_user us';

    /**
     * Get User by ID
     *
     * @param  int $userId
     * @return DomainObject
     */
    public function getUser(int $userId): ?DomainObject
    {
        $this->sql = $this->defaultSelect . ' where user_id = ?';
        $this->bindValues[] = $userId;

        return $this->findRow();
    }

    /**
     * Get User by Email
     *
     * @param string $email
     * @return DomainObject
     */
    public function getUserByEmail(string $email): ?DomainObject
    {
        $this->sql = $this->defaultSelect . ' where email = ?';
        $this->bindValues[] = $email;

        // Fetch and return user
        return $this->findRow();
    }
}
