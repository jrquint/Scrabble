
<div class="left">
	<h3>Your <span class="accent">active</span> games</h3>
	<?php if (empty($your_active_games)):?>
		<p>You have no active games!</p>
	<?php else:?>
		<?php foreach ($your_active_games as $game):?>
		<div class="game-listing">
			<a href="<?php echo $html->url('/games/view/'.$game['Game']['id']);?>">
				<?php echo $game['Game']['id'];?>
			</a>
		</div>
		<?php endforeach;?>
	<?php endif;?>
	
	<h3>Your <span class="accent">previous</span> games</h3>
	<?php if (empty($your_history_games)):?>
		<p>You have no previous games!</p>
	<?php else:?>
		<?php foreach ($your_history_games as $game):?>
		<div class="game-listing">
			<a href="<?php echo $html->url('/games/view/'.$game['Game']['id']);?>">
				<?php echo $game['Game']['id'];?>
			</a>
		</div>
		<?php endforeach;?>
	<?php endif;?>
</div>
