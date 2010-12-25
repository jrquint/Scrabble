<html>
<head>
	<title>Scrabble!</title>
	<link rel="stylesheet" type="text/css" href="<?php echo $html->url('/css/style.css');?>" media="all" />
	<script type="text/javascript" src="<?php echo $html->url('/js/mootools-core.js');?>"></script>
	<script type="text/javascript" src="<?php echo $html->url('/js/mootools-more.js');?>"></script>
	<script type="text/javascript" src="<?php echo $html->url('/js/board_manager.js');?>"></script>
	<script type="text/javascript" src="<?php echo $html->url('/js/scrabble_game.js');?>"></script>
	<script type="text/javascript" src="<?php echo $html->url('/js/seedrandom-min.js');?>"></script>
</head>
<body>

<p class="top">
	<a class="logo" href="<?php echo $html->url('/');?>">Scrabble!</a>
	<?php if ($session->check('Auth.User')):?>
		<span class="sep">&ndash;</span>
		Welcome back, <strong><?php echo $session->read('Auth.User.nickname');?></strong>!
		<span class="sep">&ndash;</span>
		<a href="<?php echo $html->url('/users/logout');?>">Log Out</a>
	<?php else:?>
	<?php endif;?>
</p>

<?php echo $content_for_layout;?>

<?php $flash = $session->flash() . $session->flash('auth');?>
<?php if (!empty($flash)):?>
	<div class="all_flash">
		<?php echo $flash;?>
	</div>
<?php endif;?>

</body>
</html>