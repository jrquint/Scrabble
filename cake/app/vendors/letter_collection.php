<?php

/**
 * A LetterCollection object handles a collection of letters,
 * as is often done in a Scrabble game. A player's rack,
 * the remaining letters, the letters of a played word, are
 * all examples of functions of this class.
 * The class defines methods for letter and collection addition/
 * removal, and methods to check validity and size.
 * 
 * @class LetterCollection
 * @author kelleyvanevert
 */
class LetterCollection
{
	protected $coll;
	
	/**
	 * Constructs a new LetterCollection object by a string of letters
	 */
	public function __construct($letterstring = '')
	{
		$this->coll = array_combine(
			str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ_'),
			array_fill(0, 27, 0)
		);
		if (!empty($letterstring))
		{
			$letters = str_split($letterstring);
			foreach ($letters as $letter)
			{
				$this->add($letter);
			}
		}
	}
	
	/**
	 * Returns the standard scrabble collection
	 */
	public static function getScrabbleCollection()
	{
		return new LetterCollection('eeeeeeeeeeeeaaaaaaaaaiiiiiiiiioooooooonnnnnnrrrrrrttttttllllssssuuuuddddgggbbccmmppffhhvvwwyykjxqz__');
	}
	
	/**
	 * Adds the specified letter, optionally multiple times
	 */
	public function add($letter, $num = 1)
	{
		$letter = strtoupper($letter);
		$this->coll[$letter] += $num;
	}
	
	/**
	 * Removes the specified letter, optionally multiple times
	 */
	public function remove($letter, $num = 1)
	{
		$this->add($letter, -$num);
	}
	
	/**
	 * Returns a new collection representing the union of this
	 * collection and given collection.
	 * This collection remains unmodified
	 */
	public function addCollection($lc)
	{
		$new = clone $this;
		foreach ($lc->coll as $letter => $num)
		{
			$new->add($letter, $num);
		}
		return $new;
	}
	
	/**
	 * Returns a new collection representing this collection
	 * minus the given collection.
	 * This collection remains unmodified
	 */
	public function removeCollection($lc)
	{
		$new = clone $this;
		foreach ($lc->coll as $letter => $num)
		{
			$new->remove($letter, $num);
		}
		return $new;
	}
	
	/**
	 * Calculates the size of this collection.
	 * Optionally, by specifying $type as 'pos' or 'neg',
	 * one can only calculate the amount of letters actually
	 * "in the collection" resp. "not in the collection"
	 */
	public function size($type = 'both')
	{
		if ($type == 'neg')
		{
			$size = 0;
			foreach ($this->coll as $letter => $num)
			{
				if ($num < 0)
				{
					$size -= $num;
				}
			}
			return $size;
		}
		elseif ($type == 'pos')
		{
			$size = 0;
			foreach ($this->coll as $letter => $num)
			{
				if ($num >= 0)
				{
					$size += $num;
				}
			}
			return $size;
		}
		else // both
		{
			$size = 0;
			foreach ($this->coll as $letter => $num)
			{
				$size += $num;
			}
			return $size;
		}
	}
	
	/**
	 * Checks whether this collection contains the given collection
	 */
	public function contains($lc)
	{
		$new = $this->removeCollection($lc);
		return $new->valid();
	}
	
	/**
	 * Checks whether this collection is valid.
	 * A collection is invalid if it contains
	 * letter "not actually in the collection",
	 * e.g. 'letter "K" occurs in this collection -4 times'
	 */
	public function valid()
	{
		foreach ($this->coll as $letter => $num)
		{
			if ($num < 0)
			{
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Returns the string representation of this collection.
	 * This method should really be used after having verified
	 * validity of the collection by using $collection->valid(),
	 * else bugs might occur..
	 * 
	 * Examples:
	 *   1 A, 2 B's and 3 C's --> "ABBCCC"
	 *   1 A, 2 B's and -3 C's --> "ABB-CCC"
	 */
	public function __toString()
	{
		$pos = '';
		$neg = '';
		foreach ($this->coll as $letter => $num)
		{
			if ($num < 0)
			{
				$neg .= str_repeat($letter, -$num);
			}
			else
			{
				$pos .= str_repeat($letter, $num);
			}
		}
		
		if (empty($neg))
		{
			return $pos;
		}
		else
		{
			return $pos.'-'.$neg;
		}
	}
	
	/**
	 * Dumps info about this collection
	 */
	public function dump()
	{
		$pos = '';
		$neg = '';
		foreach ($this->coll as $letter => $num)
		{
			if ($num < 0)
			{
				$neg .= str_repeat($letter, -$num);
			}
			else
			{
				$pos .= str_repeat($letter, $num);
			}
		}
		
		if (empty($pos) && empty($neg))
		{
			echo 'EMPTY<br />';
		}
		elseif (empty($pos))
		{
			echo '-'.$neg.' !INVALID<br />';
		}
		elseif (empty($neg))
		{
			echo $pos.'<br />';
		}
		else
		{
			echo $pos.'-'.$neg.' !INVALID<br />';
		}
	}
}

?>