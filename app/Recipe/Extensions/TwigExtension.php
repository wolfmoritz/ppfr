<?php
namespace Recipe\Extensions;

/**
 * Custom Extensions for Twig
 */
class TwigExtension extends \Twig_Extension
{
    protected $environment;

    // Identifer
    public function getName()
    {
        return 'moritz';
    }

    // Initialize
    public function initRuntime(\Twig_Environment $environment)
    {
        $this->environment = $environment;
    }

    /**
     * Register Global variables
     */
    public function getGlobals()
    {
        return array(
            'mode' => $this->getConfig('mode'),
            'production' => ($this->getConfig('mode') === 'production') ? true : false,
            'request' => $this->getServerVars(),
            'categories' => $this->categories(),
        );
    }

    /**
     * Register Custom Filters
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('excerpt', array($this, 'truncateHtml')),
            new \Twig_SimpleFilter('formatIngredients', array($this, 'formatIngredients')),
        );
    }

    /**
     * Register Custom Functions
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('flash', array($this, 'flash')),
            new \Twig_SimpleFunction('checked', array($this, 'checked')),
            new \Twig_SimpleFunction('excerpt', array($this, 'truncateHtml')),
            new \Twig_SimpleFunction('imageUrl', array($this, 'imageUrl')),
            new \Twig_SimpleFunction('siteUrlFor', array($this, 'siteUrlFor')),
            new \Twig_SimpleFunction('uriSegment', array($this, 'uriSegment')),
            new \Twig_SimpleFunction('formatIngredients', array($this, 'formatIngredients')),
            new \Twig_SimpleFunction('topRecipes', array($this, 'topRecipes')),
            new \Twig_SimpleFunction('randomRecipes', array($this, 'randomRecipes')),
            new \Twig_SimpleFunction('config', array($this, 'getConfig')),
            new \Twig_SimpleFunction('session', array($this, 'getSession')),
        );
    }

    /**
     * Get Configuration Items
     *
     * Gets settings from $app->config(<config-items>);
     * @param string, config item name
     */
    public function getConfig($item)
    {
        $app = \Slim\Slim::getInstance();

        return $app->config($item);
    }

    /**
     * Get Server Request Variables
     *
     * Gets selected variables from $_SERVER
     */
    public function getServerVars()
    {
        $app = \Slim\Slim::getInstance();

        return array(
            'host' => $app->request->getHost(),
        );
    }

    /**
     * Get Flash Messages
     *
     * Because we are not replacing Slim Views with Twig, but using them together but separately,
     * we needed a way to get to flash messages in the Twig template.
     *
     * @param String, Default 'message'
     * @return String
     */
    public function flash($key = 'message')
    {
        return isset($_SESSION['slim.flash'][$key]) ? $_SESSION['slim.flash'][$key] : false;
    }

    /**
     * Set Checkbox
     *
     * If the supplied value is true "1" returns the checked string
     * @param 0/1
     */
    public function checked($checked = 0)
    {
        return (isset($checked) && $checked == 1) ? 'checked="checked"' : '';
    }

    /**
     * truncateHtml can truncate a string up to a number of characters while preserving whole words and HTML tags
     * http://alanwhipple.com/2011/05/25/php-truncate-string-preserving-html-tags-words/
     *
     * @param string $text String to truncate.
     * @param integer $length Length of returned string, including ellipsis.
     * @param string $ending Ending to be appended to the trimmed string.
     * @param boolean $exact If false, $text will not be cut mid-word
     * @param boolean $considerHtml If true, HTML tags would be handled correctly
     *
     * @return string Trimmed string.
     */

    public function truncateHtml($text, $length = 100, $ending = '...', $exact = false, $considerHtml = true)
    {
        $app = \Slim\Slim::getInstance();
        $Toolbox = $app->Toolbox;

        return $Toolbox::truncateHtmlText($text, $length, $ending, $exact, $considerHtml);
    }

    /**
     * Get Upload Image URL
     */
    public function imageUrl($id, $filename, $width = null, $height = null)
    {
        static $largeUri;
        static $thumbUri;
        static $baseUrl = '';
        static $app;

        // Cache variables for next request
        if (!isset($app)) {
            $app = \Slim\Slim::getInstance();

            $largeUri = $app->config('image')['file.uri'];
            $thumbUri = $app->config('image')['file.thumb.uri'];

            $baseUrl = $app->request->getUrl() . $app->request->getRootUri() . '/';
        }

        // Was the full sized image requested?
        if ($width === null && $height === null) {
            // Just return url to the original image

            // This is a temporary hack (no $baseUrl) as our photos are on the old site
            if (strpos($largeUri, 'http') === 0) {
                return "{$largeUri}{$id}/files/{$filename}";
            }

            return "{$baseUrl}{$largeUri}{$id}/files/{$filename}";
        }

        // Make sure at least one dimension is set to a numeric size
        if (!is_numeric($width) && !is_numeric($height)) {
            throw new \Exception('imageUrl expects at least one numeric dimension');
        }

        // If width or height is not provided, set to empty string to keep aspect ratio
        $width = (is_numeric($width)) ? $width : '';
        $height = (is_numeric($height)) ? $height : '';

        return $baseUrl . $thumbUri . $id . '/' . $width . 'x' . $height . '/' . $filename;
    }

    /**
     * Get Full URL to Named Route
     *
     * @param String, the named route
     * @param mixed, array of segments, or URI string
     * @return String, the site URL + route path
     */
    public function siteUrlFor($namedRoute, $segments = null)
    {
        $app = \Slim\Slim::getInstance();
        $uri = $app->request()->getUrl();
        $route = $app->urlFor($namedRoute);
        $urlSegments = '';

        if ($segments !== null && is_array($segments)) {
            $urlSegments = '/' . implode('/', $segments);
        }

        if ($segments !== null) {
            $urlSegments = $segments;
            $urlSegments = ltrim($segments, '/');
            $urlSegments = '/' . $urlSegments;
        }

        return $uri . $route . $urlSegments;
    }

    /**
     * Get Request Segment
     *
     * Get request URI segment by position (1 indexed)
     */
    public function uriSegment($pos)
    {
        $app = \Slim\Slim::getInstance();
        $request = $app->request;

        $uriPath = $request->getResourceUri();

        if (isset($uriPath)) {
            $segments = explode('/', $uriPath);

            return isset($segments[$pos]) ? $segments[$pos] : null;
        }
    }

    /**
     * Format Ingredients
     *
     * Takes ingredients string and creates list items with checkboxes
     * @param string, ingredients text
     * @return string, html ingredients
     */
    public function formatIngredients($ingr)
    {
        // Clean input of \r's and then create array of ingredients
        $ingr = str_replace("\n\r", "\n", $ingr);
        $ingrList = explode("\n", $ingr);

        $ingrStr = '<li><label><input class="step-ingredient-check" type="checkbox"> ';
        $ingrStr .= implode("</label></li>\n<li><label><input class=\"step-ingredient-check\" type=\"checkbox\"> ", $ingrList);
        $ingrStr .= "</label></li>\n";

        return $ingrStr;
    }

    /**
     * Get Categories
     *
     * Get all categories
     */
    public function categories()
    {
        // Cache results
        static $categories;

        if ($categories) {
            return $categories;
        }

        $app = \Slim\Slim::getInstance();
        $dataMapper = $app->dataMapper;
        $CategoryMapper = $dataMapper('CategoryMapper');

        return $categories = $CategoryMapper->getAllCategories();
    }

    /**
     * Get Top Recipes
     */
    public function topRecipes($limit = 5)
    {
        // Cache results
        static $topRecipes;

        if ($topRecipes) {
            return $topRecipes;
        }

        $app = \Slim\Slim::getInstance();
        $dataMapper = $app->dataMapper;
        $RecipeMapper = $dataMapper('RecipeMapper');

        return $topRecipes = $RecipeMapper->getTopRecipes($limit);
    }

    /**
     * Get Random Recipes
     */
    public function randomRecipes($limit = 5)
    {
        // Cache results
        static $randomRecipes;

        if ($randomRecipes) {
            return $randomRecipes;
        }

        $app = \Slim\Slim::getInstance();
        $dataMapper = $app->dataMapper;
        $RecipeMapper = $dataMapper('RecipeMapper');

        return $randomRecipes = $RecipeMapper->getRandomRecipes($limit);
    }

    public function getSession($key = null)
    {
        static $sessionData = [];

        // Cach session data to avoid round trips to the DB
        if (!$sessionData) {
            $app = \Slim\Slim::getInstance();
            $SessionHandler = $app->SessionHandler;

            // Get all of the session data, it can't be that much, right?
            $sessionData = $SessionHandler->getData();
        }

        // Returns the key if provided, else all values
        if ($key === null) {
            return ($sessionData) ? $sessionData : null;
        }

        return isset($sessionData[$key]) ? $sessionData[$key] : null;
    }
}
