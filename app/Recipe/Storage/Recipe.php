<?php
namespace Recipe\Storage;

/**
 * Recipe Domain Object
 */
class Recipe extends DomainObjectAbstract
{
	/**
	 * Get Full Recipe URL
	 */
	public function niceUrl()
	{
		return '/' . $this->recipe_id . '/' . $this->url;
	}
}
