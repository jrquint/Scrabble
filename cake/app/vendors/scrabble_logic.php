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
				'letters' => new LetterCollection(strtoupper($m['letters'])),
			);
		}
		elseif (preg_match('/^(?<word>[a-z\[\]\(\)]+)[ ]+(?<startat>([0-9]{1}|1[0-4]{1})[a-oA-O]|[a-oA-O]([0-9]{1}|1[0-4]{1}))([ ]+(?<score>[0-9]+))?$/i', $n, $m))
		{
			$startat_result = self::parseStartAt($m['startat']);
			$word_result = self::parseWord($m['word'], $startat_result['initpos'], $startat_result['direction']);
			$notation = preg_replace('/\[([a-zA-Z]*)\]/e', 'strtolower(\'$1\')', strtoupper($n));
			return array(
				'type'                => 'play',
				'direction'           => $startat_result['direction'],
				'initpos'             => $startat_result['initpos'],
				'tiles'               => $word_result['tiles'],
				'letters_needed'      => $word_result['letters_needed'],
				'assumptions'         => $word_result['assumptions'],
				'passes_center_field' => $word_result['passes_center_field'],
				'mainland_connectors' => $word_result['mainland_connectors'],
				'out_of_bounds'       => $word_result['out_of_bounds'],
				'notation'            => $notation,
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
					'y' => ($dir == 'horizontal' ? substr($startat, 0, 1) : substr($startat, 1)) - 1,
				),
			);
		}
		else // strlen($startat) == 3
		{
			return array(
				'direction' => $dir,
				'initpos' => array(
					'x' => ord($dir == 'horizontal' ? substr($startat, 2) : substr($startat, 0, 1)) - 65,
					'y' => ($dir == 'horizontal' ? substr($startat, 0, 2) : substr($startat, 1)) - 1,
				),
			);
		}
	}
	
	/**
	 * Parses a scrabble notation word (such as "SC(RAB)BL[ew]ORD") and
	 * returns all kinds of information about it, including:
	 * - "tiles", an array (as would be save in the placed_tiles table, except that each element misses the "game_id" field)
	 * - "assumptions", an array (fields-coordinates that are assumed to contain a permanent tile)
	 * - "letters_needed", a LetterCollection that contains all the letters needed to place this word
	 */
	static function parseWord($word, $nullpos, $direction)
	{
		// Determine variable axis based on direction
		$variable_axis = ($direction == 'horizontal') ? 'x' : 'y';
		$constant_axis = ($direction == 'horizontal') ? 'y' : 'x';
		
		// Change all '(ABC)' to '(A)(B)(C)'
		do
		{
			$tmpword = $word;
			$word = preg_replace('/\(([a-zA-Z])([a-zA-Z]+)\)/', '($1)($2)', $tmpword);
		}
		while ($word != $tmpword);
		
		// Change all 'abc' to 'ABC' and '[ABC]' to 'abc'
		$word = strtoupper($word);
		$word = preg_replace('/\[([a-zA-Z]*)\]/e', 'strtolower(\'$1\')', $word);
		
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
		$passes_center_field = false;
		$out_of_bounds = false;
		$placed_tiles = array();
		$assumptions = array();
		$letters_needed = '';
		$mainland_connectors = array();
		foreach ($parts as $i => $letter)
		{
			$pos = $nullpos;
			$pos[$variable_axis] += $i;
			if ($pos['x'] == 7 && $pos['y'] == 7)
			{
				$passes_center_field = true;
			}
			if ($pos['x'] > 14 || $pos['y'] > 14)
			{
				$out_of_bounds = true;
			}
			if (strlen($letter) == 1)
			{
				$placed_tile = array();
				$placed_tile['letter'] = $letter;
				if (ctype_lower($letter))
				{
					$placed_tile['letter'] = '_';
					$placed_tile['blankletter'] = strtoupper($letter);
				}
				$letters_needed .= $placed_tile['letter'];
				$placed_tiles []= array_merge($pos, $placed_tile);
				
				$mainland_connectors []= array(
					$variable_axis => $pos[$variable_axis],
					$constant_axis => $pos[$constant_axis] - 1,
				);
				$mainland_connectors []= array(
					$variable_axis => $pos[$variable_axis],
					$constant_axis => $pos[$constant_axis] + 1,
				);
			}
			else
			{
				$assumptions []= $pos;
			}
		}
		
		$before_first = $placed_tiles[0];
		$before_first[$variable_axis]--;
		$mainland_connectors []= $before_first;
		
		$after_last = $placed_tiles[count($placed_tiles) - 1];
		$after_last[$variable_axis]++;
		$mainland_connectors []= $after_last;
		
		// NOTE: We do not have to care about mainland connectors that are out of bounds,
		// because when they are checked, no tiles will show up, which corresponds with
		// the logical idea that they are actually not mainland connectors at all..
		
		return array(
			'tiles' => $placed_tiles,
			'assumptions' => $assumptions,
			'letters_needed' => new LetterCollection($letters_needed),
			'mainland_connectors' => $mainland_connectors,
			'out_of_bounds' => $out_of_bounds,
			'passes_center_field' => $passes_center_field,
		);
	}
}

?>