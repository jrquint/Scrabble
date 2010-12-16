<?php

class PlacedTile extends AppModel
{
	var $name = 'PlacedTile';
	var $belongsTo = array(
		'Game',
	);
	
	function hasTile($game_id, $coord)
	{
		$r = $this->find('all', array(
			'conditions' => array(
				'game_id' => $game_id,
				'x' => $coord['x'],
				'y' => $coord['y'],
			),
		));
		return (!empty($r));
	}
	
	/**
	 * Check if all fields denoted by the given coords are empty, given game ID
	 */
	function allFieldsEmpty($game_id, $coords)
	{
		foreach ($coords as $coord)
		{
			if ($this->hasTile($game_id, $coord))
			{
				return false;
			}
		}
		
		return true;
	}
}

?>