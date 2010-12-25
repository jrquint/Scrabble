<?php

class DashboardController extends AppController
{
	var $name = 'Dashboard';
	var $uses = array(
		'Game',
		'User',
		'Player',
		'PlacedTile',
		'Move',
	);
	
	function index()
	{
		$game_ids = $this->Player->find('list', array(
			'fields' => array(
				'Player.game_id',
				'Player.game_id',
			),
			'conditions' => array(
				'Player.user_id' => $this->Session->read('Auth.User.id'),
			),
		));
		
		// Get active games
		$this->set('your_active_games', $this->Game->find('all', array(
			'conditions' => array(
				'Game.id' => $game_ids,
				'Game.status' => 'active',
			),
		)));
		
		// Get history games
		$this->set('your_history_games', $this->Game->find('all', array(
			'conditions' => array(
				'Game.id' => $game_ids,
				'Game.status !=' => 'active',
			),
		)));
	}
}

?>