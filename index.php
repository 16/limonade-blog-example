<?php

# 0. Loading limonade framework
require_once('lib/limonade.php');

# 1. Setting global options of our application
function configure()
{
  # A. Setting environment
  $localhost = preg_match('/^localhost(\:\d+)?/', $_SERVER['HTTP_HOST']);
  $env =  $localhost ? ENV_DEVELOPMENT : ENV_PRODUCTION;
  option('env', $env);
  
  # B. Initiate db connexion
	$dsn = $env == ENV_PRODUCTION ? 'sqlite:db/prod.db' : 'sqlite:db/dev.db';
	try
	{
	  $db = new PDO($dsn);
	}
	catch(PDOException $e)
	{
	  halt("Connexion failed: ".$e); # raises an error / renders the error page and exit.
	}
	$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
	option('db_conn', $db);
	
	# C. Other options
	setlocale(LC_TIME, "fr_FR");
}


# 2. Setting code that will be executed bfore each controller function
function before()
{
  layout('layouts/default.html.php');
}

# 3. Defining routes and controllers
# ----------------------------------------------------------------------------
# RESTFul map:
#
#  HTTP Method |  Url path         |  Controller function
# -------------+-------------------+-------------------------------------------
#   GET        |  /posts           |  blog_posts_index
#   GET        |  /posts/:id       |  blog_posts_show 
#   GET        |  /posts/new       |  blog_posts_new 
#   POST       |  /posts           |  blog_posts_create
#   GET        |  /posts/:id/edit  |  blog_posts_edit 
#   PUT        |  /posts/:id       |  blog_posts_update
#   DELETE     |  /posts/:id       |  blog_posts_destroy
#   GET        |  /                |  blog_posts_home (redirect to /posts)
# -------------+-------------------+-------------------------------------------
#

# matches GET /
dispatch('/', 'blog_posts_home');
  function blog_posts_home()
  {
    redirect(url_for('posts')); # redirects to the index
  }

# matches GET /posts  
dispatch('/posts', 'blog_posts_index');
  function blog_posts_index()
  {
    $posts = post_find_all();
    set('posts', $posts); # passing posts to the view
    return html('posts/index.html.php'); # rendering HTML view
  }

# matches GET /posts/new
# must be written before the /posts/:id route
dispatch('/posts/new', 'blog_posts_new');
  function blog_posts_new()
  { 
    # passing an empty post to the view
    set('post', array('id'=>'', 'title'=>'Your title here...', 'body'=>'Your content...'));
    return html('posts/new.html.php'); # rendering view
  }

# matches GET /posts/1  
dispatch('/posts/:id', 'blog_posts_show');
  function blog_posts_show()
  { 
    if( $post = post_find(params('id')) )
    {
      set('post', $post); # passing the post the the view
      return html('posts/show.html.php'); # rendering the view
    }
    else
    {
      halt(NOT_FOUND, "This post doesn't exists"); # raises error / renders an error page
    }
    
  }
  
# matches POST /posts
dispatch_post('/posts', 'blog_posts_create');
  function blog_posts_create()
  { 
    if($post_id = post_create($_POST['post']))
    {
      redirect(url_for('posts', $post_id)); # redirects to the show page of this newly created post
    }
    else
    {
      halt(SERVER_ERROR, "AN error occured while trying to create a new post"); # raises error / renders an error page
    }
  }
  
# matches GET /posts/1/edit  
dispatch('/posts/:id/edit', 'blog_posts_edit');
  function blog_posts_edit()
  {
    if($post = post_find(params('id')))
    {
      set('post', $post); # passing the post the the view
      return html('posts/edit.html.php'); # rendering the edit view, with its form
    }
    else
    {
      halt(NOT_FOUND, "This post doesn't exists. Can't edit it."); # raises error / renders an error page
    }
  }
  
# matches PUT /posts/1
dispatch_put('/posts/:id', 'blog_posts_update');
  function blog_posts_update()
  {
    $post_id = params('id');
    if(post_update($post_id, $_POST['post']))
    {
      redirect(url_for('posts', $post_id)); # redirects to this freshly just updated post
    }
    else
    {
      halt(SERVER_ERROR, "An error occured while trying to update post ".$post_id); # raises error / renders an error page
    }
  }
  
# matches DELETE /posts/1
dispatch_delete('/posts/:id', 'blog_posts_destroy');
  function blog_posts_destroy()
  {
    $post_id = params('id');
    if($post = post_destroy($post_id))
    {
      redirect(url_for('posts')); # redirects to the index
    }
    else
    {
      halt(SERVER_ERROR, "An error occured while trying to destroy post ".$post_id); # raises error / renders an error page
    }
  }

# 4. Running the limonade blog app
run();

?>