
<div class="left">
</div>
<div class="main">
	<div id="scrabble"></div>
	<script type="text/javascript">
		window.addEvent('domready', function()
		{
			window.scrabble = new ScrabbleGame('scrabble', 'play_form', 'play_notation'<?php if (!empty($tiles)):?>, [
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
	<div style="display:none;">
		<?php echo $form->create(false, array(
			'id' => 'play_form',
			'url' => '/games/view/'.$game['Game']['id'],
		));?>
			<?php echo $form->input('play_notation', array(
				'type' => 'text',
				'id' => 'play_notation',
				'label' => 'Notation?',
			));?>
		<?php echo $form->end();?>
	</div>
</div> <!-- /.main -->
