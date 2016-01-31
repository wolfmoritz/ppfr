<?php
namespace Recipe\Controllers;

/**
 * Blog Action Controller
 *
 * Performs actions on data (update, insert, delete)
 */
class BlogActionController
{
    private $app;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->app = \Slim\Slim::getInstance();
    }

    /**
     * Save Blog
     *
     * Accepts POST data
     */
    public function saveBlogPost()
    {
        // Get services
        $dataMapper = $this->app->dataMapper;
        $BlogMapper = $dataMapper('BlogMapper');
        $SessionHandler = $this->app->SessionHandler;
        $Security = $this->app->security;
        $ImageUploader = $this->app->ImageUploader;
        $Validation = $this->app->Validation;

        // If a Blog ID was supplied, get that blog post. Otherwise get a blank blog record
        if (!empty($this->app->request->post('blog_id'))) {
            $blog = $BlogMapper->findById((int) $this->app->request->post('blog_id'));
        } else {
            $blog = $BlogMapper->make();
        }

        // Verify authority to modify blog post
        if (!$Security->authorized('admin')) {
            // Just redirect to show blog
            $this->app->redirectTo('showBlogPost', ['id' => $id, 'url' => $blog->niceUrl()]);
        }

        // If this is a previously published blog, use that publish date as default
        $publishedDate = ($this->app->request->post('published_date')) ?: '';
        if ($this->app->request->post('button') === 'publish' && empty($publishedDate)) {
            // Then default to today
            $date = new \DateTime();
            $publishedDate = $date->format('Y-m-d');
        }

        // Validate data....
        $rules = array(
            'required' => [['title']],
            'dateFormat' => [['published_date', 'Y-m-d']],
        );

        $v = $Validation($this->app->request->post());
        $v->rules($rules);

        // Run validation
        if (!$v->validate()) {
            // Fail! Save to session for page reload
            $errorMessages = $this->formatErrorMessages($v->errors());
            $this->app->flash('level', 'danger');
            $this->app->flash('message', "You forgot something!<br> $errorMessages");
            $SessionHandler->setData('blog', $this->app->request->post());

            // Return to edit blog
            if (empty($blog->blog_id)) {
                // If new blog without an ID yet
                $this->app->redirectTo('adminEditBlogPost');
            } else {
                // For an existing blog
                $this->app->redirectTo('adminEditBlogPost', ['id' => $blog->blog_id]);
            }

            return;
        }

        // Assign data
        // Note: the url field is set in the BlogMapper on save
        $blog->title = $this->app->request->post('title');
        $blog->content = $this->app->request->post('content');

        // Only set the publish date if not empty
        if (!empty($publishedDate)) {
            $blog->published_date = $publishedDate;
        }

        // Save blog
        $blog = $BlogMapper->save($blog);

        // If we have a blog ID and a photo, then handle any image upload
        // if (is_numeric($blog->recipe_id) && !empty($_FILES['main_photo']['tmp_name'])) {
        //     $ImageUploader->initialize((int) $blog->recipe_id);

        //     if (!$ImageUploader->upload('main_photo')) {
        //         // Snap! Get messages and direct back to edit blog
        //         $errorMessages = $this->formatErrorMessages($ImageUploader->getMessages());

        //         // No need to save form data, we can pull it from the database at this point
        //         $this->app->flash('level', 'danger');
        //         $this->app->flash('message', "You forgot something!<br> $errorMessages");

        //         $this->app->redirectTo('adminEditRecipe', ['id' => $blog->recipe_id]);
        //     }

        //     // Update blog with main_image name
        //     $blog->main_photo = $ImageUploader->imageFileName;
        //     $BlogMapper->update($blog);
        // }

        $this->app->redirectTo('adminBlogPosts');
    }

    /**
     * Unpublish Blog Post
     *
     * @param int, blog ID
     */
    public function unpublishBlogPost($id)
    {
        // Get services
        $dataMapper = $this->app->dataMapper;
        $BlogMapper = $dataMapper('BlogMapper');
        $Security = $this->app->security;
        $Session = $this->app->SessionHandler;
        $user = $Session->getData();

        // Get the recipe to unpublish
        $blog = $BlogMapper->findById((int) $id);

        // Verify authority to modify blog post. Admins can edit all
        if ((int) $blog->created_by !== (int) $user['user_id']) {
            // Just redirect to show blog
            $this->app->redirectTo('showBlogPost', ['id' => $id, 'url' => $blog->url]);
        }

        // Unset the published date and save
        $blog->published_date = '';
        $blog = $BlogMapper->save($blog);

        // Go back to edit blog
        $this->app->redirectTo('adminEditBlogPost', ['id' => $blog->blog_id]);
    }

    /**
     * Delete Blog Post
     *
     * @param int, blog ID
     */
    public function deleteBlogPost($id)
    {
        // Get services
        $Security = $this->app->security;
        $dataMapper = $this->app->dataMapper;
        $BlogMapper = $dataMapper('BlogMapper');
        $Session = $this->app->SessionHandler;
        $user = $Session->getData();

        // Get blog record
        $blog = $BlogMapper->findById((int) $id);

        // Verify authority to modify blog post
        if ((int) $blog->created_by !== (int) $user['user_id']) {
            // Just redirect to show blog
            $this->app->redirectTo('showBlogPost', ['id' => $id, 'url' => $blog->url]);
        }

        $BlogMapper->delete($blog);
        $this->app->redirectTo('adminBlogPosts');
    }

    /**
     * Format Array of Error Messages
     *
     * Accepts a multidimensional array of error messages,
     * and returns a formatted HTML unordered list of error messages
     * @param mixed, string or array of messages
     * @return string, unordered list
     */
    public function formatErrorMessages($messages)
    {
        $messageString = null;

        if (is_string($messages)) {
            $this->formatErrorMessages([$messages]);
        }

        if (is_array($messages)) {
            $messageString = '<ul>';
            array_walk_recursive($messages, function ($a) use (&$messageString) {
                $messageString .= "<li>$a</li>";
            });
            $messageString .= '</ul>';
        }

        return $messageString;
    }
}
