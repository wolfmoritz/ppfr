<?php
namespace Recipe\Controllers;

use Symfony\Component\Yaml\Yaml;

/**
 * Backend Controller for Pages
 */
class AdminPageController extends AdminBaseController
{
  /**
   * Constructor
   */
  public function __construct()
  {
    parent::__construct();

    // Security check
    $security = $this->app->security;
    $security->authorized('editor');
  }

  /**
   * List All Pages
   */
  public function listAllPages()
  {
    // Get list of pages
    $dataMapper = $this->app->dataMapper;
    $PageMapper = $dataMapper('PageMapper');
    $pages =  $PageMapper->find();

    $twig = $this->app->twig;
    $twig->display('pageList.html', array('pages' => $pages));
  }

  /**
   * New Page
   *
   * Select template for new page
   */
  public function newPage()
  {
    // Get a list of available page templates
    $files = glob($this->app->config('site')['themePath'] . 'definitions/*.yaml');

    // No templates found in theme?
    if ($files === false || empty($files)) {
      $this->app->halt(500, 'There are no page templates found for this theme.');
    }

    // Parse YAML files
    $templates = [];
    foreach ($files as $key => $file) {
      $pageDefinition = $this->getPageDefinition($file);

      $templates[$key]['name'] = $pageDefinition['name'];
      $templates[$key]['template'] = $pageDefinition['template'];
    }

    // Render template
    $twig = $this->app->twig;
    $twig->display('newPage.html', array('templates' => $templates));
  }

  /**
   * Edit or Create Page
   * 
   * @param String, Template
   * @param Integer, Page ID
   */
  public function editPage($template, $id = null)
  {
    // Get Mapper
    $dataMapper = $this->app->dataMapper;
    $PageMapper = $dataMapper('PageMapper');
    $PageFieldMapper = $dataMapper('PageFieldMapper');

    // Get template definition
    $templatePath = $this->app->config('site')['themePath'] . 'definitions/' . $template . '.yaml';
    $pageDefinition = $this->getPageDefinition($templatePath);

    // Was an ID was supplied?
    if (is_numeric($id)) {
      // Then get page record and fields
      $page =  $PageMapper->findById($id);
      $pageFields = $PageFieldMapper->getPageFieldsById($id);

      // Set the saved value in the definition array
      foreach ($pageDefinition['fields'] as $key => &$field) {
        // Find matching data row
        foreach ($pageFields as $row) {
          if ($key === $row->field_name) {
            $field['value'] = ($row->field) ? $row->field : $row->field_text;
            break;
          }
        }
      }

      $page->fields = $pageDefinition['fields'];

    } else {
      // New page, get empty domain object and set page definition
      $page = $PageMapper->make();
      $page->template = $pageDefinition['template'];
      $page->template_name = $pageDefinition['name'];
      $page->fields = $pageDefinition['fields'];
    }

    // If session data exists for the page edit, load that
    // TODO Improve this
    $session = $this->app->session;
    if ($pageData = $session->getData('editPageData')) {
      $page = $pageData;
      $session->unsetData('editPageData');
    }

    // Render template
    $twig = $this->app->twig;
    $twig->display('pageEditForm.html', array('page' => $page));
  }

  /**
   * Save Page (Public)
   * 
   * Saves page using post data
   */
  public function savePage()
  {
    // Get data mappers
    $dataMapper = $this->app->dataMapper;
    $PageFieldMapper = $dataMapper('PageFieldMapper');
    $PageMapper = $dataMapper('PageMapper');

    // Get page definition for template
    $definitionPath = $this->app->config('site')['themePath'] . 'definitions/' . $this->app->request->post('template') . '.yaml';
    $pageDefinition = $this->getPageDefinition($definitionPath);

    // Assign post data to domain object and set ID if it exists
    $page =  $PageMapper->make();
    $page->id = $this->app->request->post('id');

    // Is this a delete request? If so, no sense in doing anything more
    if ($this->app->request->post('delete') === 'delete') {
      $PageMapper->delete($page);
      $this->app->redirectTo('listPages');
    }

    // Assign other top-level fields to page object
    $page->template = $this->app->request->post('template');
    $page->template_name = $this->app->request->post('template_name');
    $page->name = $this->app->request->post('name');
    $page->title = $this->app->request->post('title');
    $page->slug = $this->app->request->post('slug');
    $page->meta_description = $this->app->request->post('meta_description');
    $page->publish_date = $this->app->request->post('publish_date');

    // Get custom field data as array
    $fieldsPost = $this->app->request->post('fields');

    // Check that slug does not already exist
    // TODO Repair this validation
    $exists = $PageMapper->validateSlug($page->slug, $page->id);
    if ($exists > 0) {
      // Oops, set error message and go back to edit
      $this->app->flash('message', 'The URL slug already exists.');
     
      // Save page data to session
      $session = $this->app->session;
      $session->setData('editPageData', $page);
      $session->setData('editPageFieldsData', $fieldsPost);
      
      $this->app->redirectTo('editPage');
      return;
    }

    // Save page data to get ID
    $page = $PageMapper->save($page);

    // Now loop through fields as defined in the page definition and match to post data
    $fieldsArray = [];
    foreach ($pageDefinition['fields'] as $key => $field) {
      $fieldObj = $PageFieldMapper->make();
      $fieldObj->page_id = $page->id;
      $fieldObj->field_name = $key;

      // Determine type of input when saving
      if ($field['type'] === 'textarea') {
        // If this is an array from an autogrow table serialize it
        if (is_array($fieldsPost[$key])) {
          $fieldsPost[$key] = serialize($fieldsPost[$key]);
        }

        $fieldObj->field_text = $fieldsPost[$key];

      } elseif ($field['type'] === 'image') {
        // TODO Fix this
        $fieldObj->media_id = 1;

      } else {
        $fieldObj->field = $fieldsPost[$key];
      }

      // Add to array
      $fieldsArray[] = $fieldObj;
      $fieldObj = null;
    }

    // Save all page fields
    $PageFieldMapper->upsertFields($fieldsArray);

    $this->app->redirectTo('listPages');
  }

  /**
   * Parse Definition File
   *
   * Parses yaml definition file and returns PHP array
   * On parse error sends user alert
   * @param string, filename
   * @return array
   */
  protected function getPageDefinition($yamlFile)
  {
    // Make sure we have a valid file path
    if (!file_exists($yamlFile)) {
      $this->app->flash('message', "No page definition file found for: {$yamlFile}");
      $this->app->redirectTo('listPages');
    }

    try {
      return Yaml::parse(file_get_contents($yamlFile));
    } catch (ParseException $e) {
      $this->app->flash('message', "Unable to parse Yaml page definition file for: {$yamlFile}");
      $this->app->redirectTo('listPages');
    }
  }
}
