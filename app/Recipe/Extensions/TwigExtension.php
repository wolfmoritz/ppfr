<?php

declare(strict_types=1);

namespace Recipe\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Exception;

/**
 * Custom Extensions for Twig Templates
 */
class TwigExtension extends AbstractExtension implements GlobalsInterface
{
    /**
     * Class Cache
     * @var array
     */
    protected $cache = [];

    /**
     * Register Global variables
     *
     * @param void
     * @return array
     */
    public function getGlobals(): array
    {
        return [
            'mode' => $this->getConfig('mode'),
            'production' => ($this->getConfig('mode') === 'production'),
            'request' => $this->getServerVars(),
            'categories' => $this->categories(),
        ];
    }

    /**
     * Register Custom Filters
     *
     * @param void
     * @return array
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('excerpt', [$this, 'truncateHtml']),
            new TwigFilter('formatIngredients', [$this, 'formatIngredients']),
        ];
    }

    /**
     * Register Custom Functions
     *
     * @param void
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('flash', [$this, 'flash']),
            new TwigFunction('checked', [$this, 'checked']),
            new TwigFunction('excerpt', [$this, 'truncateHtml']),
            new TwigFunction('imageUrl', [$this, 'imageUrl']),
            new TwigFunction('siteUrlFor', [$this, 'siteUrlFor']),
            new TwigFunction('uriSegment', [$this, 'uriSegment']),
            new TwigFunction('formatIngredients', [$this, 'formatIngredients']),
            new TwigFunction('randomRecipes', [$this, 'randomRecipes']),
            new TwigFunction('config', [$this, 'getConfig']),
            new TwigFunction('session', [$this, 'getSession']),
            new TwigFunction('role', [$this, 'authorizedRole']),
            new TwigFunction('authorizedToEditRecipe', [$this, 'authorizedToEditRecipe']),
            new TwigFunction('blogPostNav', [$this, 'getBlogPostNav'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Get Configuration Items
     *
     * Gets settings from $app->config(<config-items>);
     * @param string $item Config item name
     * @return string|array|null
     */
    public function getConfig(string $item)
    {
        isset($this->cache['app']) ?: $this->cache['app'] = \Slim\Slim::getInstance();

        return $this->cache['app']->config($item);
    }

    /**
     * Get Server Request Variables
     *
     * Gets selected variables from $_SERVER
     * @param void
     * @return array
     */
    public function getServerVars(): array
    {
        isset($this->cache['app']) ?: $this->cache['app'] = \Slim\Slim::getInstance();

        return array(
            'host' => $this->cache['app']->request->getHost(),
            'basePath' => $this->cache['app']->request->getRootUri(),
        );
    }

    /**
     * Get Flash Messages
     *
     * Because we are not replacing Slim Views with Twig, but using them together but separately,
     * we needed a way to get to flash messages in the Twig template.
     *
     * @param string $key Default 'message'
     * @return string Nullable
     */
    public function flash(string $key = 'message'): ?string
    {
        return $_SESSION['slim.flash'][$key] ?? null;
    }

    /**
     * Set Checkbox
     *
     * If the supplied value is true "1" returns the checked string
     * @param 0/1
     * @return string
     */
    public function checked($checked = 0): string
    {
        return (isset($checked) && $checked == 1) ? 'checked="checked"' : '';
    }

    /**
     * truncateHtml can truncate a string up to a number of characters while preserving whole words and HTML tags
     * http://alanwhipple.com/2011/05/25/php-truncate-string-preserving-html-tags-words/
     *
     * @param string $text         String to truncate.
     * @param int    $length       Length of returned string, including ellipsis.
     * @param string $ending       Ending to be appended to the trimmed string.
     * @param bool   $exact        If false, $text will not be cut mid-word
     * @param bool   $considerHtml If true, HTML tags would be handled correctly
     * @return string Trimmed string.
     */

    public function truncateHtml(string $text, int $length = 100, string $ending = '...', bool $exact = false, bool $considerHtml = true): string
    {
        isset($this->cache['app']) ?: $this->cache['app'] = \Slim\Slim::getInstance();
        $toolbox = $this->cache['app']->Toolbox;

        return $toolbox::truncateHtmlText($text, $length, $ending, $exact, $considerHtml);
    }

    /**
     * Get Upload Image URL
     *
     * @param int|string $id
     * @param string     $filename
     * @param int        $width
     * @param int        @height
     * @return string
     */
    public function imageUrl($id, string $filename, int $width = null, int $height = null): string
    {
        // Cache variables for next request
        isset($this->cache['app']) ?: $this->cache['app'] = \Slim\Slim::getInstance();
        isset($this->cache['largeUri']) ?: $this->cache['largeUri'] = $this->cache['app']->config('image')['file.uri'];
        isset($this->cache['thumbUri']) ?: $this->cache['thumbUri'] = $this->cache['app']->config('image')['file.thumb.uri'];
        isset($this->cache['baseUrl']) ?: $this->cache['baseUrl'] = $this->cache['app']->request->getUrl() . $this->cache['app']->request->getRootUri() . '/';

        // Was the full sized image requested?
        if ($width === null && $height === null) {
            // Just return url to the original image

            // This is a temporary hack (no $baseUrl) as our photos are on the old site
            if (strpos($this->cache['largeUri'], 'http') === 0) {
                return "{$this->cache['largeUri']}{$id}/files/{$filename}";
            }

            // Test if "_large" variant exists and return the full size version
            $fileInfo = pathinfo($filename);
            $largeImageFile = "{$this->cache['largeUri']}{$id}/files/{$fileInfo['filename']}_large.{$fileInfo['extension']}";
            if (file_exists($largeImageFile)) {
                return $this->cache['baseUrl'] . $largeImageFile;
            }

            // Otherwise return default
            return "{$this->cache['baseUrl']}{$this->cache['largeUri']}{$id}/files/{$filename}";
        }

        // Make sure at least one dimension is set to a numeric size
        if (!is_numeric($width) && !is_numeric($height)) {
            throw new Exception('imageUrl expects at least one numeric dimension');
        }

        // If width or height is not provided, set to empty string to keep aspect ratio
        $width = (is_numeric($width)) ? $width : '';
        $height = (is_numeric($height)) ? $height : '';

        return $this->cache['baseUrl'] . $this->cache['thumbUri'] . $id . '/' . $width . 'x' . $height . '/' . $filename;
    }

    /**
     * Get Full URL to Named Route
     *
     * @param string            $namedRoute Named of the route
     * @param array|string|null $segments   Array of segments, or URI string
     * @return string Full URL
     */
    public function siteUrlFor(string $namedRoute, $segments = null): ?string
    {
        isset($this->cache['app']) ?: $this->cache['app'] = \Slim\Slim::getInstance();
        isset($this->cache['domain']) ?: $this->cache['domain'] = $this->cache['app']->request()->getUrl();

        if (is_array($segments)) {
            return $this->cache['domain'] . $this->cache['app']->urlFor($namedRoute, $segments);
        } elseif ($segments !== null) {
            return $this->cache['domain'] . $this->cache['app']->urlFor($namedRoute) . '/' . ltrim((string) $segments, '/');
        }

        return $this->cache['domain'] . $this->cache['app']->urlFor($namedRoute);
    }

    /**
     * Get Request Segment
     *
     * Get request URI segment by position (1 indexed)
     * @param int $pos
     * @return string|null
     */
    public function uriSegment(int $pos): ?string
    {
        isset($this->cache['app']) ?: $this->cache['app'] = \Slim\Slim::getInstance();
        $request = $this->cache['app']->request;
        $segments = [];
        $uriPath = $request->getResourceUri();

        if (isset($uriPath)) {
            $segments = explode('/', $uriPath);
        }

        return $segments[$pos] ?? null;
    }

    /**
     * Format Ingredients
     *
     * Takes ingredients string and creates list items with checkboxes
     * @param string $ing Ingredients text
     * @return string
     */
    public function formatIngredients(string $ingr): string
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
     * @param void
     * @return array
     */
    public function categories(): array
    {
        // Check cache
        if (isset($this->cache['categories'])) {
            return $this->cache['categories'];
        }

        isset($this->cache['app']) ?: $this->cache['app'] = \Slim\Slim::getInstance();
        $categoryMapper = ($this->cache['app']->dataMapper)('CategoryMapper');

        return $this->cache['categories'] = $categoryMapper->getAllCategories();
    }

    /**
     * Get Random Recipes
     *
     * @param int $limit
     * @return array|null
     */
    public function randomRecipes(int $limit = 5): ?array
    {
        isset($this->cache['app']) ?: $this->cache['app'] = \Slim\Slim::getInstance();
        $recipeMapper = ($this->cache['app']->dataMapper)('RecipeMapper');

        return $recipeMapper->getRandomRecipes($limit);
    }

    /**
     * Get Session Data
     *
     * @param string $key
     * @return mixed
     */
    public function getSession(string $key = null)
    {
        isset($this->cache['app']) ?: $this->cache['app'] = \Slim\Slim::getInstance();

        $sessionHandler = $this->cache['app']->SessionHandler;
        return $sessionHandler->getData($key);
    }

    /**
     * Verify Role Level
     *
     * Compares supplied role against user role
     * @param string $role Role name
     * @return bool
     */
    public function authorizedRole(string $role = null): bool
    {
        isset($this->cache['app']) ?: $this->cache['app'] = \Slim\Slim::getInstance();
        $security = $this->cache['app']->security;

        return $security->authorized($role, false);
    }

    /**
     * Verify User is Authorized to Edit Recipe
     *
     * @param \Piton\ORM\DomainObject $recipe DomainObject
     * @return bool
     */
    public function authorizedToEditRecipe(\Piton\ORM\DomainObject $recipe): bool
    {
        isset($this->cache['app']) ?: $this->cache['app'] = \Slim\Slim::getInstance();
        $security = $this->cache['app']->security;

        return $security->authorizedToEditRecipe($recipe);
    }

    /**
     * Get Blog Posts Nav
     *
     * @param void
     * @return array|null
     */
    public function getBlogPostNav(): ?array
    {
        isset($this->cache['app']) ?: $this->cache['app'] = \Slim\Slim::getInstance();
        $blogMapper = ($this->cache['app']->dataMapper)('BlogMapper');

        // Get posts
        $posts = $blogMapper->getPosts();

        // Nest array by month
        $priorPosts = [];
        foreach ($posts as $post) {
            $priorPosts[(new \DateTime($post->published_date))->format('Y-m')][] = $post;
        }

        return $priorPosts;
    }
}
