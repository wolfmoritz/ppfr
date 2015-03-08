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
  private $sitemapFileName = 'sitemap.xml';

  /**
   *  Constructor
   *
   * @param $app, Object, Slim Application Object
   */
  public function __construct(\Slim\Slim $app) {
      // Get the instance
    $this->app = $app;
  }

  /**
   *  Generate sitemap
   */
  public function makeSitemap() {

    // Get all pages
    $dataMapper = $this->app->dataMapper;
    $PageMapper = $dataMapper('PageMapper');
    $pages = $PageMapper->find();
    $log = $this->app->log;
    $log->alert('Updating sitemap');

    $siteUrl = $this->app->request->getUrl();

    // Begin assembling the sitemap starting with the header
    $sitemap = "<\x3Fxml version=\"1.0\" encoding=\"UTF-8\"\x3F>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

    // Add each page
    foreach($pages as $page)
    {
      $modifiedDate = date('Y-m-d', strtotime($page->updated_date));
      $sitemap .= "\t<url>\n\t\t<loc>{$siteUrl}/{$page->slug}</loc>\n";
      $sitemap .= "\t\t<lastmod>{$modifiedDate}</lastmod>\n \t</url>\n";
    }

    // Close the sitemap XML
    $sitemap .=  "</urlset>\n";

    // Write the sitemap data to file at web root
    try {
      $sitemapFilePath = ROOT_DIR . $this->sitemapFileName;
      $fh = fopen($sitemapFilePath, 'w');
      fwrite($fh, $sitemap);
      fclose($fh);
    } catch (\Exception $e) {
      // Log failure
      $log->error('Failed to write sitemap');
      $log->error(print_r($e->getMessage(), true));

      return false;
    }

    // If this is the production instance, attempt to update Google with the new sitemap index.
    if($this->app->config('mode') === 'production')
    {
      // Ping Google via http request with updated sitemap
      $log->alert('Submitting sitemap to search engines');
      $sitemapUrl = urlencode($siteUrl . '/' . $this->sitemapFileName);
      $googleSitemapUrl = "http://www.google.com/webmasters/tools/ping?sitemap=" . $sitemapUrl;

      try {
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,2);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt ($ch, CURLOPT_URL, $googleSitemapUrl);
        $response = curl_exec($ch);
        $httpResponseStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
      } catch (\Exception $e) {
        // Log failure
        $log->error('Failed to connect to search engines');
        $log->error(print_r($e->getMessage(), true));

        return false;
      }


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
