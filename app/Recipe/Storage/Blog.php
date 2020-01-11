<?php
namespace Recipe\Storage;

use \Piton\ORM\DomainObject;

/**
 * Blog Domain Object
 */
class Blog extends DomainObject
{
    /**
     * Get Nice Blog URL
     */
    public function niceUrl()
    {
        return '/' . $this->blog_id . '/' . $this->url;
    }
}
