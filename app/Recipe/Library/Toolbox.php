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
     * Accepts an HTML string, and returns just the unformatted text, truncated to the number of words
     * @param text, string, input HTML string
     * @param length, integer, number of characters to return
     * @param ending, string, append ellipsis or other characters to returned string
     * @param exact, boolean, whether to cut the string precisely at the desired length, or stop at the last whole word
     * @param considerHtml, boolean, whether to return with html
     */
    public function truncateHtmlText($text, $characters = 300)
    {
        // Clean up html tags and special characters
        $text = preg_replace('/<[^>]*>/', ' ', $text);
        $text = str_replace("\r", '', $text); // replace with empty space
        $text = str_replace("\n", ' ', $text); // replace with space
        $text = str_replace("\t", ' ', $text); // replace with space
        $text = preg_replace('/\s+/', ' ', $text); // remove multiple consecutive spaces
        $text = preg_replace('/^[\s]/', '', $text); // Remove leading space from excerpt
        $text = preg_replace('/[\s]$/', '', $text); // Remove trailing space from excerpt

        // Truncate to character limit
        $text = substr($text, 0, $characters);

        // We don't want the string cut mid-word, so search for the last space and trim there
        $lastSpacePosition = strrpos($text, ' ');
        if (isset($lastSpacePosition)) {
            // Cut the text at this last word
            $text = substr($text, 0, $lastSpacePosition);
        }

        return $text;
    }

    /**
     * Time to ISO8601 Duration Format
     *
     * Convert time duration (UNIX) and converts to ISO8601 Duration format
     * @param int
     * @return string
     */
    public function timeToIso8601Duration($time)
    {
        $units = array(
            "Y" => 365 * 24 * 3600,
            "D" => 24 * 3600,
            "H" => 3600,
            "M" => 60,
            "S" => 1,
        );

        $str = "P";
        $istime = false;

        foreach ($units as $unitName => &$unit) {
            $quot = intval($time / $unit);
            $time -= $quot * $unit;
            $unit = $quot;

            if ($unit > 0) {
                if (!$istime and in_array($unitName, array("H", "M", "S"))) {
                    $str .= "T";
                    $istime = true;
                }
                $str .= strval($unit) . $unitName;
            }
        }

        return $str;
    }

    /**
     * String to Seconds
     *
     * Convert duration string like '1 day 4 hours and 3 min' to seconds
     * @param string
     * @return mixed, int, Returns null if no matches are found, otherwise returns the number of seconds.
     */
    public function stringToSeconds($str = null)
    {
        $totalSeconds = 0;

        if (preg_match_all('/\s*,?(\d+)\s*(?:(d)(?:ays?)?|(h)(?:ours?)?|(m)(?:in(?:utes?)?)?)/i', $str, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                if (end($match) === 'd') {
                    $mult = 24 * 60 * 60;
                } else if (end($match) === 'h') {
                    $mult = 60 * 60;
                } else {
                    $mult = 60;
                }

                $totalSeconds += $match[1] * $mult;
            }

            return $totalSeconds;
        }

        return null;
    }

    /**
     * Clean URLs
     *
     * Replaces any non alphanumeric or space characters with dashes
     * @param string
     * @return string
     */
    public function cleanUrl($string)
    {
        // First replace ampersands with the word 'and'
        $string = str_replace('&', 'and', $string);

        // Strip out any single quotes
        $string = str_replace("'", '', $string);

        // Remove unwelcome characters, and replace with dashes
        $string = preg_replace('/[^a-zA-Z0-9]+/', '-', $string);

        // Finally remove and trailing dashes
        $string = trim($string, '-');

        return $string;
    }
}
