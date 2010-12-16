<?php

class Player extends AppModel
{
	var $name = 'Player';
	var $hasMany = array(
		'Move',
	);
	var $belongsTo = array(
		'Game',
		'User',
	);
	
	function getGameRackLetters($game_id)
	{
		$player_racks = $this->find('list', array(
			'fields' => array(
				'Player.id',
				'Player.rack_tiles',
			),
			'conditions' => array(
				'Player.game_id' => $game_id,
			),
		));
		$letters = '';
		foreach ($player_racks as $rack)
		{
			$letters .= $rack;
		}
		
		return str_split($letters);
	}
}

?>