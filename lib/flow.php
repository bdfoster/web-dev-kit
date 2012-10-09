<?php

//ini_set('display_errors','On');
//error_reporting(E_ALL | E_STRICT);

define('ROOT', __DIR__ . '/..');
require_once ROOT . '/lib/sag/src/Sag.php';
require_once ROOT . '/lib/bootstrap.php';

function __autoload($classname) {
	include_once(ROOT . "/classes/" . strtolower($classname) . ".php");
}

function get($route, $callback) {
	Flow::register($route, $callback, 'GET');
}

function post($route, $callback) {
	Flow::register($route, $callback, 'POST');
}

function put($route, $callback) {
	Flow::register($route, $callback, 'PUT');
}

function delete($route, $callback) {
	Flow::register($route, $callback, 'DELETE');
}

function resolve() {
	Flow::resolve();
}

class Flow {
	private static $instance;
	public static $route_found = false;
	public $route = '';
	public $method = '';
    public $content = '';
	public $vars = array();
	public $route_segments = array();
	public $route_variables = array();
	public $couch;
	
	/* The Singleton Pattern allows our class to not just be a simple
	 * class, but also to be one object. This means that each time we
	 * call our class, we are accessing a single existing object. But
	 * if that object doesn't exist yet, we automatically create it
	 * here for us to use.
	 */
	 public static function get_instance() {
		 if (!isset(self::$instance)) {
			 self::$instance = new Flow();
		 }
		 return self::$instance;
	 }
	 
	 public function __construct() {
		 $this->route = $this->get_route();
		 $this->route_segments = explode('/', trim($this->route, '/'));
		 $this->method = $this->get_method();
		 $this->couch = new Sag(COUCHDB_HOST, COUCHDB_PORT);
		 $this->couch->setDatabase(COUCHDB_DEFAULT_DB);
	 }
	 
	 protected function get_route() {
		 parse_str($_SERVER['QUERY_STRING'], $route);
		 if ($route) {
			 return '/' . $route['request'];
		 } else {
			 return '/';
		 }
	 }
	 
	 protected function get_method() {
		 if (!isset($_SERVER['REQUEST_METHOD'])) {
			 return 'GET';
		 } else {
			 return $_SERVER['REQUEST_METHOD'];
		 }
	 }
	 
	 public function set($index, $value) {
		 $this->vars[$index] = $value;
	 }
	 
	 public function render($view, $layout = "layout") {
		 $this->content = ROOT. '/views/'. $view . '.php';
		 foreach ($this->vars as $key => $value) {
			 $$key = $value;
		 }
		 
		 if (!$layout) {
			 include($this->content);
		 } else {
			 include(ROOT. '/views/' . $layout . '.php');
		 }
	 }
	 
	 /* This function has two parameters: $route and $callback. $route
	  * contains the route that we are attempting to match against the 
	  * actual route, and $callback is the function that will be 
	  * executed if the routes do indeed match. Notice that, at the
	  * start of the register function, we call for out Flow instance, 
	  * using the static::get_instance() function. This is the Singleton
	  * Pattern in action, returning the single instance of the Flow
	  * object to us. The register function then checks to see if the
	  * route that we visited through our browser matches the route that
	  * was passed into the function. If there is a match, our
	  * $route_found variable will be set to true, which will allow us
	  * to skip looking through the rest of the routes. The register
	  * function will then execute a callback function that will do the
	  * work that was defined by our route. Our Flow instance will also
	  * be passed with the callback function so that we can use it to 
	  * our advantage. If the route is not a match, we will return false
	  * so that we know the route was not a match. Later, we added a 
	  * method arguement to be passed into our register function. We
	  * then used this $method variable in our register function by
	  * adding it to the list of arguements that have to be true in
	  * order for it to be considered a match. Therefore, if the routes
	  * match, but it's a different HTTP method than expected, it will
	  * be ignored. This will allow you to create routes with the same
	  * name but act differently based on the method that is passed.
	  * Sounds just like REST, doesn't it?
	  */
	  
	 public static function register($route, $callback, $method) {
		if (!static::$route_found) {
			$flow = static::get_instance();
			$url_parts = explode('/', trim($route, '/'));
			$matched = null;

			if (count($flow->route_segments) == count($url_parts)) {
				foreach ($url_parts as $key=>$part) {
					if (strpos($part, ":") !== false) {
						// Contains a route variable
						$flow->route_variables[substr($part, 1)] = $flow->route_segments[$key];;
					} else {
						// Does not contain a route variable
						if ($part == $flow->route_segments[$key]) {
							if (!$matched) {
								// Routes match
								$matched = true;
							}
						} else {
							// Routes don't match
							$matched = false;
						}
					}
				}
			} else {
				// Routes are different lengths
				$matched = false;
			}
				
			if (!$matched || $flow->method != $method) {
				return false;
			} else {
				static::$route_found = true;
				echo $callback($flow);
			}
		}
	}

	/* Here, we created a simple function called form that serves as a 
	 * wrapper around the $_POST array, which is an array of variables
	 * passed through the HTTP POST method. This will allow us to
	 * collect variables after we POST them.
	 */
	public function form($key) {
		return $_POST[$key];
	}
	
	/* This function will soon be used everywhere to create clean links
	 * so that we can link to other resources in our application.
	 */
	public function make_route($path = '') {
		$url = explode("/", $_SERVER['PHP_SELF']);
		if ($url[1] == 'index.php') {
			return $path;
		} else {
			return '/' . $url[1] . $path;
		}
	}
	
	public function request($key) {
		return $this->route_variables[$key];
	}
	
	public function display_alert($variable = 'error') {
		if (isset($this->vars[$variable])) {
			return "<div class='alert alert-" . $variable . "'><a class='close' href='#' data-dismiss='alert'>&times;</a>" . $this->vars[$variable] . "</div>\n";
		}
	}
	
	public function redirect($path = '/') {
		header('Location: ' . $this->make_route($path));
	}
    
    public function error404() {
		$this->render('error/400');
		exit;
	}
	
	public static function resolve() {
		if(!static::$route_found) {
			$flow = static::get_instance();
			$flow->error404();
		}
	}

    public function error500($exception) {
		$this->set('exception', $exception);
		$this->render('error/500');
		exit;
	}
	
	public function validate_email($email_address) {
		if (filter_var($email_address, FILTER_VALIDATE_EMAIL)) {
			return true;
		} else {
			return false;
		}
	}
}
	
	
