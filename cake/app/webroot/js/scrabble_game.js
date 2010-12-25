
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
	initialize: function(el, submit_form, notation_element)
	{
		this.el = $(el);
		this.submit_form = $(submit_form);
		this.notation_element = $(notation_element);
		
		// Create board div, then startup board manager object
		var board = (new Element('div', {id: 'board'})).inject(this.el, 'bottom');
		this.boardManager = new BoardManager(board);
		this.boardManager.putTiles((arguments[3] || {}), 'permanent');
		
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
				'up':          (function(e) { this.navigate({x: 0, y:-1}); e.stop(); }).bind(this),
				'down':        (function(e) { this.navigate({x: 0, y: 1}); e.stop(); }).bind(this),
				'right':       (function(e) { this.navigate({x: 1, y: 0}); e.stop(); }).bind(this),
				'left':        (function(e) { this.navigate({x:-1, y: 0}); e.stop(); }).bind(this),
				'shift+up':    (function(e) { this.navigate({x: 0, y:-1}); e.stop(); }).bind(this),
				'shift+down':  (function(e) { this.navigate({x: 0, y: 1}); e.stop(); }).bind(this),
				'shift+right': (function(e) { this.navigate({x: 1, y: 0}); e.stop(); }).bind(this),
				'shift+left':  (function(e) { this.navigate({x:-1, y: 0}); e.stop(); }).bind(this),
				'esc':         (function(e) { this.escape();               e.stop(); }).bind(this),
				'enter':       (function(e) { this.submit();               e.stop(); }).bind(this),
			},
		};
		Array.each('abcdefghijklmnopqrstuvwxyz'.split(''), function(letter)
		{
			opts.events[letter] = (function(e) { this.letter({letter:letter.toUpperCase()}); e.stop(); }).bind(this);
			// Enter blank letter by pressing shift + [desired letter]
			opts.events['shift+'+letter] = (function(e) { this.letter({letter:'_',blankletter:letter.toUpperCase()}); e.stop(); }).bind(this);
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
		if (!this.boardManager.isValid())
		{
			alert('not valid!');
			return false;
		}
		
		this.notation_element.set('value', this.getPlayNotation_());
		this.submit_form.submit();
	},
	
	/**
	 * Gets play notation for submitted word.
	 * Asserts that the word to be submitted is valid
	 */
	getPlayNotation_: function()
	{
		var notation = '';
		var check_pos;
		var tmp = this.boardManager.getTemporaryTilePositions();
		
		// Get word direction and bounds
		// (resulting in an implicitly stated range (<x1,y1> -- <x2,y2>) using the
		// variables 'constant_coord', 'variable_coord', 'minbound', 'maxbound')
		// e.g. If word is horizontal, then 'constant_coord' = 'y' and variable_coord = 'x',
		// and 'minbound' and 'maxbound' will be x-values of coordinates
		
		// Discovery of direction is distinct in two cases: ONE temporary letter or multiple
		if (tmp.length == 1)
		{
			// word direction is an arbitrary choice
			// later on, we might make this choice more sophiticated
			var constant_coord = 'x';
			var variable_coord = 'y';
		}
		else
		{
			var constant_coord = (tmp[0].y == tmp[1].y) ? 'y' : 'x';
			var variable_coord = (constant_coord == 'x') ? 'y' : 'x';
		}
		
		// Get bounds of word
		var minbound = tmp[0][variable_coord];
		var maxbound = tmp[0][variable_coord];
		// find min bound
		check_pos = Object.clone(tmp[0]);
		while (this.boardManager.hasTile(check_pos))
		{
			minbound = check_pos[variable_coord];
			check_pos[variable_coord]--;
		}
		// find max bound
		check_pos = Object.clone(tmp[0]);
		while (this.boardManager.hasTile(check_pos))
		{
			maxbound = check_pos[variable_coord];
			check_pos[variable_coord]++;
		}
		
		// Get letter within bounds
		check_pos = Object.clone(tmp[0]);
		for (var i = minbound; i <= maxbound; i++)
		{
			check_pos[variable_coord] = i;
			var letter = this.boardManager.getTileLetter(check_pos);
			if (this.boardManager.hasPermanentTile(check_pos))
			{
				notation += '('+letter.toUpperCase()+')';
			}
			else if (letter == '_')
			{
				notation += '['+this.boardManager.getTileBlankLetter(check_pos).toLowerCase()+']';
			}
			else
			{
				notation += letter.toUpperCase();
			}
		}
		notation = notation.replace(/\)\(/g, '');
		
		// Append direction and first coordinate to notation
		if (constant_coord == 'x')
		{
			notation += ' '+ String.fromCharCode(65+tmp[0][constant_coord]) + minbound;
		}
		else
		{
			notation += ' '+ tmp[0][constant_coord] + String.fromCharCode(65+minbound);
		}
		
		// Append score to notation
		notation += ' ' + this.calcScore().toString();
		
		return notation;
	},
	
	/**
	 * User entered a new letter
	 */
	letter: function(tile)
	{
		if (!this.boardManager.hasFocus())
		{
			this.boardManager.putFocus();
			this.activity = this.activities.Navigate;
		}
		
		if (!this.boardManager.hasPermanentTile())
		{
			this.boardManager.putTile(tile);
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
		return 42; // TODO
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
