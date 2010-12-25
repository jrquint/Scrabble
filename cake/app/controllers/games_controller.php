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
		// Games overview:
		// * My Games (Active; History)
	}
	
	function create()
	{
		// arg: ordered array of users
		$this->Game->createGame(array(1,2));
		
		$this->redirect('/games/view/'.$this->Game->id);
	}
	
	function view($id = NULL)
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
		
		// Check for word submit
		if (isset($this->data))
		{
			if (!isset($this->data['play_notation']))
			{
				die('WTF');
			}
			$result = $this->Game->play($this->data['play_notation']);
			if ($result === true)
			{
				// Success
				$this->Session->setFlash('Success!');
				$this->redirect('/games/view/'.$this->Game->id);
			}
			else
			{
				// Error
				$this->Session->setFlash($this->Game->errors[$this->Game->errorcode]);
				$this->redirect('/games/view/'.$this->Game->id);
			}
		}
		
		// Get game data
		$this->set('game', $this->Game->data);
		
		// Get active player data
		$this->Player->id = $this->Game->data['Game']['active_player'];
		$this->Player->read();
		$this->set('active_player', $this->Player->data);
		
		// Get all players
		$r = $this->Player->find('all', array(
			'recursive' => 1,
			'conditions' => array(
				'Player.game_id' => $this->Game->id,
			),
		));
		$players = array();
		foreach ($r as $player)
		{
			$players[$player['User']['id']] = $player;
		}
		$this->set('players', $players);
		
		// $tiles for board element
		$this->set('tiles', $this->Game->data['PlacedTile']);
	}
}

?>