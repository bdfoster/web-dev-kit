<?php

class User extends Base {
	
	protected $name;
	protected $email;
	protected $full_name;
	protected $salt;
	protected $password_sha;
	protected $roles;
	
	public function __construct() {
		
		parent::__construct('user');
		
	}
	
	public function create_account($username, $password) {
		
		$flow = new Flow();
		$flow->couch->setDatabase('_users');
		$flow->couch->login(ADMIN_USER, ADMIN_PASSWORD);
		$this->roles = array();
		$this->name = preg_replace('/[^a-z0-9-]/', '', strtolower($username));
		$this->_id = 'org.couchdb.user:' . $this->name;
		$this->salt = $flow->couch->generateIDs(1)->body->uuids[0];
		$this->password_sha = sha1($password . $this->salt);
		
		try {
			$flow->couch->put($this->_id, $this->to_json());
		} catch(SagCouchException $e) {
			if($e->getCode() == "409") {
				$flow->set('error', 'A user with this name already exists.');
				$flow->render('user/signup');
				exit;
			}
		}
	}
	
	public function login($password) {
		
		$flow = new Flow;
		$flow->couch->setDatabase('_users');
		
		try {
			$flow->couch->login($this->name, $password, Sag::$AUTH_COOKIE);
			session_start();
			$_SESSION['username'] = $flow->couch->getSession()->body->userCtx->name;
			session_write_close();
		} catch (SagCouchException $e) {
			if ($e->getCode() =="401") {
				$flow->set('error', 'Username and/or Password do not match.');
				$flow->render('user/login');
				exit;
			}
			$flow->error500();
		}
	}
	
	public static function logout() {
		
		$flow = new Flow;
		$flow->couch->login(null, null);
		session_start();
		session_destroy();
	}

	public static function current_user() {
		
		session_start();
		return $_SESSION['username'];
		session_write_close();
	}
	
	public static function is_authenticated() {
		
		if (self::current_user()) {
			return true;
		} else {
			return false;
		} 
	}
}
