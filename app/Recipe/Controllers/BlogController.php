<?php
namespace Recipe\Controllers;

/**
 * Blog Controller
 *
 * Manages Blog Pages
 */
class BlogController
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

        // Fetch post
        $post = $BlogMapper->findById((int) $id);

        // If no post found then 404
        if (!$post) {
            $this->app->notFound();
            return;
        }

        // If there was no url provided, then 301 redirect back here with the url segment
        if ($url === null) {
            $this->app->redirect($this->app->urlFor('show') . $blog->niceUrl(), 301);
            return;
        }

        $twig = $this->app->twig;
        $twig->display('blog.html', ['post' => $post, 'title' => $post->title]);
    }

    /**
     * Get Blog Posts
     *
     */
    public function getBlogPosts()
    {
        // Get services
        $dataMapper = $this->app->dataMapper;
        $BlogMapper = $dataMapper('BlogMapper');
        $Paginator = $this->app->PaginationHandler;
        $twig = $this->app->twig;

        // Get the page number
        $pageNumber = $this->app->request->get('page') ?: 1;

        // Configure pagination object
        $Paginator->useQueryString = true;
        $Paginator->setPagePath($this->app->urlFor('blogPosts'));
        $Paginator->setCurrentPageNumber((int) $pageNumber);

        // Fetch posts
        $posts = $BlogMapper->getPosts($Paginator->getRowsPerPage(), $Paginator->getOffset());

        // Get count of posts returned by query and load pagination
        $Paginator->setTotalRowsFound($BlogMapper->foundRows());
        $twig->parserExtensions[] = $Paginator;

        $twig->display('blogList.html', ['posts' => $posts, 'title' => 'Blog Posts']);
    }

    /**
     * Get Blog Posts (Admin)
     *
     */
    public function getAdminBlogPosts()
    {
        // Get services
        $dataMapper = $this->app->dataMapper;
        $BlogMapper = $dataMapper('BlogMapper');
        $Paginator = $this->app->PaginationHandler;
        $twig = $this->app->twig;

        // Get the page number
        $pageNumber = $this->app->request->get('page') ?: 1;

        // Configure pagination object
        $Paginator->useQueryString = true;
        $Paginator->setPagePath($this->app->urlFor('adminBlogPosts'));
        $Paginator->setCurrentPageNumber((int) $pageNumber);

        // Fetch posts
        $posts = $BlogMapper->getPosts($Paginator->getRowsPerPage(), $Paginator->getOffset(), false);

        // Get count of posts returned by query and load pagination
        $Paginator->setTotalRowsFound($BlogMapper->foundRows());
        $twig->parserExtensions[] = $Paginator;

        $twig->display('admin/userBlogList.html', ['posts' => $posts, 'title' => 'Blog Posts']);
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
        $dataMapper = $this->app->dataMapper;
        $BlogMapper = $dataMapper('BlogMapper');
        $SessionHandler = $this->app->SessionHandler;
        $SecurityHandler = $this->app->security;

        // Get user session data for reference
        $sessionData = $SessionHandler->getData();

        // If a blog post ID was supplied, get that post, otherwise get a blank blog record
        if ($id !== null) {
            $blog = $BlogMapper->findById((int) $id);
        } else {
            $blog = $BlogMapper->make();
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
            $SessionHandler->unsetData('blog');
        }

        // Display
        $this->app->twig->display('admin/editBlogPost.html', ['blog' => $blog, 'title' => 'Edit Blog Post']);
    }
}
