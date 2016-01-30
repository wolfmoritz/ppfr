<?php
namespace Recipe\Storage;

/**
 * Blog Domain Object
 */
class Blog extends DomainObjectAbstract
{
    /**
     * Get Nice Blog URL
     */
    public function niceUrl()
    {
        return '/' . $this->blog_id . '/' . $this->url;
    }
}
