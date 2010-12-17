<?php

class UsersController extends AppController
{
	var $name = 'Users';
	
	function index()
	{
		//die('Hello World!');
	}
	
	function login()
	{
	}
	
	function logout()
	{
		$this->redirect($this->Auth->logout());
	}
}

?>