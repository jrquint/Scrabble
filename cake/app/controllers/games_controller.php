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
		$this->Game->playMove('a h7');
		//$this->Game->playMove('exchange a');
		die();
	}
	
	function create()
	{
		// arg: ordered array of users
		$this->Game->createGame(array(5,7,11,13));
		
		$this->redirect('/games/view/'.$this->Game->id);
	}
	
	function view($id = NULL)
	{
		$this->layout = 'game';
		
		// ID given?
		if (!$id)
		{
			$this->Session->setFlash('Game ID not given!');
			$this->redirect('/games');
		}
		
		// Game exists?
		$this->Game->id = $id;
		$this->Game->read();
		if (!$this->Game->data)
		{
			$this->Session->setFlash('Game with ID '.$id.' does not exist!');
			$this->redirect('/games');
		}
		$this->set('game_id', $this->Game->id);
		
		// Get active player rack
		// TODO: only if YOU ARE the active player, ..?
		$this->set('rack', $this->Game->getActivePlayerRack());
		
		// Get placed tiles
		// (Javascript only accepts the placed_tile rows, so we have to filter these out)
		$r = $this->PlacedTile->getPlacedTiles($this->Game->id);
		$tiles = array();
		foreach ($r as $tile)
		{
			// (Just in case the letters are stored lowercase -- uppercase them!)
			$tile['PlacedTile']['letter']      = strtoupper($tile['PlacedTile']['letter']);
			$tile['PlacedTile']['blankletter'] = strtoupper($tile['PlacedTile']['blankletter']);
			
			$tiles []= $tile['PlacedTile'];
		}
		$this->set('tiles', $tiles);
	}
	
	function play($id = NULL)
	{
		// ID given?
		if (!$id)
		{
			$this->Session->setFlash('Game ID not given!');
			$this->redirect('/games');
		}
		
		// Game exists?
		$this->Game->id = $id;
		$this->Game->read();
		if (!$this->Game->data)
		{
			$this->Session->setFlash('Game with ID '.$id.' does not exist!');
			$this->redirect('/games');
		}
		
		// Play notation given?
		if (!$this->data)
		{
			$this->Session->setFlash('Notation not given!');
			$this->redirect('/games/view/'.$this->Game->id);
		}
		
		// Play!
		$notation = $this->data['notation'];
		$result = $this->Game->playMove($notation);
		if ($result !== true)
		{
			// Error!
			$this->Session->setFlash('Error! '.$this->Game->errors[$result]);
			$this->redirect('/games/view/'.$this->Game->id);
		}
		
		// Done!
		$this->Session->setFlash('Success!');
		$this->redirect('/games/view/'.$this->Game->id);
	}
}

?>