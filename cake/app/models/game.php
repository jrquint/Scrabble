<?php

class Game extends AppModel
{
	var $name = 'Game';
	var $hasMany = array(
		'Player',
		'PlacedTile',
	);
	
	var $errors = array(
		0 => 'Given play notation is incorrect!',
		1 => 'You do not have the appropriate letters in your rack!',
		2 => 'You cannot play more than 7 tiles in one go!',
		3 => 'You didn\'t play any tile at all!',
		4 => 'You placed tiles on fields that are not empty!',
		5 => 'The first word of the game must pass the center field (H8)!',
		6 => 'Not all tiles are connected!',
		7 => 'The played word is not connected to already existing tiles!',
	);
	
	function play($play_notation)
	{
		$inf = ScrabbleLogic::parsePlayNotation($play_notation);
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
	function playMove($notation)
	{
		// First, let's analyse $notation
		if ($notation == 'pass')
		{
			echo 'PASS<br />';
		}
		elseif (1 == preg_match('/^exchange (?<letters>[a-zA-Z_]{1,7})$/', $notation, $matches))
		{
			$letters = strtoupper($matches['letters']);
			if (!$this->haveLetters_($letters))
			{
				return 1;
			}
			// TODO $this->exchangeRackLetters($letters);
		}
		// This is really a word play -- let's analyse it!
		// WORKS (quite sure)
		elseif (1 == preg_match('/^((?<word>[a-zA-Z\(\)\[\]]+)[ ]+(?<initpos>[0-9]{1,2}[a-oA-O]|[a-oA-O][0-9]{1,2}))([ ]+(?<score>[0-9]+))?$/', $notation, $matches))
		{
			// Catch $word and $initpos (in scrabble notation, e.g. D5 or 7K..) from regex matches
			$word    = $matches['word1'];
			$initpos = $matches['initpos1'];
			
			// Find out direction from $initpos, then reconstruct $nullpos
			$direction = in_array(substr($initpos, 1), str_split('0123456789')) ? 'vertical' : 'horizontal';
			if ($direction == 'horizontal')
			{
				$nullpos = array(
					'x' => ord(strtoupper(substr($initpos, 1))) - 65,
					'y' => substr($initpos, 0, 1),
				);
			}
			else // vertical
			{
				$nullpos = array(
					'x' => ord(strtoupper(substr($initpos, 0, 1))) - 65,
					'y' => substr($initpos, 1),
				);
			}
			
			// Get placed tiles
			$placed_tiles = $this->parsePlacedTiles_($word, $nullpos, $direction);
			
			// Do validity check
			if (!$this->validMove_($placed_tiles, $direction))
			{
				return $this->errorcode;
			}
			
			// Put tiles to board
			$this->PlacedTile->saveAll($placed_tiles);
			
			// Load active player
			$this->read();
			$this->Player->id = $this->data['Game']['active_player'];
			$this->Player->read();
			
			
			// Remove placed tile letters from rack, get new letters
			$rack = $this->Player->data['Player']['rack_tiles'];
			$letters = '';
			foreach ($placed_tiles as $placed_tile)
			{
				$letters []= strtoupper($placed_tile['letter']);
			}
			$rack = implode($this->letterSubtract(str_split($rack), $letters));
			$this->Player->set('rack_tiles', $rack);
			$this->Player->save();
			
			// Get new letters, add to active player rack
			$gameletters = str_shuffle(implode($this->getLeftOverGameLetters()));
			$rack .= substr($gameletters, 0, count($placed_tiles));
			$this->Player->set('rack_tiles', $rack);
			$this->Player->save();
			
			// Change active player
			$this->Player->read();
			$this->set('active_player', $this->Player->data['Player']['next_player_id']);
			$this->save();
			return true;
		}
		else
		{
			return 0;
		}
	}
	
	function getLeftOverGameLetters()
	{
		return $this->letterSubtract(
			$this->getAllLetters(),
			array_merge(
				$this->PlacedTile->getPlacedLetters($this->id),
				$this->Player->getGameRackLetters($this->id)
			)
		);
	}
	
	/**
	 * Subtracts $less from $more
	 * e.g. $less = array('a', 'a', 'b'); $more = array('a', 'b'); $returnvalue = array('a');
	 * returns FALSE if not subtractable
	 */
	function letterSubtract($more, $less)
	{
		$more = str_split(strtoupper(implode($more)));
		$less = str_split(strtoupper(implode($less)));
		foreach ($less as $letter)
		{
			$key = array_search($letter, $more);
			if ($key === false)
			{
				return false;
			}
			unset($more[$key]);
		}
		
		return $more;
	}
	
	// Checks if active player in this game has the given letters
	// Must be used within context of $this->id
	function haveLetters_($letters)
	{
		$rack = str_split($this->getActivePlayerRack());
		if (is_string($letters))
		{
			$letters = str_split($letters);
		}
		return ($this->letterSubtract($rack, $letters) !== false);
	}
	
	// Gets the rack of the active player as an string of letters ("_" for blank)
	// Must be used within context of $this->id
	function getActivePlayerRack()
	{
		$this->read(); // Get data of this game
		$this->Player->id = $this->data['Game']['active_player'];
		$this->Player->read(); // Get active player data
		return strtoupper($this->Player->data['Player']['rack_tiles']);
	}
	
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
		
		$tiles = str_shuffle(implode($this->getAllLetters()));
		
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
}

?>