<?php

declare(strict_types=1);

namespace Recipe\Storage;

use Piton\ORM\DomainObject;

/**
 * Blog Domain Object
 */
class Blog extends DomainObject
{
    /**
     * Get Nice Blog URL
     *
     * @param void
     * @return string
     */
    public function niceUrl(): string
    {
        return '/' . $this->blog_id . '/' . $this->url;
    }
}
