<?php

class Move extends AppModel
{
	var $name = 'Move';
	var $belongsTo = array(
		'Player',
	);
}

?>