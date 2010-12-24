<?php

class Game extends AppModel
{
	var $name = 'Game';
	var $hasMany = array(
		'Player',
		'PlacedTile',
		'Move',
	);
	
	public $errorcode = false;
	public $errors = array(
		0 => 'Given play notation is incorrect!',
		1 => 'You do not have the appropriate letters in your rack!',
		2 => 'You cannot play more than 7 tiles in one go!',
		3 => 'You didn\'t play any tile at all!',
		4 => 'You placed tiles on fields that are not empty!',
		5 => 'The first word of the game must pass the center field (H8)!',
		6 => 'Not all tiles are connected!',
		7 => 'The played word is not connected to already existing tiles!',
		8 => 'There must be at least 7 tiles remaining in the sack to be able to exchange tiles!'
	);
	
	/**
	 * Plays a turn given a play notation string.
	 * Must be used in a game context.
	 */
	public function play($play_notation)
	{
		$inf = ScrabbleLogic::parsePlayNotation($play_notation);
		if ($inf === false)
		{
			$this->errorcode = 0; // Error in notation
			return false;
		}
		
		extract($inf);
		/*
			Pass?
				$type           := 'pass'
			Exchange?
				$type           := 'exchange'
				$letters        := a LetterCollection of letters to be exchanged
			Play?
				$type           := 'play'
				$direction      := 'horizontal', 'vertical'
				$initpos        := array('x' => ?, 'y' => ?)
				$tiles          := array of "placed tile" row elements, although each missing the "game_id" field
				$letters_needed := a LetterCollection of necessary letters for this play
				$assumptions    := an array of coordinates of fields that are assumed to have permanent tiles
				$notation       := the notation initially passed to ScrabbleLogic::parsePlayNotation()
		*/
		
		// If Pass or Exchange, delegate to their resp. methods..
		if ($type == 'pass')
		{
			return $this->_pass();
		}
		elseif ($type == 'exchange')
		{
			return $this->_exchange($letters);
		}
		
		// Get player rack, and check if player has necessary letters
		$this->read();
		$this->Player->id = $this->data['Game']['active_player'];
		$this->Player->read();
		$rack = new LetterCollection($this->Player->data['Player']['rack_tiles']);
		$rack = $rack->removeCollection($letters_needed);
		if (!$rack->valid())
		{
			$this->errorcode = 1;
			return false;
		}
		
		// Calculate new rack, but do not save yet
		$leftover = $this->_getLeftOverGameLetters();
		$newrack = $rack->addCollection(new LetterCollection(substr(str_shuffle((string)$leftover), 0, min($letters_needed->size(), $leftover->size()))));
		
		// Valid move?
		// TODO
		
		// Calculate score
		// TODO
		
		// Put tiles to board
		foreach ($tiles as $k => $v)
		{
			$tiles[$k]['game_id'] = $this->id;
		}
		$this->PlacedTile->saveAll($tiles);
		
		// Save new rack
		$this->read();
		$this->Player->id = $this->data['Game']['active_player'];
		$this->Player->read();
		$this->Player->set('rack_tiles', ((string)$newrack));
		$this->Player->save();
		
		// Save move
		$this->Move->create();
		$this->Move->set('game_id', $this->id);
		$this->Move->set('player_id', $this->data['Game']['active_player']);
		$this->Move->set('notation', $notation);
		$this->Move->save();
		
		// Change active player
		$this->_changeActivePlayer();
		
		// Success
		return true;
	}
	
	/**
	 * Passes a turn within a game context.
	 */
	private function _pass()
	{
		// Save move
		$this->Move->create();
		$this->Move->set('game_id', $this->id);
		$this->Move->set('player_id', $this->data['Game']['active_player']);
		$this->Move->set('notation', 'pass');
		$this->Move->save();
		
		// Change active player
		$this->_changeActivePlayer();
		
		// Success
		return true;
	}
	
	/**
	 * Exchanges letters within a game context.
	 * @param {LetterCollection} $letters The collection of letters that are to be exchanged.
	 */
	private function _exchange($letters)
	{
		// Exchange letters
		$this->read();
		$this->Player->id = $this->data['Game']['active_player'];
		$this->Player->read();
		$rack = new LetterCollection($this->Player->data['Player']['rack_tiles']);
		$rack = $rack->removeCollection($letters);
		if (!$rack->valid())
		{
			$this->errorcode = 1;
			return false;
		}
		$leftover = $this->_getLeftOverGameLetters();
		if ($leftover->size() < 7)
		{
			$this->errorcode = 8;
			return false;
		}
		// And now we have the currently exchanged letters again..
		$leftover = $leftover->addCollection($letters);
		
		// Add new random letters
		$newrack = $rack->addCollection(new LetterCollection(substr(str_shuffle((string)$leftover), 0, $letters->size())));
		$this->Player->set('rack_tiles', $newrack);
		$this->Player->save();
		
		// Save move
		$this->Move->create();
		$this->Move->set('game_id', $this->id);
		$this->Move->set('player_id', $this->data['Game']['active_player']);
		$this->Move->set('notation', 'exchange '.((string)$letters));
		$this->Move->save();
		
		// Change active player
		$this->_changeActivePlayer();
		
		// Success
		return true;
	}
	
	/**
	 * Gets the leftover game letters within game context, which are:
	 * All scrabble letters - placed letters - rack letters of all players
	 * .. as a LetterCollection.
	 */
	private function _getLeftOverGameLetters()
	{
		$placed_letters = new LetterCollection(implode($this->PlacedTile->find('list', array(
			'fields' => array(
				'PlacedTile.letter',
				'PlacedTile.letter',
			),
			'conditions' => array(
				'PlacedTile.game_id' => $this->id,
			),
		))));
		$rack_letters = $this->Player->getGameRackLetters($this->id);
		
		$leftover = LetterCollection::getScrabbleCollection();
		$leftover = $leftover->removeCollection($placed_letters);
		$leftover = $leftover->removeCollection($rack_letters);
		return $leftover;
	}
	
	/**
	 * Changes the active player within a game context.
	 */
	private function _changeActivePlayer()
	{
		// Get next player id, then save it to this game
		$this->read();
		$this->Player->id = $this->data['Game']['active_player'];
		$this->Player->read();
		$this->set('active_player', $this->Player->data['Player']['next_player_id']);
		$this->save();
		
		// Success
		return true;
	}
	
	// Plays move on current game by current player
	// (Must only be called when $this->id is set)
	/*
		switch? Pass
		switch? Move
			# Get $word, $nullpos, $direction
			# Get $placed_tiles
			# Checks:
				# All fields empty?
				# In rack of active player?
				# All connected?
				# Connected to mainland? / First word?
			# Put tiles to board
			# Change active player
	*/
	
	// Checks validity of given "placed tiles" within the context of current game and active player
	/*
		WORKS! (quite sure..)
		# Checks:
			# numTiles correct?
			# All fields empty?
			# In rack of active player?
			# All in one dimension?
			# All connected?
			# Connected to mainland? / First word?
	*/
	private function validMove_($placed_tiles, $direction)
	{
		// numTiles correct?
		// WORKS
		if (count($placed_tiles) > 7)
		{
			// Error: too many tiles placed -- it is not possible for the player to play so many tiles!
			$this->errorcode = 2;
			return false;
		}
		if (count($placed_tiles) < 1)
		{
			// Error: not one tile placed!
			$this->errorcode = 3;
			return false;
		}
		
		// All fields empty?
		// WORKS
		if (!$this->PlacedTile->allFieldsEmpty($this->id, $placed_tiles))
		{
			$this->errorcode = 4;
			return false;
		}
		
		// In rack of active player?
		// WORKS
		$rack_tiles = $this->getActivePlayerRack();
		$letters = array();
		foreach ($placed_tiles as $placed_tile)
		{
			$letters []= $placed_tile['letter'];
		}
		if (!$this->haveLetters_($letters))
		{
			$this->errorcode = 1;
			return false;
		}
		
		// All in one dimension?
		// WORKS:
		// Not nessecary, because of where $placed_tiles comes from ($this->playMove)
		
		// All connected?
		// Connected to mainland? / First word?
		// (I THINK this works..)
		$mainland_connection = false;
		if ($this->PlacedTile->getNumPlayedTiles($this->id) == 0)
		{
			// This is the first word -- therefore it must pass (7,7)
			// We do not have to look for mainland anymore..
			foreach ($placed_tiles as $coord)
			{
				if ($coord['x'] == 7 && $coord['y'] == 7)
				{
					$mainland_connection = true;
				}
			}
			if (!$mainland_connection)
			{
				$this->errorcode = 5;
				return false;
			}
		}
		$variable_axis = ($direction == 'horizontal') ? 'x' : 'y';
		$constant_axis = ($direction == 'horizontal') ? 'y' : 'x';
		for ($i = 0; $i < count($placed_tiles) - 1; $i++)
		{
			if ($placed_tiles[$i][$variable_axis] < $placed_tiles[$i+1][$variable_axis] - 1)
			{
				// --> there are missing placed tiles here
				// --> these were assumed to be permanent tiles -- let's check them
				for ($checkvar = $placed_tiles[$i][$variable_axis] + 1; $checkvar <= $placed_tiles[$i+1][$variable_axis] - 1; $checkvar++)
				{
					$coord = array(
						$variable_axis => $checkvar,
						$constant_axis => $placed_tiles[0][$constant_axis],
					);
					if (!$this->PlacedTile->hasTile($this->id, $coord))
					{
						$this->errorcode = 6;
						return false;
					}
					$mainland_connection = true;
				}
			}
		}
		// If we haven't found a mainland connection yet, then we'll
		// have to do the tedious task of checking all neighbours anyway...
		if (!$mainland_connection)
		{
			foreach ($placed_tiles as $coord)
			{
				if (
					$this->PlacedTile->hasTile($this->id, array('x' => $coord['x']-1, 'y' => $coord['y']  )) ||
					$this->PlacedTile->hasTile($this->id, array('x' => $coord['x']+1, 'y' => $coord['y']  )) ||
					$this->PlacedTile->hasTile($this->id, array('x' => $coord['x']  , 'y' => $coord['y']-1)) ||
					$this->PlacedTile->hasTile($this->id, array('x' => $coord['x']  , 'y' => $coord['y']+1))
				)
				{
					$mainland_connection = true;
					continue;
				}
			}
			if (!$mainland_connection)
			{
				$this->errorcode = 7;
				return false;
			}
		}
		
		return true;
	}
	
	// Creates a new game with given, ordered, set of users as players
	// Returns new game ID
	function createGame($users)
	{
		// Create game entry
		$this->create();
		$this->set('status', 'active');
		$this->set('player_order', implode(',', $users));
		$this->save();
		
		$tiles = str_shuffle((string)LetterCollection::getScrabbleCollection());
		
		// Create player entries, with their rack tiles
		foreach ($users as $i => $user_id)
		{
			$this->Player->create();
			$this->Player->set('user_id', $user_id);
			$this->Player->set('game_id', $this->id);
			$this->Player->set('rack_tiles', substr($tiles, $i * 7, 7));
			$this->Player->set('score', 0);
			$this->Player->save();
			if ($i == 0)
			{
				// Set first player as active player
				$this->set('active_player', $this->Player->id);
				$this->save();
			}
		}
		// For each player, assign "next player"
		$player_ids = array_values($this->Player->find('list', array(
			'fields' => array(
				'Player.user_id',
				'Player.id',
			),
			'conditions' => array(
				'game_id' => $this->id,
			),
			'order' => 'Player.id ASC',
		)));
		foreach ($player_ids as $i => $player_id)
		{
			$this->Player->id = $player_id;
			$this->Player->set('next_player_id', $player_ids[($i+1) % count($player_ids)]);
			$this->Player->save();
		}
		
		return $this->id;
	}
	
	/**
	 * Dumps all information on game within game context
	 */
	public function dump()
	{
		$this->read();
		echo '<br /><span style="color:#c00;">Game Dump:</span><br />';
		echo '| ID: '.$this->data['Game']['id'].'<br />';
		echo '| Active Player: '.$this->data['Game']['active_player'].'<br />';
		
		echo '| Players:<br />';
		$player_ids = array_values($this->Player->find('list', array(
			'fields' => array(
				'Player.user_id',
				'Player.id',
			),
			'conditions' => array(
				'game_id' => $this->id,
			),
			'order' => 'Player.id ASC',
		)));
		foreach ($player_ids as $player_id)
		{
			$this->Player->id = $player_id;
			$this->Player->read();
			echo '|  - '.$this->Player->data['Player']['id'].': User('.$this->Player->data['Player']['user_id'].') Rack('.$this->Player->data['Player']['rack_tiles'].')<br />';
		}
		
		echo '| Board:<br />';
		for ($y = 0; $y < 15; $y++)
		{
			echo '| ';
			for ($x = 0; $x < 15; $x++)
			{
				$r = $this->PlacedTile->find('first', array(
					'recursive' => 0,
					'conditions' => array(
						'PlacedTile.game_id' => $this->id,
						'PlacedTile.x' => $x,
						'PlacedTile.y' => $y,
					),
				));
				if (empty($r))
				{
					echo ' . ';
				}
				else
				{
					if ($r['PlacedTile']['letter'] == '_')
					{
						echo '['.strtolower($r['PlacedTile']['blankletter']).']';
					}
					else
					{
						echo ' '.strtoupper($r['PlacedTile']['letter']).' ';
					}
				}
			}
			echo '<br />';
		}
	}
}

?>