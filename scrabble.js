
window.addEvent('domready', function()
{
	window.scrabble = new ScrabbleGame('scrabble', {
		'8,3': 'k',
		'8,4': 'e',
		'8,5': 'l',
		'8,6': 'l',
		'8,7': 'e',
		'8,8': 'y',
		
		'5,5': 'h',
		'6,5': 'a',
		'7,5': 'p',
	//	'8,5': 'l',
		'9,5': 'a',
		'10,5': 'n',
		'11,5': 'd',
	});
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
		var layout = arguments[1] || {};
		
		this._setupTiles();
		this._setupBoard(layout);
		this._setupActivities();
	},
	
	/**
	 * Sets up tileset etc..
	 */
	_setupTiles: function()
	{
		/* We shouldn't need this!
		this.tile_set = {
			'_': 2,
			'a': 9,
			'b': 2,
			'c': 2,
			'd': 4,
			'e': 12,
			'f': 2,
			'g': 3,
			'h': 2,
			'i': 9,
			'j': 1,
			'k': 1,
			'l': 4,
			'm': 2,
			'n': 6,
			'o': 8,
			'p': 2,
			'q': 1,
			'r': 6,
			's': 4,
			't': 6,
			'u': 4,
			'v': 2,
			'w': 2,
			'x': 1,
			'y': 2,
			'z': 1,
		};*/
		
		this.letter_scores = {
			'_': 0,
			'a': 1,
			'b': 3,
			'c': 3,
			'd': 2,
			'e': 1,
			'f': 4,
			'g': 2,
			'h': 4,
			'i': 1,
			'j': 8,
			'k': 5,
			'l': 1,
			'm': 3,
			'n': 1,
			'o': 1,
			'p': 3,
			'q': 10,
			'r': 1,
			's': 1,
			't': 1,
			'u': 1,
			'v': 4,
			'w': 4,
			'x': 8,
			'y': 4,
			'z': 10,
		};
	},
	
	/**
	 * Gets the score for the given letter
	 */
	get_letter_score: function(letter)
	{
		return this.letter_scores[letter];
	},
	
	/**
	 * Fills board div with field etc..
	 */
	_setupBoard: function(layout)
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
		var x, y, field, letter;
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
				this.fields[x][y] = {
					field: field,
				};
				if (layout[x.toString() + ',' + y.toString()] != undefined)
				{
					letter = layout[x.toString() + ',' + y.toString()];
					this.fields[x][y].permanent = letter;
					this.fields[x][y].field.addClass('permanent').set('html',
						'<p>'+letter+'<span>'+this.get_letter_score(letter)+'</span></p>'
					);
				}
			}
		}
	},
	
	/**
	 * Returns the field at given position
	 */
	fieldAt: function(pos)
	{
		return this.fields[pos.x][pos.y].field;
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
			if (this.is_written_to(this.pos))
			{
				this.remove_letter(this.pos);
			}
			else
			{
				this.remove_letters();
				this.activity = this.activities.Navigate;
			}
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
			this.activity = this.activities.Navigate;
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
	 * Checks whether the field at given pos contains permanent tile
	 */
	is_permanent: function(pos)
	{
		return this.fieldAt(pos).hasClass('permanent');
	},
	
	/**
	 * Checks whether the field at given pos is written to
	 */
	is_written_to: function(pos)
	{
		return this.fieldAt(pos).hasClass('temporary');
	},
	
	/**
	 * Removes a single letter at current position or given position
	 */
	remove_letter: function()
	{
		var pos = arguments[0] || this.pos;
		this.fieldAt(pos).set('html', '').removeClass('temporary');
		return true;
	},
	
	/**
	 * Remove all temporary letters from the board
	 */
	remove_letters: function()
	{
		Array.each(this.written, this.remove_letter.bind(this));
		this.written = [];
		return true;
	},
	
	/**
	 * Writes given letter to field at current position.
	 * Activity is raised to Write.
	 */
	write_letter: function(letter)
	{
		if (this.is_permanent(this.pos))
		{
			return false;
		}
		
		// Activity checks
		if (this.activity < this.activities.Navigate)
		{
			this.move(this.pos);
		}
		this.activity = Math.max(this.activity, this.activities.Write);
		
		// Write letter
		var html = '<p>'+letter+'<span>'+this.get_letter_score(letter)+'</span></p>';
		this.written.push(this.fieldAt(this.pos).addClass('temporary').set('html', html).retrieve('pos'));
	},
});