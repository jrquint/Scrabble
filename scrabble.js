
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
	
	//window.scrabble.addEvent('onWrite', my_points);
});

function my_points(data)
{
	$('points').set('text', 'valid? ' + (data.is_valid ? 'YES' : 'NO') + ', score? ' + data.score.toString());
}

/**
 * Creates a new scrabble game object
 * This object will fill the scrabble div, and
 * handle all clicks in the scrabble div and
 * handle all key events related to the scrabble game.
 * 
 * So:
 * - Scrabble board setup
 * - Scrabble board navigation
 * - Scrabble-game-related AJAX
 * 
 * @class ScrabbleGame
 * @author kelleyvanevert
 */
var ScrabbleGame = new Class(
{
	initialize: function(el)
	{
		this.el = $(el);
		
		/*
			Create board div, then startup board manager object
		*/
		var board = (new Element('div', {id: 'board'})).inject(el, 'bottom');
		this.boardManager = new BoardManager(board);
		this.boardManager.putTiles((arguments[1] || {}), 'permanent');
		
		//this._setupCustomEvents();
		
		this.relative_movements = {
			'up':    {x: 0, y:-1},
			'down':  {x: 0, y: 1},
			'left':  {x:-1, y: 0},
			'right': {x: 1, y: 0},
		};
		
		this.activities = {
			Write:    2,
			Navigate: 1,
			Sleep:    0,
		};
		this.activity = 0;
		
		// Mouse clicks can instantaniously move focus to new field
		// TODO (ugly code)
		$$('.field, .field p, .field span').addEvent('click', (function(e) {
			var t = $(e.target);
			if (!t.hasClass('field')) t = t.getParent('.field');
			this.fieldclick(t.retrieve('pos'));
		}).bind(this));
		
		// Key events: navigation shortcuts & letter input
		// TODO (ugly code)
		var opts = {
			defaultEventType: 'keydown',
			events: {
				'up':    (function(e) { this.navigate({x: 0, y:-1}); e.stop(); }).bind(this),
				'down':  (function(e) { this.navigate({x: 0, y: 1}); e.stop(); }).bind(this),
				'right': (function(e) { this.navigate({x: 1, y: 0}); e.stop(); }).bind(this),
				'left':  (function(e) { this.navigate({x:-1, y: 0}); e.stop(); }).bind(this),
				'esc':   (function(e) { this.escape();               e.stop(); }).bind(this),
				'enter': (function(e) { this.submit();               e.stop(); }).bind(this),
			},
		};
		Array.each('abcdefghijklmnopqrstuvwxyz'.split(''), function(letter)
		{
			opts.events[letter] = (function(e) { this.letter(letter); e.stop(); }).bind(this);
		}, this);
		this.keyboard_events = new Keyboard(opts);
	},
	
	/**
	 * User wants to escape from current activity
	 */
	escape: function()
	{
		if (this.activity == this.activities.Sleep)
		{
			return;
		}
		else if (this.activity == this.activities.Navigate)
		{
			this.boardManager.removeFocus();
		}
		else if (this.activity == this.activities.Write)
		{
			if (this.boardManager.hasTemporaryTile())
			{
				this.boardManager.removeTile();
				if (this.boardManager.getTemporaryTilePositions().length == 0)
				{
					this.activity = this.activities.Navigate;
				}
			}
			else
			{
				this.boardManager.removeAllTiles();
				this.activity = this.activities.Navigate;
			}
		}
	},
	
	/**
	 * User wants to submit the temporary letters
	 */
	submit: function()
	{
		// TODO
	},
	
	/**
	 * User entered a new letter
	 */
	letter: function(letter)
	{
		if (!this.boardManager.hasFocus())
		{
			this.boardManager.putFocus();
			this.activity = this.activities.Navigate;
		}
		
		if (!this.boardManager.hasPermanentTile())
		{
			this.boardManager.putTile(letter);
			this.activity = Math.max(this.activity, this.activities.Write);
		}
	},
	
	/**
	 * User wants to navigate (relatively) using arrow keys
	 */
	navigate: function(newpos)
	{
		if (!this.boardManager.hasFocus())
		{
			this.boardManager.putFocus();
			this.activity = this.activities.Navigate;
			return;
		}
		
		this.boardManager.moveRelative(newpos);
		this.activity = Math.max(this.activity, this.activities.Navigate);
	},
	
	/**
	 * User clicked on a field --> to switch focus to this field
	 */
	fieldclick: function(pos)
	{
		if (!this.boardManager.hasFocus())
		{
			this.boardManager.putFocus();
		}
		
		this.boardManager.moveAbsolute(pos);
		this.activity = Math.max(this.activity, this.activities.Navigate);
	},
	
	/**
	 * Set up custom events..
	 */
	/*
	_setupCustomEvents: function()
	{
		// An object with event names as keys and
		// arrays of subscribed callbacks as values
		
		// I reckon there are two kinds of data associated with events:
		// 1. Custom data given to the fired event where the event was fired
		// 2. Data that just 'belongs' to this kind of event, of which the generation
		//    method is statically available (within object-state..)
		this.events = {
			onWrite: {
				subscribers: [],
				data_generators: {
					is_valid: this.is_valid,
					score: this.calc_score,
				},
			},
		};
	},
	
	fireEvent: function(eventname)
	{
		var custom_data = arguments[1] || {};
		var generated_data = {};
		Object.each(this.events[eventname].data_generators, function(generator, key)
		{
			generated_data[key] = generator.call(this);
		});
		Array.each(this.events[eventname].subscribers, function(subscriber)
		{
			subscriber.call(this, Object.merge(generated_data, custom_data));
		}, this);
	},
	
	addEvent: function(eventname, subscriber)
	{
		if (this.events[eventname] == undefined)
		{
			alert(eventname);
			return false;
		}
		this.events[eventname].subscribers.push(subscriber);
	},
	*/
});