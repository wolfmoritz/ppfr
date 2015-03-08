<?php
namespace Recipe\Controllers;

/**
 * Index Controller
 *
 * Renders public facing pages
 */
class IndexController
{
  private $app;

  public function __construct ()
  {
    $this->app = \Slim\Slim::getInstance();
  }

  public function index()
  {
    $twig = $this->app->twig;
    $twig->display('home.html');
  }

  public function showRecipe($id, $slug)
  {
    $twig = $this->app->twig;
    $twig->display('recipe.html');
  }

  /**
   * Get Page By Slug
   *
   * @param String, $slug
   */
  // public function getPageByID($slug)
  // {
  //   // Get data mappers
  //   $dataMapper = $this->app->dataMapper;
  //   $PageMapper = $dataMapper('PageMapper');
  //   $PageFieldMapper =  $dataMapper('PageFieldMapper');

  //   $page = $PageMapper->getPageBySlug($slug);

  //   // If no page record was found, raise 404
  //   if ($page === false) {
  //     // $this->app->log->error('IndexController->getPageBySlug() did not return a page record to load, redirecting to 404.');
  //     $this->app->notFound();
  //     return;
  //   }
  
  //   // Get fields
  //   $fields = $PageFieldMapper->getPageFieldsById($page->id);
  //   // TODO Move this into some mapper function
  //   foreach ($fields as $row) {
  //     $page->{$row->field_name} = ($row->field) ? $row->field : $row->field_text;
  //   }

  //   $twig = $this->app->twig;
  //   $twig->display($page->template . '.html', array('page' => $page));
  // }
}