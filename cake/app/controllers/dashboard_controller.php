<?php

class DashboardController extends AppController
{
	var $name = 'Dashboard';
	var $uses = array(
		'Game',
		'User',
		'Player',
		'PlacedTile',
		'Move',
	);
	
	function index($notation = '')
	{
		echo '<pre>';
		$this->Game->id = 1;
		$this->Game->dump();
		if ($notation != '')
		{
			echo '<br />Play: <strong>'.$notation.'</strong><br />';
			$r = $this->Game->play($notation);
			if ($r === true)
			{
				$this->Game->dump();
			}
			else
			{
				echo 'Error('.$this->Game->errorcode.'): <strong>'.$this->Game->errors[$this->Game->errorcode].'</strong><br />';
			}
		}
		
		die();
	}
}

?>