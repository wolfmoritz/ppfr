<?php
namespace Recipe\Extensions;

/**
 * Custom Pagination Extension for Twig Templates
 */
class TwigExtensionPagination extends \Twig_Extension
{
    protected $environment;
    protected $baseUrl;
    protected $pageUrl;
    public $useQueryString = false;
    public $queryStringParam = 'pageno';
    protected $currentPageNumber;
    protected $rowsPerPage;
    protected $numberOfLinks;
    protected $totalRowsFound;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setParams();
    }

    // Identifer
    public function getName()
    {
        return 'moritzPaginator';
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
            'currentPageNumber' => $this->getCurrentPageNumber(),
        );
    }

    /**
     * Register Custom Functions
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('pagination', array($this, 'pagination'), array('is_safe' => array('html'))),
        );
    }

    /**
     * Set Pagination Params
     *
     * Gets and sets config params from $config['pagination'], and other basic setup tasks
     */
    public function setParams()
    {
        $app = \Slim\Slim::getInstance();
        $params = $app->config('pagination');
        $this->rowsPerPage = $params['rowsPerPage'];
        $this->numberOfLinks = $params['numberOfLinks'];

        // And set the base URL while we are it
        $this->baseUrl = $app->request()->getUrl();
    }

    /**
     * Set Base Page Path
     *
     * The application will determine the schema, domain, and use this path to set URL
     * @param string, path to resource
     */
    public function setPagePath($pagePath)
    {
        // Are we using query strings?
        if ($this->useQueryString) {
            $this->pageUrl = $this->baseUrl . $pagePath . '&' . $this->queryStringParam . '=';
        } else {
            $this->pageUrl = $this->baseUrl . $pagePath . '/';
        }
    }

    /**
     * Set Current Page Number
     *
     * Set the current page number
     * @param int, page number
     */
    public function setCurrentPageNumber($pageNumber)
    {
        $this->currentPageNumber = ($pageNumber) ? $pageNumber : 1;
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
     * Based on the $config['pagination']['rowsPerPage'] config value
     * this returns the offset for the current page number
     * @param none
     * @return int
     */
    public function getOffset()
    {
        return ($this->currentPageNumber - 1) * $this->rowsPerPage;
    }

    /**
     * Get Rows Per Page Config
     *
     * Gets the rows per page configuration setting
     * @param none
     * @return int
     */
    public function getRowsPerPage()
    {
        return $this->rowsPerPage;
    }

    /**
     * Set Total Rows Found
     *
     * Set the total rows matching the query
     * @param int, number of rows found
     */
    public function setTotalRowsFound($totalRowsFound)
    {
        $this->totalRowsFound = $totalRowsFound;
    }

    /**
     * Private: Build Pagination
     *
     * Build pagination link array
     */
    private function buildPagination()
    {
        // Calculate the number of page in this set
        $numberOfPages = ceil($this->totalRowsFound / $this->rowsPerPage);

        // Calcuate starting and ending page in the central link series
        $startPage = ($this->currentPageNumber - $this->numberOfLinks > 0) ? $this->currentPageNumber - $this->numberOfLinks : 1;
        $endPage = ($this->currentPageNumber + $this->numberOfLinks <= $numberOfPages) ? $this->currentPageNumber + $this->numberOfLinks : $numberOfPages;

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
     * Paginate
     *
     * Generate pagination links
     */
    public function pagination()
    {
        static $pageListCache;
        if ($pageListCache) {
            return $pageListCache;
        }

        // If there are no rows, or if there is only one page then return nothing
        if ($this->totalRowsFound === 0 || $this->rowsPerPage >= $this->totalRowsFound) {
            return $pageListCache = '';
        }

        // Pass the page list and current page to the template
        $values['links'] = $this->buildPagination();
        $values['currentPage'] = $this->currentPageNumber;
        $values['pageUrl'] = $this->pageUrl;

        return $this->environment->render('_pagination.html', $values);
    }
}
