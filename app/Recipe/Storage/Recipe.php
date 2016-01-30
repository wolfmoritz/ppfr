<?php
namespace Recipe\Storage;

/**
 * Recipe Domain Object
 */
class Recipe extends DomainObjectAbstract
{
    /**
     * Get Nice Recipe URL
     */
    public function niceUrl()
    {
        return '/' . $this->recipe_id . '/' . $this->url;
    }

    /**
     * Merge Data
     *
     * Merge an array of values into the class properties by array key = property
     */
    public function mergeRecipe(array $modifiedRecipe)
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
