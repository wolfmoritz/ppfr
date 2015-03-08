<?php
namespace Recipe\Library;

/**
 *  Cache Handler Class
 *
 *  Clears cache files. Currently only the twig cach directory.
 */
class CacheHandler 
{
  public $deletedFiles = 0;
  public $deletedDirectories = 0;
  public $twigCachePath;

  /**
   * Clear Twig Cache
   */
  public function clearTwigCache()
  {
    // Get the twig cache path
    $app = \Slim\Slim::getInstance();
    $this->twigCachePath = $app->config('twig.parserOptions')['cache'];

    if (!is_dir($this->twigCachePath)) {
      throw new \Exception('The twig cache path is not defined: [twig.parserOptions][cache]');
    }

    // Get an array of Twig cache directories
    $directories = array_diff(scandir($this->twigCachePath), array('.', '..'));

    // Loop through twig directories to delete recurisvely
    foreach ($directories as $dir) {
      if (is_dir($this->twigCachePath . '/' . $dir)) {
        $this->deleteDirectory($this->twigCachePath . '/' . $dir);
      }
    }

    return;
  }

  /**
   * Delete Directories Recurively
   */
  private function deleteDirectory($dir) 
  {
    if (is_dir($dir)) {
      // Get list of objects in supplied directory
      $objects = array_diff(scandir($dir), array('.', '..')); 
      
      // Loop through objects
      foreach ($objects as $object) {
        $objectPath = $dir . '/' . $object;

        // Determine type of object
        if (is_dir($objectPath)) {
          // Call this method recursively if this is a directory
          $this->deleteDirectory($objectPath);
        } elseif (is_file($objectPath)) {
          // Delete file
          $this->deletedFiles++;
          unlink($objectPath);
        }
      }

      // Remove containing directory
      $this->deletedDirectories++;
      rmdir($dir);
    }

    return;
  }
}
