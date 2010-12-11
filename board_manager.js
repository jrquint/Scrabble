
/**
 * The board manager object, as the name says, manages a scrabble board.
 * Methods enable adding (permanent and temporary) letters to the board,
 * changing/removing/adding selected field, retrieving score information, etc..
 * 
 * @class BoardManager
 * @author kelleyvanevert
 */
var BoardManager = new Class(
{
	/**
	 * Constructor of BoardManager class
	 */
	initialize: function(board_div)
	{
		this.board = $(board_div);
		this._setupTiles();
		this._setupBoard();
		
		this.invalidities = {
			NoTiles:         0,
			TwoDimensions:   1,
			NotAllConnected: 2,
		};
	},
	
	/**
	 * Sets up tile info etc..
	 */
	_setupTiles: function()
	{
		// A list of (temporary tile) positions
		this.temporaryTiles = [];
		
		// Selected field
		this.focused = false;
		this.pos = {
			x: 7,
			y: 7,
		};
		
		this.tileScores = {
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
	 * Sets up the initial board (tile div's, etc..)
	 * 
	 * @access private
	 */
	_setupBoard: function()
	{
		// Board
		this.board.setStyles({
			'width': (3 + 15*33).toString() + 'px',
			'height': (3 + 15*33).toString() + 'px',
		});
		
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
					temporary: false,
				};
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
	 * Registeres temporary tile to board
	 */
	registerTemporaryTile: function(pos)
	{
		this.fields[pos.x][pos.y].temporary = true;
	},
	
	/**
	 * Unregisteres temporary tile from board
	 */
	registerTemporaryTile: function(pos)
	{
		this.fields[pos.x][pos.y].temporary = false;
	},
	
	/**
	 * Gets positions of all temporary tiles of board
	 */
	getTemporaryTilePositions: function()
	{
		var positions = [];
		for (x = 0; x < 15; x++)
		{
			for (y = 0; y < 15; y++)
			{
				if (this.hasTemporaryTile({x:x, y:y}))
				{
					positions.push({x:x, y:y});
				}
			}
		}
		
		return positions;
	},
	
	/**
	 * Checks whether temporary tiles are put in one dimension
	 */
	usingOneDimension: function()
	{
		var bounds = {
			max_x: 0,
			max_y: 0,
			min_x: 14,
			min_y: 14,
		};
		return Array.every(this.getTemporaryTilePositions(), function(pos)
		{
			bounds.max_x = Math.max(pos.x, bounds.max_x);
			bounds.max_y = Math.max(pos.y, bounds.max_y);
			bounds.min_x = Math.min(pos.x, bounds.min_x);
			bounds.min_y = Math.min(pos.y, bounds.min_y);
			
			return (bounds.max_x == bounds.min_x || bounds.max_y == bounds.min_y);
		});
	},
	
	/**
	 * Checks whether all temporary tiles are connected
	 * to each other (may be via permanent tiles)
	 * and to the mainland
	 * Is it assumed that all tiles are in ONE column or row
	 */
	allConnected: function()
	{
		return Array.every(this.getTemporaryTilePositions(), function(pos)
		{
			return (this.hasTile({x:pos.x,   y:pos.y+1}) ||
					this.hasTile({x:pos.x,   y:pos.y-1}) ||
					this.hasTile({x:pos.x+1, y:pos.y}) ||
					this.hasTile({x:pos.x-1, y:pos.y}));
		}, this);
	},
	
	/**
	 * Checks validity of temporary tiles
	 */
	isValid: function()
	{
		if (this.getTemporaryTilePositions().length == 0)
		{
			this.invalidity = this.invalidities.NoTiles;
			return false;
		}
		else
		{
			if (!this.usingOneDimension())
			{
				this.invalidity = this.invalidities.TwoDimensions;
				return false;
			}
			else
			{
				if (!this.allConnected())
				{
					this.invalidity = this.invalidities.NotAllConnected;
					return false;
				}
				else
				{
					return true;
				}
			}
		}
		return false;
	},
	
	/**
	 * Filters a position to make sure it falls within
	 * the scrabble coordinate system (which is [0..14]^2)
	 */
	filterScrabblePoint: function(pos)
	{
		pos.x = (pos.x + 15) % 15;
		pos.y = (pos.y + 15) % 15;
		return pos;
	},
	
	/**
	 * Returns focused position
	 */
	getPosition: function()
	{
		return Object.clone(this.pos);
	},
	
	/**
	 * Moves the selected field to an absolute coordinate
	 */
	moveAbsolute: function(newpos)
	{
		this.removeFocus();
		this.pos = this.filterScrabblePoint(newpos);
		this.putFocus();
	},
	
	/**
	 * Moves the selected field to a new position, relative to old one
	 */
	moveRelative: function(newpos)
	{
		this.removeFocus();
		this.pos = this.filterScrabblePoint({
			x: this.pos.x + newpos.x,
			y: this.pos.y + newpos.y,
		});
		this.putFocus();
	},
	
	/**
	 * Puts focus to remembered position
	 */
	putFocus: function()
	{
		this.fieldAt(this.pos).addClass('at');
		this.focused = true;
	},
	
	/**
	 * Removes focus from focused field
	 */
	removeFocus: function()
	{
		this.fieldAt(this.pos).removeClass('at');
		this.focused = false;
	},
	
	/**
	 * Checks whether focused
	 */
	hasFocus: function()
	{
		return this.focused;
	},
	
	/**
	 * Returns individual tile score for tile with given letter.
	 * 'Blank' tile denoted by '_'
	 */
	getTileScore: function(letter)
	{
		return this.tileScores[letter];
	},
	
	/**
	 * Checks whether a field has a tile
	 */
	hasTile: function()
	{
		var pos = arguments[0] || this.pos;
		return (this.hasPermanentTile(pos) || this.hasTemporaryTile(pos));
	},
	
	/**
	 * Checks whether a field has permanent tile
	 */
	hasPermanentTile: function()
	{
		var pos = arguments[0] || this.pos;
		return this.fieldAt(pos).hasClass('permanent');
	},
	
	/**
	 * Checks whether a field has temporary tile
	 */
	hasTemporaryTile: function()
	{
		var pos = arguments[0] || this.pos;
		return this.fieldAt(pos).hasClass('temporary');
	},
	
	/**
	 * Puts a letter with type (=permanent/temporary) at given position
	 */
	putTile: function(letter, pos, permanentType)
	{
		pos = arguments[1] || this.pos;
		this.registerTemporaryTile(pos);
		this.fieldAt(pos)
			.addClass((permanentType ? 'permanent' : 'temporary'))
			.set('html', '<p>' + letter + '<span>' + this.getTileScore(letter) + '</span></p>');
	},
	
	/**
	 * Puts multiple letters with same type (=permanent/temporary) to the board
	 */
	putTiles: function(tiles, permanentType)
	{
		Array.each(tiles, function(tile)
		{
			this.putTile(tile.letter, tile.pos, permanentType);
		}, this);
	},
	
	/**
	 * Removes tile at specified position (but only if type is temporary)
	 */
	removeTile: function()
	{
		var pos = arguments[0] || this.pos;
		var f = this.fieldAt(pos);
		if (f.hasClass('temporary'))
		{
			this.registerTemporaryTile(pos);
			f.removeClass('temporary').empty();
		}
	},
	
	/**
	 * Removes tiles at specified positions (but only if type is temporary)
	 */
	removeTiles: function(positions)
	{
		Array.each(positions, function(pos)
		{
			this.removeTile(pos);
		}, this);
	},
	
	/**
	 * Removes all temporary tiles from the board
	 */
	removeAllTiles: function()
	{
		this.removeTiles(this.getTemporaryTilePositions());
	},
});
