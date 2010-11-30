
window.addEvent('domready', function()
{
	window.scrabble = new ScrabbleGame('scrabble');
});

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
 */
var ScrabbleGame = new Class(
{
	initialize: function(el)
	{
		this.el = $(el);
		this._setupBoard();
		this._setupNavigation();
	},
	
	/**
	 * Fills board div with field etc..
	 */
	_setupBoard: function()
	{
		/*
		// Input element
		this.input = new Element('input', {
			'id': 'in',
			'type': 'text',
		});
		this.input.inject(this.el, 'bottom');
		this.input.focus();
		*/
		
		// Board
		this.board = new Element('div', {
			'id': 'board',
		});
		this.board.setStyles({
			'width': (3 + 15*33).toString() + 'px',
			'height': (3 + 15*33).toString() + 'px',
		});
		this.board.inject(this.el, 'bottom');
		
		// Fields
		var x, y, field;
		this.fields = [];
		for (x = 0; x < 15; x++)
		{
			this.fields[x] = [];
			for (y = 0; y < 15; y++)
			{
				field = new Element('div', {
					'id': 'field-' + x.toString() + '-' + y.toString(),
					'class': 'field',
				});
				field.setStyles({
					'left': (3 + x*33).toString() + 'px',
					'top': (3 + y*33).toString() + 'px',
				});
				field.store('x', x);
				field.store('y', y);
				field.inject(this.board, 'bottom');
				this.fields[x][y] = field;
			}
		}
	},
	
	/**
	 * Sets up navigation variables and event handlers
	 */
	_setupNavigation: function()
	{
		// Focus & position activity
		this.at = {x:7, y:7};
		this.focused = false;
		
		// Write word activity
		this.writing = false;
		this.written = []; // Set of (letter-field)'s as an array
		
		// Mouse clicks
		$$('.field').addEvent('click', (function(e)
		{
			// Use move method
			this.move($(e.target).retrieve('x'), $(e.target).retrieve('y'));
		}).bind(this));
		
		/*
		// Letter input
		this.input.addEvent('keydown', function(e)
		{
			if (e.code < 65 || e.code > 90) return false;
		});
		*/
		
		// Shortcuts & Letters
		var opts = {
			defaultEventType: 'keydown',
			events: {
				'up':    (function(e) { this.move( 0,-1, true); e.stop(); }).bind(this),
				'down':  (function(e) { this.move( 0, 1, true); e.stop(); }).bind(this),
				'right': (function(e) { this.move( 1, 0, true); e.stop(); }).bind(this),
				'left':  (function(e) { this.move(-1, 0, true); e.stop(); }).bind(this),
				'esc':   (function(e) { this.stop_activity();   e.stop(); }).bind(this),
			},
		};
		Array.each('abcdefghijklmnopqrstuvwxyz'.split(''), function(letter)
		{
			opts.events[letter] = (function(e) { this.input_letter(letter); e.stop(); }).bind(this);
		}, this);
		this.keyboard_events = new Keyboard(opts);
	},
	
	/**
	 * Stops the current activity.
	 * If writing a word --> remove word from board
	 * If only focused   --> unfocus
	 */
	stop_activity: function()
	{
		if (this.writing)
		{
			this.writing = false;
			this.orientation = undefined;
			this.written = {start: undefined, end: undefined};
		}
		else if (this.focused)
		{
			this.unfocus_field();
			return true;
		}
	},
	
	/**
	 * Move the selected field to given (relative or absolute) coordinate,
	 * OR IF NOT FOCUSED --> just focus
	 */
	move: function(x, y)
	{
		var relative = arguments[2] || false;
		
		var new_x = ((relative ? this.at.x + x : x) + 15) % 15;
		var new_y = ((relative ? this.at.y + y : y) + 15) % 15;
		
		// Unfocus, change coordinate, refocus
		this.unfocus_field();
		this.at = {x: new_x, y: new_y};
		this.focus_field();
	},
	
	/**
	 * Unfocus field at current position
	 */
	unfocus_field: function()
	{
		if (!this.focused)
			return true;
		
		var f = this.fields[this.at.x][this.at.y];
		f.removeClass('at');
		
		this.focused = false;
	},
	
	/**
	 * Focus field at current position
	 */
	focus_field: function()
	{
		if (this.focused)
			return true;
		
		var f = this.fields[this.at.x][this.at.y];
		f.addClass('at');
		
		this.focused = true;
	},
	
	input_letter: function(letter)
	{
		if (!this.focused)
			return true;
		
		this.writing = true;
		
		var f = this.fields[this.at.x][this.at.y];
		f.set('text', letter);
	},
});