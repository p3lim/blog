<?php

session_start();

require('includes/Slim/Slim.php');
require('includes/Database.php');
require('includes/Articles.php');
require('includes/Comments.php');
require('includes/Users.php');
require('includes/Flash.php');

\Slim\Slim::registerAutoLoader();
\Slim\Route::setDefaultConditions(array('id' => '\d+'));

$slim = new \Slim\Slim(array(
	'view' => new \Slim\Views\Twig()
));

$slim->view->setTemplatesDirectory('./views');

$excerpt = new Twig_SimpleFilter('excerpt', function($html){
	$document = new DOMDocument();
	libxml_use_internal_errors(true);
	$document->loadHTML('<div>' . $html . '</div>');
	libxml_clear_errors();

	return $document->saveHTML($document->getElementsByTagName('section')->item(0));
});

$sections = new Twig_SimpleFunction('sections', function($html){
	$document = new DOMDocument();
	libxml_use_internal_errors(true);
	$document->loadHTML('<div>' . $html . '</div>');
	libxml_clear_errors();

	$xpath = new DOMXpath($document);
	return $xpath->query('//div/*')->length;
});

$twig = $slim->view->getEnvironment();
$twig->addGlobal('auth', authenticated());
$twig->addFunction($sections);
$twig->addFilter($excerpt);

function authenticated(){
	return (bool) $_SESSION['authenticated'];
}

// public
$slim->get('/(page/:id)', function($id = 1) use ($slim){
	$articles = new Articles();
	$result = $articles->getAll($id);
	if((bool) $result){
		$result['pages'] = $articles->getPages();
		$slim->render('index.twig', insertFlash($result));
	} else {
		$slim->notFound();
	}
});

$slim->get('/post/:id', function($id) use ($slim){
	$articles = new Articles();
	$result = $articles->get($id);
	if((bool) $result){
		$comments = new Comments();
		$result['comments'] = $comments->get($id);
		$slim->render('post.twig', insertFlash($result));
	} else {
		$slim->notFound();
	}
});

$slim->get('/about', function() use ($slim){
	$slim->render('about.twig');
});

$slim->get('/feed', function() use ($slim){
	$articles = new Articles();
	$slim->render('feed.twig', $articles->getFeed());
});

$slim->get('/404', function() use ($slim){
	$slim->render('404.twig');
});

$slim->notFound(function() use ($slim){
	$slim->redirect('/404');
});

// private
$slim->get('/login', function() use ($slim){
	$slim->render('login.twig', insertFlash(array()));
});

$slim->get('/logout', function() use ($slim){
	if(authenticated()){
		session_destroy();
		session_start();

		addFlash('message', 'Successfully logged out');
		$slim->redirect('/');
	} else {
		$slim->notFound();
	}
});

$slim->get('/register', function() use ($slim){
	$users = new Users();
	if($users->numUsers() > 0){
		$slim->notFound();
	} else {
		$slim->render('register.twig', insertFlash(array()));
	}
});

$slim->get('/create', function() use ($slim){
	if(authenticated()){
		$slim->render('create.twig', insertFlash(array()));
	} else {
		$slim->notFound();
	}
});

$slim->get('/edit/:id', function($id) use ($slim){
	if(authenticated()){
		$articles = new Articles();
		$result = $articles->get($id);
		if((bool) $result){
			$slim->render('create.twig', insertFlash($result));
		} else {
			$slim->notFound();
		}
	} else {
		$slim->notFound();
	}
});

// api
$slim->post('/login', function() use ($slim){
	$post = $slim->request->post();
	$errors = '';

	if(empty($post['username'])){
		$errors = 'You did not specify a username';
	} elseif(empty($post['password'])){
		$errors = 'You did not specify a password';
	} else {
		$users = new Users();
		if($users->exists($post['username'], $post['password'])){
			$_SESSION['authenticated'] = true;

			addFlash('message', 'Successfully logged in');
			$slim->redirect('/');
		} else {
			$errors = 'Wrong username and/or password';
		}
	}

	if(!empty($errors)){
		addFlash('error', $errors);
		$slim->redirect('/login');
	}
});

$slim->post('/register', function() use ($slim){
	if($slim->request->isAjax()){
		$slim->halt(401);
	}

	$users = new Users();
	if($users->numUsers() > 0){
		$slim->notFound();
	} else {
		$post = $slim->request->post();
		if($users->register($post['username'], $post['password'])){
			addFlash('message', 'Successfully registered');
			$slim->redirect('/login');
		} else {
			addFlash('error', 'Something went wrong');
			$slim->redirect('/register');
		}
	}
});

$slim->post('/post', function() use ($slim){
	if(authenticated()){
		$post = $slim->request->post();

		$articles = new Articles();
		$result = $articles->create($post['title'], $post['content']);
		if(gettype($result) === 'integer'){
			if($slim->request->isAjax()){
				$slim->response->headers->set('Content-Type', 'application/json');
				$slim->response->setBody(json_encode(array(
					'message' => 'Successfully created post',
					'id' => $result
				)));
			} else {
				addFlash('message', 'Successfully created post');
				$slim->redirect('/post/' . $result);
			}
		} else {
			if($slim->request->isAjax()){
				$slim->halt(400, $result);
			} else {
				addFlash('error', $result);
				addFlashData($post);
				$slim->redirect('/create');
			}
		}
	} else {
		if($slim->request->isAjax()){
			$slim->halt(401, 'You need to be logged in to do that');
		} else {
			addFlash('error', 'You need to be logged in to do that');
			$slim->redirect('/login');
		}
	}
});

$slim->put('/post', function() use ($slim){
	if(authenticated()){
		$put = $slim->request->put();

		$articles = new Articles();
		$result = $articles->update($put['id'], $put['title'], $put['content']);
		if(gettype($result) === 'boolean'){
			if($slim->request->isAjax()){
				$slim->response->headers->set('Content-Type', 'application/json');
				$slim->response->setBody(json_encode(array(
					'message' => 'Successfully updated post',
					'id' => $put['id']
				)));
			} else {
				addFlash('message', 'Successfully updated post');
				$slim->redirect('/post/' . $put['id']);
			}
		} else {
			if($slim->request->isAjax()){
				$slim->halt(400, $result);
			} else {
				addFlash('error', $result);
				addFlashData($put);
				$slim->redirect('/edit/' . $put['id']);
			}
		}
	} else {
		if($slim->request->isAjax()){
			$slim->halt(401, 'You need to be logged in to do that');
		} else {
			addFlash('error', 'You need to be logged in to do that');
			$slim->redirect('/login');
		}
	}
});

$slim->delete('/post', function() use ($slim){
	if(authenticated()){
		$articles = new Articles();
		$result = $articles->delete($slim->request->delete('id'));
		if(gettype($result) === 'boolean'){
			$comments = new Comments();
			$result = $comments->deleteAll($slim->request->delete('id'));
			if(gettype($result) === 'boolean'){
				$slim->response->headers->set('Content-Type', 'application/json');
				$slim->response->setBody(json_encode(array('message' => 'Successfully deleted post & comments')));
			} else {
				$slim->response->headers->set('Content-Type', 'application/json');
				$slim->response->setBody(json_encode(array('message' => 'Successfully deleted post, but not comments')));
			}
		} else {
			$slim->halt(400, $result);
		}
	} else {
		$slim->halt(401, 'You need to be logged in to do that');
	}
});

$slim->post('/comment', function() use ($slim){
	if($slim->request->isAjax()){
		$slim->halt(400);
	}

	$post = $slim->request->post();
	$comments = new Comments();
	$result = $comments->create($post['id'], $post['name'], $post['email'], $post['content']);
	if(gettype($result) === 'string'){
		addFlash('error', $result);
		addFlashData($post);
	} else {
		addFlash('message', 'Successfully added comment');
	}

	$slim->redirect('/post/' . $post['id']);
});

$slim->delete('/comment', function() use ($slim){
	if(authenticated()){
		$comments = new Comments();
		$result = $comments->delete($slim->request->delete('id'));
		if(gettype($result) === 'boolean'){
			$slim->response->headers->set('Content-Type', 'application/json');
			$slim->response->setBody(json_encode(array('message' => 'Successfully deleted comment')));
		} else {
			$slim->halt(400, $result);
		}
	} else {
		$slim->halt(401, 'You need to be logged in to do that');
	}
});

$slim->post('/ban', function() use ($slim){
	if(authenticated()){
		$comments = new Comments();
		$result = $comments->ban($slim->request->post('id'));
		if(gettype($result) === 'boolean'){
			$slim->response->headers->set('Content-Type', 'application/json');
			$slim->response->setBody(json_encode(array('message' => 'Successfully banned user')));
		} else {
			$slim->halt(400, $result);
		}
	} else {
		$slim->halt(401, 'You need to be logged in to do that');
	}
});

$slim->run();
