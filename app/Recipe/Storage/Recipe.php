<?php

declare(strict_types=1);

namespace Recipe\Storage;

use Piton\ORM\DomainObject;

/**
 * Recipe Domain Object
 */
class Recipe extends DomainObject
{
    /**
     * Get Nice Recipe URL
     *
     * @param  void
     * @return string
     */
    public function niceUrl(): string
    {
        return '/' . $this->recipe_id . '/' . $this->url;
    }

    /**
     * Merge Data
     *
     * Merge an array of values into the class properties by array key = property
     * @param  array $modifiedRecipe
     * @return void
     */
    public function mergeRecipe(array $modifiedRecipe): void
    {
        // Make sure we have an array
        if (!is_array($modifiedRecipe)) {
            throw new \Exception(__CLASS__ . '->' . __METHOD__ . ' expects array');
        }

        foreach ($this as $key => $value) {
            if (isset($modifiedRecipe[$key])) {
                $this->$key = $modifiedRecipe[$key];
            }
        }
    }
}
