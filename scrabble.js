
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
		this._setupActivities();
	},
	
	/**
	 * Fills board div with field etc..
	 */
	_setupBoard: function()
	{
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
				field.store('pos', {
					x: x,
					y: y,
				});
				field.inject(this.board, 'bottom');
				this.fields[x][y] = field;
			}
		}
	},
	
	/**
	 * Returns the field at given position
	 */
	fieldAt: function(pos)
	{
		return this.fields[pos.x][pos.y];
	},
	
	/**
	 * Sets up activity variables and event handlers
	 */
	_setupActivities: function()
	{
		/*
			An activity denotes what level of interaction a user is in.
			Activities fit a total order, therefore it is a kind of implicit stack.
			The idea of "activities" is made concrete, because it denotes, for example, the
			  amount of times the user has to press "esc" to lose focus etc from the board.
			The activities are:
				0. Sleep:      the user has no focus on the game board
				1. Navigation: the user is navigating through the board, but has not written anything yet.
				2. Write:      the user has written some letters (and can of course still navigate..).
		*/
		this.activities = {
			Write:    2,
			Navigate: 1,
			Sleep:    0,
		};
		this.activity = 0;
		
		// Position; initial position is center of board.
		// Always set: even is activity is false, position is remembered
		this.pos = {
			x: 7,
			y: 7,
		};
		
		// Written letters; empty set if activity is lower than "write"
		this.written = []; // Set of (letter-field)'s as an array
		
		// Mouse clicks can instantaniously move focus to new field
		$$('.field').addEvent('click', (function(e) {
			this.move($(e.target).retrieve('pos'));
		}).bind(this));
		
		// Key events: navigation shortcuts & letter input
		var opts = {
			defaultEventType: 'keydown',
			events: {
				'up':    (function(e) { this.move({x: 0, y:-1}, true); e.stop(); }).bind(this),
				'down':  (function(e) { this.move({x: 0, y: 1}, true); e.stop(); }).bind(this),
				'right': (function(e) { this.move({x: 1, y: 0}, true); e.stop(); }).bind(this),
				'left':  (function(e) { this.move({x:-1, y: 0}, true); e.stop(); }).bind(this),
				'esc':   (function(e) { this.drop_activity();   e.stop(); }).bind(this),
			},
		};
		Array.each('abcdefghijklmnopqrstuvwxyz'.split(''), function(letter)
		{
			opts.events[letter] = (function(e) { this.write_letter(letter); e.stop(); }).bind(this);
		}, this);
		this.keyboard_events = new Keyboard(opts);
	},
	
	/**
	 * Drops down an activity
	 */
	drop_activity: function()
	{
		if (this.activity == this.activities.Navigate)
		{
			this.remove_focus();
			this.activity = this.activities.Sleep;
		}
		else if (this.activity == this.activities.Write)
		{
			this.remove_letters();
			this.activity = this.activities.Navigate;
		}
	},
	
	/**
	 * Move focused field to given (possibly relative) position.
	 * For relative movement, pass along third, boolean true, argument.
	 * If activity lower than Navigate --> activity is raised to Navigate.
	 */
	move: function(newpos)
	{
		if (this.activity < this.activities.Navigate)
		{
			this.put_focus(this.pos);
			this.activity = this.activities.Navigate;
			return;
		}
		
		var relative = arguments[1] || false;
		
		newpos.x = ((relative ? this.pos.x + newpos.x : newpos.x) + 15) % 15;
		newpos.y = ((relative ? this.pos.y + newpos.y : newpos.y) + 15) % 15;
		
		this.remove_focus();
		this.pos = newpos;
		this.put_focus();
	},
	
	/**
	 * Removes focus
	 */
	remove_focus: function()
	{
		this.fieldAt(this.pos).removeClass('at');
	},
	
	/**
	 * Puts focus
	 */
	put_focus: function()
	{
		this.fieldAt(this.pos).addClass('at');
	},
	
	/**
	 * Remove all temporary letters from the board
	 */
	remove_letters: function()
	{
		Array.each(this.written, function(field)
		{
			field.set('text', '').removeClass('written');
		});
		this.written = [];
		return true;
	},
	
	/**
	 * Writes given letter to field at current position.
	 * Activity is raised to Write.
	 */
	write_letter: function(letter)
	{
		// Activity checks
		if (this.activity < this.activities.Navigate)
		{
			this.move(this.pos);
		}
		this.activity = Math.max(this.activity, this.activities.Write);
		
		// Write letter
		this.written.push(this.fieldAt(this.pos).addClass('written').set('text', letter));
	},
});