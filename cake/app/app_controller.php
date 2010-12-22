<?php

class AppController extends Controller
{
	var $name = 'App';
	var $components = array(
		'Session',
		'Auth',
	);
	
	function beforeFilter()
	{
		App::import('Vendor', 'LetterCollection');
	}
}

?>