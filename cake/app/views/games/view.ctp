
<div class="left">
	<?php if ($active_player['Player']['user_id'] == $session->read('Auth.User.id')):?>
		<p class="your_turn">
			It's your turn!
		</p>
		<p class="key_value">
			<span class="key">Your tiles:</span>
			<?php $rack = str_split($active_player['Player']['rack_tiles']);?>
			<?php foreach ($rack as $letter):?>
				<?php if ($letter == '_'):?>
					<div class="tile"></div>
				<?php else:?>
					<div class="tile"><p><?php echo $letter;?><span><?php echo ScrabbleLogic::getLetterScore($letter);?></span></p></div>
				<?php endif;?>
			<?php endforeach;?>
		</p>
		<div style="clear:both;"></div>
		<p class="key_value">
			<span class="key">Valid word?</span>
			<span class="value" id="updatable_validity">No</span>
		</p>
		<p class="key_value">
			<span class="key">Word score:</span>
			<span class="value" id="updatable_score">0</span>
		</p>
	<?php else:?>
	It's nt your turn, you'll have to wait..!
	<?php endif;?>
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
			/*
			$('points').set('text', 'valid? ' + ((data.score > 0) ? 'YES' : 'NO') + ', score? ' + data.score.toString());
			if (data.score < 0)
			{
				$('points').appendText(' Reason? ' + errors[data.reason]);
			}
			*/
			
			if (data.score >= 0)
			{
				$('updatable_validity').set('text', 'Yes');
				$('updatable_score').set('text', data.score.toString());
			}
			else
			{
				$('updatable_validity').set('text', 'Error: ' + errors[data.reason]);
				$('updatable_score').set('text', 'NaN');
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
