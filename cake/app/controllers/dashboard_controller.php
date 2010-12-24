<?php

class DashboardController extends AppController
{
	var $name = 'Dashboard';
	var $uses = array();
	
	function index()
	{
		echo '<pre>';
		/* Test ScrabbleLogic */
		debug(ScrabbleLogic::parsePlayNotation('pass'));
		debug(ScrabbleLogic::parsePlayNotation('a'));
		debug(ScrabbleLogic::parsePlayNotation('exchange abcd'));
		debug(ScrabbleLogic::parsePlayNotation('exchange aab'));
		debug(ScrabbleLogic::parsePlayNotation('exchange aab6'));
		debug(ScrabbleLogic::parsePlayNotation('exchange '));
		debug(ScrabbleLogic::parsePlayNotation('exchange'));
		debug(ScrabbleLogic::parsePlayNotation('ra[b](lle) 7e'));
		debug(ScrabbleLogic::parsePlayNotation('[rab](l)le 7'));
		debug(ScrabbleLogic::parsePlayNotation('(r)a[b]lle h9 42'));
		debug(ScrabbleLogic::parsePlayNotation('sc(r)a[b]lle h9 42'));
		debug(ScrabbleLogic::parsePlayNotation('sc(r)a[b]lle 13h 42'));
		debug(ScrabbleLogic::parsePlayNotation('sc(r)a[b]lle h13 42'));
		debug(ScrabbleLogic::parsePlayNotation('sc(r)a[b]lle 16h 42'));
		debug(ScrabbleLogic::parsePlayNotation('sc(r)a[b]lle h23 42'));
		
		/* Test LetterCollection
		$a = new LetterCollection('abcc');
		$a->dump();
		$b = new LetterCollection('dddccc');
		$b->dump();
		$c = $a->removeCollection($b);
		$c->dump();
		echo $c->size('neg');
		*/
		die();
	}
}

?>