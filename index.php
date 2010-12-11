<html>
<head>
	<title>Scrabble!</title>
	<link rel="stylesheet" type="text/css" href="style.css" media="all" />
	<script type="text/javascript" src="mootools-core.js"></script>
	<script type="text/javascript" src="mootools-more.js"></script>
	<script type="text/javascript" src="board_manager.js"></script>
	<script type="text/javascript" src="scrabble_game.js"></script>
	<script type="text/javascript">
		window.addEvent('domready', function()
		{
			window.scrabble = new ScrabbleGame('scrabble', [
				{pos: {x: '8', y: '3'}, letter: 'k'},
				{pos: {x: '8', y: '4'}, letter: 'e'},
				{pos: {x: '8', y: '5'}, letter: 'l'},
				{pos: {x: '8', y: '6'}, letter: 'l'},
				{pos: {x: '8', y: '7'}, letter: 'e'},
				{pos: {x: '8', y: '8'}, letter: 'y'},
				{pos: {x: '5', y: '5'}, letter: 'h'},
				{pos: {x: '6', y: '5'}, letter: 'a'},
				{pos: {x: '7', y: '5'}, letter: 'p'},
				{pos: {x: '9', y: '5'}, letter: 'a'},
				{pos: {x: '10', y: '5'}, letter: 'n'},
				{pos: {x: '11', y: '5'}, letter: 'd'},
			]);
			
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
</head>
<body>

<p id="points">..</p>

<div id="scrabble">
</div>

</body>
</html>