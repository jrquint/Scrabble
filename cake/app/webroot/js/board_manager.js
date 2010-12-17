
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
			'A': 1,
			'B': 3,
			'C': 3,
			'D': 2,
			'E': 1,
			'F': 4,
			'G': 2,
			'H': 4,
			'I': 1,
			'J': 8,
			'K': 5,
			'L': 1,
			'M': 3,
			'N': 1,
			'O': 1,
			'P': 3,
			'Q': 10,
			'R': 1,
			'S': 1,
			'T': 1,
			'U': 1,
			'V': 4,
			'W': 4,
			'X': 8,
			'Y': 4,
			'Z': 10,
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
	 * Registers temporary tile to board
	 */
	registerTemporaryTile: function(pos, letter)
	{
		this.fields[pos.x][pos.y].temporary = true;
		this.fields[pos.x][pos.y].letter = letter;
		if (letter == '_')
		{
			this.fields[pos.x][pos.y].blankletter = arguments[2];
		}
	},
	
	/**
	 * Unregisters temporary tile from board
	 */
	unregisterTemporaryTile: function(pos)
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
			// at least one side (north, south, east or west) must be connected
			// but we have to be careful about checking out of game bounds!
			var n, e, s, w;
			n = (pos.y ==  0) ? false : this.hasTile({x:pos.x,   y:pos.y-1});
			e = (pos.x == 14) ? false : this.hasTile({x:pos.x+1, y:pos.y  });
			s = (pos.y == 14) ? false : this.hasTile({x:pos.x,   y:pos.y+1});
			w = (pos.x ==  0) ? false : this.hasTile({x:pos.x-1, y:pos.y  });
			return (n || e || s || w);
		}, this);
	},
	
	/**
	 * Checks validity of temporary tiles
	 */
	isValid: function()
	{
		// TODO connected to mainland!
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
	 * Returns tile letter for tile at pos
	 * 'Blank' tile denoted by '_'
	 */
	getTileLetter: function()
	{
		var pos = arguments[0] || this.pos;
		return this.fields[pos.x][pos.y].letter;
	},
	
	/**
	 * Returns tile blankletter for tile at pos
	 * If tile not a blank tile: return false
	 */
	getTileBlankLetter: function()
	{
		var pos = arguments[0] || this.pos;
		if (this.getTileLetter(pos) != '_')
		{
			return false;
		}
		return this.fields[pos.x][pos.y].blankletter;
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
	 * Puts a tile to the board
	 * Arguments:
	 * tile = {
	 *     pos: {x:?, y:?}, // optional, defaults to current "cursor" position
	 *     letter: ?,       // mandatory, uppercase single letter or '_'
	 *     blankletter: ?,  // optional, uppercase single letter, use only if letter == '_'
	 * }
	 * permanentType        // optional, enum('permanent', 'temporary'), defaults to 'temporary'
	 */
	putTile: function(tile, permanentType)
	{
		pos = tile.pos || this.pos;
		permanentType = (permanentType ? 'permanent' : 'temporary');
		
		this.registerTemporaryTile(pos, tile.letter, tile.blankletter);
		if (tile.letter == '_')
		{
			this.fieldAt(pos).addClass(permanentType)
				.set('html', '<p class="blankletter">'+tile.blankletter+'</p>');
		}
		else
		{
			this.fieldAt(pos).addClass(permanentType)
				.set('html', '<p>' + tile.letter + '<span>' + this.getTileScore(tile.letter) + '</span></p>');
		}
	},
	
	/**
	 * Puts multiple letters with same type (=permanent/temporary) to the board
	 */
	putTiles: function(tiles, permanentType)
	{
		Array.each(tiles, function(tile)
		{
			this.putTile(tile, permanentType);
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
			this.unregisterTemporaryTile(pos);
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
