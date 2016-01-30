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
}
