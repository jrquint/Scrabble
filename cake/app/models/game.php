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
		5 => 'The first word of the game must pass the center field (H8 or 8H)!',
		6 => 'Not all tiles are connected!',
		7 => 'The played word is not connected to already existing tiles!',
		8 => 'There must be at least 7 tiles remaining in the sack to be able to exchange tiles!',
		9 => 'There are no tiles on the board yet that you can use in your word!',
		10 => 'A field that was assumed to have a tile doesn\'t have one!',
		11 => 'The word extends out of the board, which is not possible!',
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
				$passes_center_field
				                := a boolean value indicating if the word passes the center field
				$mainland_connectors
								:= an array of all field coordinates that could that could imply a mainland connection
				$out_of_bounds  := a boolean value indicating if the word is out of bounds
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
		
		// First check: if word is out of bounds, then it must no be accepted whatsoever..
		if ($out_of_bounds)
		{
			$this->errorcode = 11;
			return false;
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
		$c = $this->PlacedTile->find('count', array(
			'conditions' => array(
				'PlacedTile.game_id' => $this->id,
			),
		));
		if ($c == 0)
		{
			// First word
			if (!empty($assumptions))
			{
				$this->errorcode = 9;
				return false;
			}
			if (!$passes_center_field)
			{
				$this->errorcode = 5;
				return false;
			}
		}
		else
		{
			// All fields empty?
			foreach ($tiles as $tile)
			{
				$r = $this->PlacedTile->find('count', array(
					'conditions' => array(
						'PlacedTile.game_id' => $this->id,
						'PlacedTile.x' => $tile['x'],
						'PlacedTile.y' => $tile['y'],
					),
				));
				if ($r > 0)
				{
					$this->errorcode = 4;
					return false;
				}
			}
			
			// If there are assumptions, then we can conclude that mainland
			// connection is made (assuming the assumptions are confirmed),
			// and if not, then we needn't check them in the first place!
			if (empty($assumptions))
			{
				// Check for mainland connection
				$mainland_connection = false;
				foreach ($mainland_connectors as $i => $coord)
				{
					$r = $this->PlacedTile->find('count', array(
						'conditions' => array(
							'PlacedTile.game_id' => $this->id,
							'PlacedTile.x' => $coord['x'],
							'PlacedTile.y' => $coord['y'],
						),
					));
					if ($r > 0)
					{
						$mainland_connection = true;
						break;
					}
				}
				if (!$mainland_connection)
				{
					$this->errorcode = 7;
					return false;
				}
			}
			else
			{
				// Check assumptions
				foreach ($assumptions as $coord)
				{
					$r = $this->PlacedTile->find('count', array(
						'conditions' => array(
							'PlacedTile.game_id' => $this->id,
							'PlacedTile.x' => $coord['x'],
							'PlacedTile.y' => $coord['y'],
						),
					));
					if ($r == 0)
					{
						$this->errorcode = 10;
						return false;
					}
				}
			}
		}
		
		// Calculate score
		// TODO
		$score = 1;
		
		// Put tiles to board
		foreach ($tiles as $k => $v)
		{
			$tiles[$k]['game_id'] = $this->id;
		}
		$this->PlacedTile->saveAll($tiles);
		
		// Save new rack & score
		$this->read();
		$this->Player->id = $this->data['Game']['active_player'];
		$this->Player->read();
		$this->Player->set('rack_tiles', ((string)$newrack));
		$this->Player->set('score', $this->Player->data['Player']['score'] + $score);
		$this->Player->save();
		
		// Save move
		$this->Move->create();
		$this->Move->set('game_id', $this->id);
		$this->Move->set('player_id', $this->data['Game']['active_player']);
		$this->Move->set('notation', $notation);
		$this->Move->set('score', $score);
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