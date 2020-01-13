<?php
namespace Recipe\Controllers;

/**
 * Blog Controller
 *
 * Manages Blog Pages
 */
class BlogController extends BaseController
{
    /**
     * Show a Single Post
     *
     * @param int, blog id
     * @param string, blog url
     */
    public function showPost($id, $url = null)
    {
        // Get data mappers
        $dataMapper = $this->app->dataMapper;
        $BlogMapper = $dataMapper('BlogMapper');
        $Security = $this->app->security;
        $SessionHandler = $this->app->SessionHandler;

        // Fetch post
        $blog = $BlogMapper->findById((int) $id);

        // If no post found then 404
        if (!$blog) {
            $this->app->notFound();
            return;
        }

        // Authorization check
        if (!$blog->published_date) {
            // Ok, blog post is not published, but let author or admin continue
            $user = $SessionHandler->getData();
            if (!$Security->authorized('admin') || (int) $blog->created_by !== (int) $user['user_id']) {
                $this->app->notFound();
            }
        }

        // If there was no url provided, then 301 redirect back here with the url segment
        if ($url !== $blog->url) {
            $this->app->redirect($this->app->urlFor('showBlogPost', ['id' => $blog->blog_id, 'url' => $blog->url]), 301);
        }

        $twig = $this->app->twig;
        $twig->display('blog.html', ['blog' => $blog, 'title' => $blog->title]);
    }

    /**
     * Get Blog Posts
     *
     */
    public function getBlogPosts()
    {
        // Get services
        $blogMapper = ($this->dataMapper)('BlogMapper');
        $paginator = $this->getPaginator();

        // Configure pagination object
        $paginator->setPagePath($this->app->urlFor('blogPosts'));

        // Fetch posts
        $posts = $blogMapper->getPosts($paginator->getResultsPerPage(), $paginator->getOffset());

        // Get count of posts returned by query and load pagination
        $paginator->setTotalResultsFound($blogMapper->foundRows());
        $this->loadTwigExtension($paginator);

        $this->render('blogList.html', ['posts' => $posts, 'title' => 'Blog Posts']);
    }

    /**
     * Get Blog Posts (Admin)
     *
     */
    public function getAdminBlogPosts()
    {
        // Get services
        $blogMapper = ($this->dataMapper)('BlogMapper');
        $paginator = $this->getPaginator();

        // Configure pagination object
        $paginator->setPagePath($this->app->urlFor('adminBlogPosts'));

        // Fetch posts
        $posts = $blogMapper->getPosts($paginator->getResultsPerPage(), $paginator->getOffset(), false);

        // Get count of posts returned by query and load pagination
        $paginator->setTotalResultsFound($blogMapper->foundRows());
        $this->loadTwigExtension($paginator);

        $this->render('admin/userBlogList.html', ['posts' => $posts, 'title' => 'Blog Posts']);
    }

    /**
     * Edit Blog Post
     *
     * Create or edit a blog post
     * @param int, blog post id
     */
    public function editPost($id = null)
    {
        // Get mapper and services
        $blogMapper = ($this->dataMapper)('BlogMapper');
        $sessionHandler = $this->app->SessionHandler;

        // Get user session data for reference
        $sessionData = $sessionHandler->getData();

        // If a blog post ID was supplied, get that post, otherwise get a blank blog record
        if ($id !== null) {
            $blog = $blogMapper->findById((int) $id);
        } else {
            $blog = $blogMapper->make();
        }

        // Verify authority to edit post
        if (is_numeric($blog->blog_id) && (int) $sessionData['user_id'] !== (int) $blog->created_by) {
            // Just redirect to show post
            $this->app->redirectTo('showBlogPost', ['id' => $blog->blog_id, 'url' => $blog->niceUrl()]);
        }

        // Fetch any saved form data from session state and merge
        if (isset($sessionData['blog'])) {
            // Merge saved data
            // $recipe->mergeRecipe($sessionData['blog']);

            // Unset session data
            $sessionHandler->unsetData('blog');
        }

        // Display
        $this->render('admin/editBlogPost.html', ['blog' => $blog, 'title' => 'Edit Blog Post']);
    }
}
