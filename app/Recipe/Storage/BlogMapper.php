<?php
namespace Recipe\Storage;

use Piton\ORM\DataMapperAbstract;
use Piton\ORM\DomainObject;

/**
 * Blog Mapper
 */
class BlogMapper extends DataMapperAbstract
{
    protected $table = 'blog';
    protected $primaryKey = 'blog_id';
    protected $modifiableColumns = ['title', 'url', 'content', 'content_excerpt', 'published_date'];
    protected $domainObjectClass = __NAMESPACE__ . '\Blog';
    protected $defaultSelect = 'select SQL_CALC_FOUND_ROWS b.*, concat(us.first_name, \' \', us.last_name) user_name, concat(us.user_id, \'/\', us.first_name, \'+\', us.last_name) user_url from blog b join pp_user us on b.created_by = us.user_id';

    /**
     * Get Blog Posts in Reverse Date Order
     *
     * Define limit and offset to limit result set.
     * Returns an array of Domain Objects (one for each record)
     * @param  int  $limit
     * @param  int  $offset
     * @param  bool $publishedPostsOnly Only get published posts (true)
     * @return array
     */
    public function getPosts(int $limit = null, int $offset = null, bool $publishedPostsOnly = true)
    {
        $this->sql = $this->defaultSelect;

        if ($publishedPostsOnly) {
            $this->sql .= ' where b.published_date <= curdate()';
        }

        if ($publishedPostsOnly) {
            $this->sql .= ' order by b.published_date desc';
        } else {
            $this->sql .= ' order by b.published_date is null desc, b.published_date desc';
        }

        if ($limit) {
            $this->sql .= " limit ?";
            $this->bindValues[] = $limit;
        }

        if ($offset) {
            $this->sql .= " offset ?";
            $this->bindValues[] = $offset;
        }

        return $this->find();
    }

    /**
     * Save Blog
     *
     * Adds pre-save manipulation prior to calling _save
     * @param Domain Object
     * @return mixed, Domain Object on success, false otherwise
     */
    public function save(DomainObject $domainObject)
    {
        // Get dependencies
        $app = \Slim\Slim::getInstance();
        $Toolbox = $app->Toolbox;

        // Set URL safe domainObject title
        $domainObject->url = $Toolbox->cleanUrl($domainObject->title);

        // Set content excerpt
        $domainObject->content_excerpt = $Toolbox->truncateHtmlText($domainObject->content);

        return parent::save($domainObject);
    }
}
