
<div class="left">
	<?php if ($active_player['Player']['user_id'] == $session->read('Auth.User.id')):?>
		<h3>It's your turn!</h3>
		<p class="key_value">
			<span class="key">Your tiles: <a class="shuffle" href="#" onclick="return !shuffle_player_tiles();">(shuffle)</a></span>
		</p>
		<div id="updatable_player_tiles"><?php
			$rack = str_split($active_player['Player']['rack_tiles']);
			foreach ($rack as $letter) {
				if ($letter == '_') {
					echo 'div class="tile"></div>';
				} else {
					echo '<div class="tile"><p>'.$letter.'<span>'.ScrabbleLogic::getLetterScore($letter).'</span></p></div>';
				}
			}
		?></div>
		<div style="clear:both;"></div>
		<p class="key_value">
			<span class="key">Score:</span>
			<span class="value" id="updatable_score">Invalid move</span>
		</p>
		<p class="key_value">
			<span class="key">Play:</span>
			<span class="value">
				<a class="scrabble_submit" href="#" onclick="return !window.scrabble.submit();">Submit word <span class="little">(&lt;enter&gt;)</span></a>
				<span class="little">or</span>
				<a class="scrabble_submit" href="#" onclick="return !window.scrabble.pass();">Pass</a>
			</span>
		</p>
	<?php else:?>
		<h3>It's <?php echo $active_player['User']['nickname'];?>'s turn!</h3>
		<p class="key_value">
			<span class="key">Your tiles: <a class="shuffle" href="#" onclick="return !shuffle_player_tiles();">(shuffle)</a></span>
		</p>
		<div id="updatable_player_tiles"><?php
			$rack = str_split($players[$session->read('Auth.User.id')]['Player']['rack_tiles']);
			foreach ($rack as $letter) {
				if ($letter == '_') {
					echo 'div class="tile"></div>';
				} else {
					echo '<div class="tile"><p>'.$letter.'<span>'.ScrabbleLogic::getLetterScore($letter).'</span></p></div>';
				}
			}
		?></div>
		<div style="clear:both;"></div>
	<?php endif;?>
	
	<h3>Players</h3>
	<?php foreach ($players as $user_id => $player):?>
		<p class="key_value">
			<span class="key"><?php echo $player['User']['nickname'];?>, score:</span>
			<span class="value"><?php echo $player['Player']['score'];?></span>
		</p>
	<?php endforeach;?>
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
			
			<?php if ($active_player['Player']['user_id'] == $session->read('Auth.User.id')):?>
				window.scrabble.activateSubmit();
				window.scrabble.addEvent('onWrite', my_points);
			<?php endif;?>
		});
		
		Array.implement({
			shuffle: function()
			{
				//destination array
				for (var j, x, i = this.length; i; j = parseInt(Math.random() * i), x = this[--i], this[i] = this[j], this[j] = x);
				return this;
			}
		});
		
		function my_points(data)
		{
			if (data.score >= 0)
			{
				$('updatable_score').set('text', data.score.toString());
			}
			else
			{
				$('updatable_score').set('text', 'Invalid move');
			}
		}
		
		function shuffle_player_tiles()
		{
			var tilebox = $('updatable_player_tiles');
			var tiles = tilebox.getChildren().shuffle();
			var tile;
			for (var i = 0; i < 7; i++)
			{
				tile = tiles[i].dispose();
				tile.inject(tilebox);
			}
			return true;
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
