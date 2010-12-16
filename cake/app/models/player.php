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
}

?>