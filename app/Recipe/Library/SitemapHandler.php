<?php
namespace Recipe\Library;

/**
 *  Sitemap Handler Class
 *
 *  Generates or updates sitemap on request
 */
class SitemapHandler
{

  protected $app;
  protected $baseUrl;
  protected $sitemapFileName = 'sitemap.xml';
  protected $sitemapFilePath;

  /**
   *  Constructor
   *
   * @param $app, Object, Slim Application Object
   */
  public function __construct(\Slim\Slim $app) {
    // Get the instance
    $this->app = $app;

    // Set the base url
    $this->baseUrl = $this->app->config('baseurl');

    // Set the full file path
    $this->sitemapFilePath = ROOT_DIR . 'web/' . $this->sitemapFileName;
  }

  /**
   *  Generate sitemap
   */
  public function make() {

    // Get all pages
    $dataMapper = $this->app->dataMapper;
    $RecipeMapper = $dataMapper('RecipeMapper');
    $pages = $RecipeMapper->find();
    $log = $this->app->log;
    $log->alert('Updating sitemap');

    $siteUrl = $this->app->request->getUrl();
    $today = date('Y-m-d', time());

    // Begin assembling the sitemap starting with the header
    $sitemap = "<\x3Fxml version=\"1.0\" encoding=\"UTF-8\"\x3F>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

    // Add static pages
    $sitemap .= "\t<url>\n\t\t<loc>{$this->baseUrl}{$this->app->router->urlFor('about')}</loc>\n";
    $sitemap .= "\t\t<lastmod>{$today}</lastmod>\n \t</url>\n";

    $sitemap .= "\t<url>\n\t\t<loc>{$this->baseUrl}{$this->app->router->urlFor('blog')}</loc>\n";
    $sitemap .= "\t\t<lastmod>{$today}</lastmod>\n \t</url>\n";

    // Add each recipe page to sitemap, and track the most recent update date
    $lastUpdated = strtotime('-2 days'); // Something  before the last update
    foreach($pages as $page) {
      $updated = strtotime($page->updated_date);
      $lastUpdated = max($lastUpdated, $updated);
      $modifiedDate = date('Y-m-d', $updated);
      $sitemap .= "\t<url>\n\t\t<loc>{$this->baseUrl}{$this->app->router->urlFor('showRecipe', ['id' => $page->recipe_id, 'slug' => $page->url])}</loc>\n";
      $sitemap .= "\t\t<lastmod>{$modifiedDate}</lastmod>\n \t</url>\n";
    }

    // Close the sitemap XML
    $sitemap .=  "</urlset>\n";

    // Write the sitemap data to file at web root
    try {
      $fh = fopen($this->sitemapFilePath, 'w');
      fwrite($fh, $sitemap);
      fclose($fh);
    } catch (\Exception $e) {
      // Log failure
      $log->error('Failed to write sitemap');
      $log->error(print_r($e->getMessage(), true));

      return false;
    }

    // If this is the production instance, attempt to update Google with the new sitemap index.
    if($this->app->config('mode') === 'production' && $lastUpdated >= strtotime('-1 day'))
    {
      // Ping Google via http request with updated sitemap
      $log->alert('Submitting sitemap to search engines');
      $sitemapUrl = urlencode($baseUrl . '/' . $this->sitemapFileName);
      $googleSitemapUrl = "http://www.google.com/webmasters/tools/ping?sitemap=" . $sitemapUrl;

      try {
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,2);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_URL, $googleSitemapUrl);
        $response = curl_exec($ch);
        $httpResponseStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
      } catch (\Exception $e) {
        // Log failure
        $log->error('Failed to connect to search engines');
        $log->error(print_r($e->getMessage(), true));

        return false;
      }

      $log->alert('Google response: ' . $httpResponseStatus);

      // Log error if update fails
      if (substr($httpResponseStatus, 0, 1) != 2)
      {
        $log = $this->app->log;
        $log->error('error', 'Ping Google with updated sitemap failed. Status: ' . $httpResponseStatus);
        $log->error('error', '->' . $googleSitemapUrl);

        return false;
      }
    }

    return true;
  }
}
