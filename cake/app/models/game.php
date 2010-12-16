<?php

class Game extends AppModel
{
	var $name = 'Game';
	var $hasMany = array(
		'Player',
		'PlacedTile',
	);
	
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
				# Connected to mainland?
	*/
	function playMove($notation)
	{
		// First, let's analyse $notation
		if ($notation == 'pass')
		{
			echo 'PASS<br />';
		}
		// This is really a word play -- let's analyse it!
		elseif (1 == preg_match('/^(?<word>[a-zA-Z\(\)\[\]]+)[ ]+(?<initpos>[0-9]{1,2}[a-oA-O]|[a-oA-O][0-9]{1,2})([ ]+(?<score>[0-9]+))?$/', $notation, $matches))
		{
			// Catch $word and $initpos (in scrabble notation, e.g. D5 or 7K..) from regex matches
			$word    = $matches['word'];
			$initpos = $matches['initpos'];
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
			$placed_tiles = $this->parsePlacedTiles_($word, $nullpos, $direction);
			
			if (!$this->validMove_($placed_tiles, $direction))
			{
				die('INVALID, because: ' . $this->invalidity);
			}
			else
			{
				echo 'valid:';
			}
		}
		else
		{
			echo 'INVALID<br />';
		}
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
			# Connected to mainland?
	*/
	private function validMove_($placed_tiles, $direction)
	{
		echo 'Start checking...<br />';
		
		// numTiles correct?
		// WORKS
		if (count($placed_tiles) > 7)
		{
			// Error: too many tiles placed -- it is not possible for the player to play so many tiles!
			$this->invalidity = 'E: #tiles>7';
			return false;
		}
		if (count($placed_tiles) < 1)
		{
			// Error: not one tile placed!
			$this->invalidity = 'E: #tiles<7';
			return false;
		}
		
		// All fields empty?
		// WORKS
		if (!$this->PlacedTile->allFieldsEmpty($this->id, $placed_tiles))
		{
			$this->invalidity = 'E: !(fields=empty)';
			return false;
		}
		
		// In rack of active player?
		// WORKS
		$this->read();
		$this->Player->id = $this->data['Game']['active_player'];
		$this->Player->read();
		$rack_tiles = strtoupper($this->Player->data['Player']['rack_tiles']);
		foreach ($placed_tiles as $placed_tile)
		{
			if (strpos($rack_tiles, $placed_tile['letter']) === false)
			{
				$this->invalidity = 'E: letter!inrack ? rack:'.$rack_tiles.' ^ letter:'.$placed_tile['letter'];
				return false;
			}
			$pos = strpos($rack_tiles, $placed_tile['letter']);
			$rack_tiles = substr($rack_tiles, 0, $pos - 1) . substr($rack_tiles, $pos);
		}
		
		// All in one dimension?
		// WORKS:
		// Not nessecary, because of where $placed_tiles comes from ($this->playMove)
		
		// All connected?
		// Connected to mainland?
		// (I THINK this works..)
		$mainland_connection = false;
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
						$this->invalidity = 'E: !tile ? ('.$coord['x'].','.$coord['y'].')';
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
				if ($this->PlacedTile->hasTile($this->id, array('x' => $coord['x']  , 'y' => $coord['y']  )))
					continue;
				if ($this->PlacedTile->hasTile($this->id, array('x' => $coord['x']+1, 'y' => $coord['y']  )))
					continue;
				if ($this->PlacedTile->hasTile($this->id, array('x' => $coord['x']  , 'y' => $coord['y']+1)))
					continue;
				if ($this->PlacedTile->hasTile($this->id, array('x' => $coord['x']+1, 'y' => $coord['y']+1)))
					continue;
			}
			$this->invalidity = 'E: !mainland_connection';
			return false;
		}
		
		return true;
	}
	
	// Parses a scrabble notation word (such as "SC(RAB)BL[ew]ORD") and
	// returns "placed tiles" array (as would be save in the placed_tiles table)
	private function parsePlacedTiles_($word, $nullpos, $direction)
	{
		// Determine variable axis based on direction
		$variable_axis = ($direction == 'horizontal') ? 'x' : 'y';
		
		// Change all '(ABC)' to '(A)(B)(C)'
		do
		{
			$tmpword = $word;
			$word = preg_replace('/\(([a-zA-Z])([a-zA-Z]+)\)/', '($1)($2)', $tmpword);
		}
		while ($word != $tmpword);
		
		// Change all 'abc' to 'ABC' and '[ABC]' to 'abc'
		$word = strtoupper($word);
		$word = preg_replace('/\[([a-zA-Z]*)\]/e', 'strtolower(\'$1\')',  $word);
		
		// Change 'ABC(D)(E)FghIJ' to array('A', 'B', 'C', '(D)', '(E)', 'F', 'g', 'h', 'I', 'J')
		$parts = array();
		while ($word != '')
		{
			if (substr($word, 0, 1) == '(') {
				$parts []= substr($word, 0, 3);
				$word = substr($word, 3);
			} else {
				$parts []= substr($word, 0, 1);
				$word = substr($word, 1);
			}
		}
		
		// Get placed tiles
		$placed_tiles = array();
		foreach ($parts as $i => $letter)
		{
			$pos = $nullpos;
			$pos[$variable_axis] += $i;
			$placed_tile = array(
				'game_id' => $this->id,
				'x' => $pos['x'],
				'y' => $pos['y'],
			);
			if (strlen($letter) == 1)
			{
				$placed_tile['letter'] = $letter;
				if (ctype_lower($letter))
				{
					$placed_tile['letter'] = '_';
					$placed_tile['blankletter'] = strtoupper($letter);
				}
				$placed_tiles []= $placed_tile;
			}
		}
		
		return $placed_tiles;
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
		
		$tiles = str_shuffle(implode('', array(
			'__',
			'eeeeeeeeeeee',
			'aaaaaaaaa',
			'iiiiiiiii',
			'oooooooo',
			'nnnnnnnn',
			'rrrrrr',
			'tttttt',
			'llll',
			'ssss',
			'uuuu',
			'dddd',
			'ggg',
			'bb',
			'cc',
			'mm',
			'rr',
			'ff',
			'hh',
			'vv',
			'ww',
			'yy',
			'k',
			'j',
			'x',
			'q',
			'z',
		)));
		
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
		
		return $this->id;
	}
}

?>