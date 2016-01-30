<?php
namespace Recipe\Storage;

/**
 * Blog Mapper
 */
class BlogMapper extends DataMapperAbstract
{
    protected $table = 'blog';
    protected $tableAlias = 'b';
    protected $primaryKey = 'blog_id';
    protected $modifyColumns = array('title', 'url', 'content', 'content_excerpt');
    protected $domainObjectClass = 'Blog';
    protected $defaultSelect = 'select SQL_CALC_FOUND_ROWS b.*, concat(us.first_name, \' \', us.last_name) user_name, concat(us.user_id, \'/\', us.first_name, \'+\', us.last_name) user_url from blog b join pp_user us on b.created_by = us.user_id';

    /**
     * Get Blog Posts with Offset
     *
     * Define limit and offset to limit result set.
     * Returns an array of Domain Objects (one for each record)
     * @param int, limit
     * @param int, offset
     * @param bool, only get published posts (true)
     * @return array
     */
    public function getPosts($limit = null, $offset = null, $publishedPostsOnly = true)
    {
        $this->sql = $this->defaultSelect;

        if ($publishedPostsOnly) {
            $this->sql .= ' where b.published_date <= curdate()';
        }

        // Add order by
        $this->sql .= ' order by b.published_date desc';

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
    public function save(DomainObjectAbstract $blog)
    {
        // Get dependencies
        $app = \Slim\Slim::getInstance();
        $Toolbox = $app->Toolbox;

        // Set URL safe blog title
        $blog->url = $Toolbox->cleanUrl($blog->title);

        // Set content excerpt
        $blog->content_excerpt = $Toolbox->truncateHtmlText($blog->content);

        return $this->_save($blog);
    }
}
