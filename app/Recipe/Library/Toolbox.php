<?php
namespace Recipe\Library;

/**
 *  Toolbox Class
 *
 *  Misc methods
 */
class Toolbox
{
  static $app;

  /**
   * Constructor
   */
  public function __construct(\Slim\Slim $app)
  {
    self::$app = $app;
  }

  /**
   * Truncate HTML Text
   *
   * Accepts an HTML string, and returns just the unformatted text, truncated to the number of characters
   * @param text, string, input HTML string
   * @param length, integer, number of characters to return
   * @param ending, string, append ellipsis or other characters to returned string
   * @param exact, boolean, whether to cut the string precisely at the desired length, or stop at the last whole word
   * @param considerHtml, boolean, whether to return with html
   */
  static public function truncateHtmlText($text, $length = 100, $ending = '...', $exact = false, $considerHtml = true)
  {
    if ($considerHtml) {
      // If the plain text is shorter than the maximum length, return the whole text
      if (mb_strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
        return $text;
      }

      // Split all html-tags to scanable lines
      preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
      $totalLength = mb_strlen($ending);
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
        $contentLength = mb_strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', ' ', $lineMatching[2]));
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
                $entitiesLength += mb_strlen($entity[0]);
              } else {
                // No more characters left
                break;
              }
            }
          }
          $truncate .= mb_substr($lineMatching[2], 0, $left + $entitiesLength);
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
      if (mb_strlen($text) <= $length) {
        return $text;
      } else {
        $truncate = mb_substr($text, 0, $length - mb_strlen($ending));
      }
    }

    // If the words shouldn't be cut in the middle...
    if (!$exact) {
      // ...Search the last occurance of a space...
      $spacepos = mb_strrpos($truncate, ' ');
      if (isset($spacepos)) {
        // ...And cut the text in this position
        $truncate = mb_substr($truncate, 0, $spacepos);
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
}
