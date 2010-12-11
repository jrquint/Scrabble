
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
		
		// Setup custom events
		this._setupCustomEvents();
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
				this.fireEvent('onWrite');
			}
			else
			{
				this.boardManager.removeAllTiles();
				this.activity = this.activities.Navigate;
				this.fireEvent('onWrite');
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
			this.fireEvent('onWrite');
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
	 * Calculates score for entered word
	 */
	calcScore: function()
	{
		if (!this.boardManager.isValid())
		{
			return -1;
		}
		return 42;
	},
	
	_event_onWrite: function()
	{
		var data = {
			score: this.calcScore(),
		};
		if (data.score < 0)
		{
			data.reason = this.boardManager.invalidity;
		}
		return data;
	},
	
	/**
	 * Set up custom events..
	 */
	_setupCustomEvents: function()
	{
		// An object with event names as keys and
		// arrays of subscribed callbacks as values
		
		// I reckon there are two kinds of data associated with events:
		// 1. Custom data given to the fired event where the event was fired
		// 2. Data that just 'belongs' to this kind of event, of which the generation
		//    method is statically available (within object-state..)
		this.events = {
			onWrite: [],
		};
	},
	
	fireEvent: function(eventname)
	{
		if (this.events[eventname] == undefined)
		{
			return false;
		}
		
		var data = {};
		if (this['_event_' + eventname] != undefined)
		{
			data = this['_event_' + eventname].call(this);
		}
		Array.each(this.events[eventname], function(subscriber)
		{
			subscriber.call(this, data);
		}, this);
	},
	
	addEvent: function(eventname, subscriber)
	{
		if (this.events[eventname] == undefined)
		{
			alert(eventname);
			return false;
		}
		this.events[eventname].push(subscriber);
	},
});
