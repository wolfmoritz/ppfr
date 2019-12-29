<?php
namespace Recipe\Storage;

/**
 * User Mapper
 */
class UserMapper extends DataMapperAbstract
{
    protected $table = 'pp_user';
    protected $tableAlias = 'us';
    protected $primaryKey = 'user_id';
    protected $modifyColumns = ['last_login_date'];
    protected $domainObjectClass = 'User';
    protected $defaultSelect = 'select us.*, concat(us.first_name, \' \', us.last_name) user_name, concat(us.first_name, \'-\', us.last_name) user_url from pp_user us';

    /**
     * Get User by ID
     *
     * @param int, user_id
     * @return DomainObjectAbstract, user
     */
    public function getUser($userId)
    {
        return $this->findById($userId);
    }

    /**
     * Get User by email
     */
    public function getUserByEmail($email)
    {
        $this->sql = $this->defaultSelect . ' where email = ?';
        $this->bindValues[] = $email;

        // Fetch and return user
        $this->execute();
        $result = $this->statement->fetch();
        $this->clear();

        return $result;
    }
}
