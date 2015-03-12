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
    return array('site' => $this->getGlobalSettings(), 'request' => $this->getServerVars());
  }

  /**
   * Register Custom Filters
   */
  public function getFilters()
  {
      return array(
          new \Twig_SimpleFilter('excerpt', array($this, 'truncateHtml')),
          new \Twig_SimpleFilter('formatIngredients', array($this, 'formatIngredients'))
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
          new \Twig_SimpleFunction('uriSegment', array($this, 'getUriSegment')),
          new \Twig_SimpleFunction('formatIngredients', array($this, 'formatIngredients'))
      );
  }

  /**
   * Get Global Settings
   * 
   * Gets settings from $app->config('site');
   */
  public function getGlobalSettings()
  {
    $app = \Slim\Slim::getInstance();
    
    return $app->config('site');
  }

  /**
   * Get Server Request Variables
   *
   * Gets selected variables from $_SERVER
   */
  public function getServerVars() 
  {
    return array('host' => $_SERVER['HTTP_HOST'], 'path' => $_SERVER['REQUEST_URI']);
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

  function truncateHtml($text, $length = 100, $ending = '...', $exact = false, $considerHtml = true) 
  {
    if ($considerHtml) {
      // If the plain text is shorter than the maximum length, return the whole text
      if (strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
        return $text;
      }

      // Split all html-tags to scanable lines
      preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
      $totalLength = strlen($ending);
      $openTags = array();
      $truncate = '';
      foreach ($lines as $lineMatching) {
        // If there is any html-tag in this line, handle it and add it (uncounted) to the output
        if (!empty($lineMatching[1])) {
          // If it's an "empty element" with or without xhtml-conform closing slash
          if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $lineMatching[1])) {
            // do nothing
          // If tag is a closing tag
          } else if (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $lineMatching[1], $tag_matchings)) {
            // Delete tag from $openTags list
            $pos = array_search($tag_matchings[1], $openTags);
            if ($pos !== false) {
            unset($openTags[$pos]);
            }
          // If tag is an opening tag
          } else if (preg_match('/^<\s*([^\s>!]+).*?>$/s', $lineMatching[1], $tag_matchings)) {
            // Add tag to the beginning of $openTags list
            array_unshift($openTags, strtolower($tag_matchings[1]));
          }
          // Add html-tag to $truncate'd text
          $truncate .= $lineMatching[1];
        }

        // Calculate the length of the plain text part of the line; handle entities as one character
        $contentLength = strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', ' ', $lineMatching[2]));
        if ($totalLength + $contentLength > $length) {
          // The number of characters which are left
          $left = $length - $totalLength;
          $entitiesLength = 0;
          // Search for html entities
          if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', $lineMatching[2], $entities, PREG_OFFSET_CAPTURE)) {
            // calculate the real length of all entities in the legal range
            foreach ($entities[0] as $entity) {
              if ($entity[1]+1-$entitiesLength <= $left) {
                $left--;
                $entitiesLength += strlen($entity[0]);
              } else {
                // No more characters left
                break;
              }
            }
          }
          $truncate .= substr($lineMatching[2], 0, $left + $entitiesLength);
          // Maximum lenght is reached, so get off the loop
          break;
        } else {
          $truncate .= $lineMatching[2];
          $totalLength += $contentLength;
        }
        // if the maximum length is reached, get off the loop
        if($totalLength >= $length) {
          break;
        }
      }
    } else {
      if (strlen($text) <= $length) {
        return $text;
      } else {
        $truncate = substr($text, 0, $length - strlen($ending));
      }
    }
    
    // If the words shouldn't be cut in the middle...
    if (!$exact) {
      // ...Search the last occurance of a space...
      $spacepos = strrpos($truncate, ' ');
      if (isset($spacepos)) {
        // ...And cut the text in this position
        $truncate = substr($truncate, 0, $spacepos);
      }
    }
    
    // Add the defined ending to the text
    $truncate .= $ending;
    if($considerHtml) {
      // close all unclosed html-tags
      foreach ($openTags as $tag) {
        $truncate .= '</' . $tag . '>';
      }
    }

    return $truncate;
  }

  /**
   * Get Upload Image URL
   */
  public function imageUrl($filename, $width = null, $height = null)
  {
    // If no filename was provided, then return nothing
    if (empty($filename)) {
      return '';
    }

    static $largeUri;
    static $thumbUri;
    static $uri = '';

    // Cache variables for next request
    if (!isset($largeUri)) {
      $app = \Slim\Slim::getInstance();
      $largeUri = $app->config('file.uri');
      $thumbUri = $app->config('file.thumb.uri');
      $uri = $app->request->getUrl() . $app->request->getRootUri() . '/';
    }

    // Was the full sized image requested?
    if ($width === null and $height === null) {
      // Just return url to the original image
      return $uri . $largeUri . $filename;
    }
    
    // Make sure at least one dimension is set to a size
    if (!is_numeric($width) and !is_numeric($height)) {
      throw new \Exception('getImageUrl expects at least one numeric dimension');
    }

    // If width or height is not provided, set to '' to keep aspect ratio
    $width = (is_numeric($width)) ? $width : '';
    $height = (is_numeric($height)) ? $height : '';

    return $uri . $thumbUri . $width . 'x' . $height . '/' . $filename;
  }

  /**
   * Get Full URL to Named Route
   * 
   * @param String, the named route
   * @return String, the site URL + route path
   */
  public function siteUrlFor($namedroute)
  {
      $app = \Slim\Slim::getInstance();
      $uri = $app->request()->getUrl();
      $path = $app->urlFor($namedroute);
      
      return $uri . $path;
  }

  /**
   * Get Request Segment
   *
   * Get request URI segment by position (1 indexed)
   */
  public function getUriSegment($pos)
  {
    if (isset($_SERVER['REQUEST_URI'])) {
      $segments = explode('/', $_SERVER['REQUEST_URI']);

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
}
