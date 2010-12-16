<?php

class GamesController extends AppController
{
	var $name = 'Games';
	var $uses = array(
		'Game',
		'User',
		'Player',
		'PlacedTile',
		'Move',
	);
	
	function index()
	{
		$id = $this->Game->createGame(array(11,13,17,19));
		$this->Game->id = $id;
		$this->Game->playMove('KE(LLE)[y] B6 42');
		die();
	}
	
	function create()
	{
		$this->Game->createGame(array(5,7,11,13));
		die();
		
		if (!$this->data)
		{
			$this->Session->setFlash('No players given!');
		}
		
		// Let game model create game, given the ordered list of user IDs
		//$this->Game->createGame($this->data['users']);
	}
	
	function view($id = NULL)
	{
		if (!$id)
		{
			$this->Session->setFlash('Game ID not given!');
			$this->redirect('/games');
		}
		
		$this->Game->id = $id;
		$this->Game->read();
		if (!$this->Game->data)
		{
			$this->Session->setFlash('Game with ID '.$id.' does not exist!');
			$this->redirect('/games');
		}
		
		debug($this->Game->data);
		die();
	}
}

?>