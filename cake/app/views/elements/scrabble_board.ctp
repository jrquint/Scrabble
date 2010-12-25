
<p id="points">..</p>

<div id="scrabble"></div>

<script type="text/javascript">
	window.addEvent('domready', function()
	{
		window.scrabble = new ScrabbleGame('scrabble', '<?php echo $html->url('/games/view/');?>'<?php if (!empty($tiles)):?>, [
			<?php foreach ($tiles as $tile):?>
			{
				pos: {
					x: '<?php echo $tile['x'];?>',
					y: '<?php echo $tile['y'];?>'
				},
				letter: '<?php echo $tile['letter'];?>',
				<?php if (isset($tile['blankletter'])):?>
				blankletter: '<?php echo $tile['blankletter'];?>',
				<?php endif;?>
			},
			<?php endforeach;?>
		]<?php endif;?>);
		
		window.scrabble.addEvent('onWrite', my_points);
	});
	
	function my_points(data)
	{
		var errors = ['No tiles!', 'Only one dimension please!', 'Not all tiles connected!'];
		$('points').set('text', 'valid? ' + ((data.score > 0) ? 'YES' : 'NO') + ', score? ' + data.score.toString());
		if (data.score < 0)
		{
			$('points').appendText(' Reason? ' + errors[data.reason]);
		}
	}
</script>
