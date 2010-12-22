<?php

class DashboardController extends AppController
{
	var $name = 'Dashboard';
	var $uses = array();
	
	function index()
	{
		echo '<pre>';
		$a = new LetterCollection('abcc');
		$a->dump();
		$b = new LetterCollection('dddccc');
		$b->dump();
		$c = $a->removeCollection($b);
		$c->dump();
		echo $c->size('neg');
		die('test');
	}
}

?>