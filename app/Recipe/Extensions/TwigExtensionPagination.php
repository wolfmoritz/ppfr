<?php
namespace Recipe\Extensions;

/**
 * Custom Pagination Extension for Twig Templates
 */
class TwigExtensionPagination extends \Twig\Extension\AbstractExtension implements \Twig\Extension\GlobalsInterface
{
    protected $domain;
    protected $pageUrl;
    protected $queryStringParam = 'page';
    protected $currentPageNumber;
    protected $resultsPerPage;
    protected $numberOfAdjacentLinks;
    protected $totalResultsFound;

    /**
     * Constructor
     *
     * @param  array $config Configuration options array
     * @return void
     */
    public function __construct(array $config)
    {
        $this->setConfig($config);
        $this->setCurrentPageNumber();
    }

    /**
     * Register Global variables
     */
    public function getGlobals()
    {
        return [
            'currentPageNumber' => $this->getCurrentPageNumber(),
        ];
    }

    /**
     * Register Custom Functions
     */
    public function getFunctions()
    {
        return [
            new \Twig\TwigFunction('pagination', [$this, 'pagination'], ['needs_environment' => true, 'is_safe' => ['html']]),
        ];
    }

    /**
     * Set Page Path
     *
     * Submit relative path, plus any query string parameters
     * @param  string $pagePath    Relative path to resource
     * @param  array  $queryParams Array of query strings and values
     * @return void
     */
    public function setPagePath(string $pagePath, array $queryParams = null)
    {
        $this->pageUrl = $this->domain . $pagePath . '?';

        if ($queryParams) {
            $this->pageUrl .= http_build_query($queryParams) . '&';
        }

        $this->pageUrl .= $this->queryStringParam . '=';
    }

    /**
     * Set Current Page Number
     *
     * Automatically gets the page number request from query parameter for $this->queryStringParam
     * Or, accepts the current page number explicitly as an argument
     * @param  int $pageNumber Current page number
     * @return void
     */
    public function setCurrentPageNumber(int $pageNumber = null)
    {
        if ($pageNumber) {
            $this->currentPageNumber = ($pageNumber) ? $pageNumber : 1;
        } else {
            $this->currentPageNumber = isset($_GET[$this->queryStringParam]) ? htmlspecialchars($_GET[$this->queryStringParam]) : 1;
        }
    }

    /**
     * Get Current Page Number
     *
     * Gets the current page number for display in templates
     * @return string, page number
     */
    public function getCurrentPageNumber()
    {
        return $this->currentPageNumber;
    }

    /**
     * Get Offset
     *
     * Based on the $config['pagination']['resultsPerPage'] config value
     * this returns the offset for the current page number
     * @param  void
     * @return int
     */
    public function getOffset()
    {
        return ($this->currentPageNumber - 1) * $this->resultsPerPage;
    }

    /**
     * Get Results Per Page Config Value
     *
     * Gets the rows per page configuration setting
     * @param  void
     * @return int
     */
    public function getResultsPerPage()
    {
        return $this->resultsPerPage;
    }

    /**
     * Set Total Results Found
     *
     * Set the total results from the query
     * @param  int $totalResultsFound number of rows found
     * @return void
     */
    public function setTotalResultsFound(int $totalResultsFound)
    {
        $this->totalResultsFound = $totalResultsFound;
    }

    /**
     * Private: Build Pagination
     *
     * Build pagination links array
     * @param  void
     * @return void
     */
    private function buildPagination()
    {
        // Calculate the number of page in this set
        $numberOfPages = ceil($this->totalResultsFound / $this->resultsPerPage);

        // Calcuate starting and ending page in the central link series
        $startPage = ($this->currentPageNumber - $this->numberOfAdjacentLinks > 0) ? $this->currentPageNumber - $this->numberOfAdjacentLinks : 1;
        $endPage = ($this->currentPageNumber + $this->numberOfAdjacentLinks <= $numberOfPages) ? $this->currentPageNumber + $this->numberOfAdjacentLinks : $numberOfPages;

        // Create array for page links
        $pageList = [];

        //  Start with Previous link
        if ((int) $this->currentPageNumber === 1) {
            $pageList[] = ['href' => $this->pageUrl . 1, 'link' => ''];
        } else {
            $pageList[] = ['href' => $this->pageUrl . ($this->currentPageNumber - 1), 'link' => ''];
        }

        // Always include the page one link
        if ($startPage > 1) {
            $pageList[] = ['href' => $this->pageUrl . 1, 'link' => 1];
        }

        // Do we need to add ellipsis after '1' and before the link series?
        if ($startPage >= 3) {
            $pageList[] = ['href' => '', 'link' => 'ellipsis'];
        }

        // Build link series
        for ($i = $startPage; $i <= $endPage; ++$i) {
            $pageList[] = ['href' => $this->pageUrl . $i, 'link' => $i];
        }

        // Do we need to add ellipsis after the link series?
        if ($endPage <= $numberOfPages - 2) {
            $pageList[] = ['href' => '', 'link' => 'ellipsis'];
        }

        // Always include last page link
        if ($endPage < $numberOfPages) {
            $pageList[] = ['href' => $this->pageUrl . $numberOfPages, 'link' => $numberOfPages];
        }

        // And finally, the Next link
        if ($endPage === $numberOfPages) {
            $pageList[] = ['href' => $this->pageUrl . $numberOfPages, 'link' => ''];
        } else {
            $pageList[] = ['href' => $this->pageUrl . ($this->currentPageNumber + 1), 'link' => ''];
        }

        return $pageList;
    }

    /**
     * Pagination
     *
     * Render pagination links HTML
     * @param  void
     * @return void
     */
    public function pagination(\Twig\Environment $env)
    {
        static $pageListCache;
        if ($pageListCache) {
            return $pageListCache;
        }

        // If there are no rows, or if there is only one page then return nothing
        if ($this->totalResultsFound === 0 || $this->resultsPerPage >= $this->totalResultsFound) {
            return $pageListCache = '';
        }

        // Pass the page list and current page to the template
        $values['links'] = $this->buildPagination();
        $values['currentPage'] = $this->currentPageNumber;
        $values['pageUrl'] = $this->pageUrl;

        return $env->render('_pagination.html', $values);
    }

    /**
     * Set Pagination Configuration
     *
     * @param  array $config Configuration array of options
     * @return void
     */
    public function setConfig(array $config)
    {
        $this->resultsPerPage = $config['resultsPerPage'];
        $this->numberOfAdjacentLinks = $config['numberOfAdjacentLinks'];
        $this->domain = isset($config['domain']) ? $config['domain'] : '/';
    }
}
