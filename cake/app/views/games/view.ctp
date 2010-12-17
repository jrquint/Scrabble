
<?php echo $this->element('scrabble_board', array(
	'tiles' => $tiles,
));?>

<p>Rack: <?php echo $rack;?></p>

<?php echo $form->create(false, array(
	'id' => 'play_form',
	'url' => '/games/play/'.$game_id,
));?>
	<?php echo $form->input('notation', array(
		'type' => 'text',
		'id' => 'notation',
		'label' => 'Notation?',
	));?>
<?php echo $form->end();?>
