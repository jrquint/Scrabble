<?php

/**
 * The ScrabbleLogic class contains a set of useful static methods
 * to perform scrabble logic.
 */
abstract class ScrabbleLogic
{
	/**
	 * Parses a play notation, then returns an array with play information,
	 * or FALSE if invalid or error
	 */
	static function parsePlayNotation($n)
	{
		debug($n);
		if ($n == 'pass')
		{
			return array('type' => 'pass');
		}
		elseif (preg_match('/^exchange([ ]+(?<letters>[a-zA-Z_]*))?$/', $n, $m))
		{
			if (empty($m['letters']))
			{
				return array('type' => 'pass');
			}
			return array(
				'type' => 'exchange',
				'letters' => strtoupper($m['letters']),
			);
		}
		elseif (preg_match('/^(?<word>[a-z\[\]\(\)]+)[ ]+(?<startat>([0-9]{1}|1[0-4]{1})[a-oA-O]|[a-oA-O]([0-9]{1}|1[0-4]{1}))([ ]+(?<score>[0-9]+))?$/i', $n, $m))
		{
			$startat_result = self::parseStartAt($m['startat']);
			$word_result = self::parseWord($m['word'], $startat_result['initpos'], $startat_result['direction']);
			return array(
				'type' => 'play',
				'direction' => $startat_result['direction'],
				'initpos' => $startat_result['initpos'],
				'tiles' => $word_result['tiles'],
				'assumptions' => $word_result['assumptions'],
			);
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Parses a "startat" coordinate, returns the initial (x,y) position and direction.
	 * Assumes "startat" coordinate is a valid scrabble coordinate.
	 */
	static function parseStartAt($startat)
	{
		$startat = strtoupper($startat);
		$dir = (ord($startat) >= 48 && ord($startat) <= 57) ? 'horizontal' : 'vertical';
		if (strlen($startat) == 2)
		{
			return array(
				'direction' => $dir,
				'initpos' => array(
					'x' => ord($dir == 'horizontal' ? substr($startat, 1) : substr($startat, 0, 1)) - 65,
					'y' => ($dir == 'horizontal' ? substr($startat, 0, 1) : substr($startat, 1)),
				),
			);
		}
		else // strlen($startat) == 3
		{
			return array(
				'direction' => $dir,
				'initpos' => array(
					'x' => ord($dir == 'horizontal' ? substr($startat, 2) : substr($startat, 0, 1)) - 65,
					'y' => ($dir == 'horizontal' ? substr($startat, 0, 2) : substr($startat, 1)),
				),
			);
		}
	}
	
	/**
	 * Parses a scrabble notation word (such as "SC(RAB)BL[ew]ORD") and
	 * returns "tiles" array (as would be save in the placed_tiles table)
	 * and "assumptions" array (fields that are assumed to contain a permanent tile)
	 */
	static function parseWord($word, $nullpos, $direction)
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
		$assumptions = array();
		foreach ($parts as $i => $letter)
		{
			$pos = $nullpos;
			$pos[$variable_axis] += $i;
			$coord = array(
				'x' => $pos['x'],
				'y' => $pos['y'],
			);
			if (strlen($letter) == 1)
			{
				$placed_tile = array();
				$placed_tile['letter'] = $letter;
				if (ctype_lower($letter))
				{
					$placed_tile['letter'] = '_';
					$placed_tile['blankletter'] = strtoupper($letter);
				}
				$placed_tiles []= array_merge($coord, $placed_tile);
			}
			else
			{
				$assumptions []= $coord;
			}
		}
		
		return array(
			'tiles' => $placed_tiles,
			'assumptions' => $assumptions,
		);
	}
}

?>