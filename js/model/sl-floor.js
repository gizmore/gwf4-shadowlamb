function SL_Tile() {
	this.x = 0;
	this.y = 0;
	return this;
}

SL_Tile.SQ = 20;
SL_Tile.sq = 10;

function SL_Floor(config, number) {

	this.WIDTH = config.width;
	this.HEIGHT = config.height;
	this.TILES = new Uint8Array(config.width * config.height);
	this.INITED = false;
	this.CURRENT = false;
	this.NUMBER = number;
	this.ITEMS = [];
	this.TX = 0;
	this.TY = 0;
	
	this.clear = function() {
		var index = 0;
		for (var y = 0; y < this.HEIGHT; y++) {
			for (var x = 0; x < this.WIDTH; x++) {
				this.TILES[index++] = 0;
			}
		}
		return this;
	};

	this.update = function(x1, y1, x2, y2, gwsMessage, BabylonSrvc) {
		for (var y = y1; y <= y2; y++) {
			var index = y * this.WIDTH + x1;
			for (var x = x1; x <= x2; x++){
				var oldTile = this.TILES[index];
				var newTile = gwsMessage.read8();
				if (oldTile === 0) {
					switch(newTile) {
					case 0x0f:
						var box = BABYLON.MeshBuilder.CreateBox('box'+index, { size: SL_Tile.SQ, height: SL_Tile.sq }, BabylonSrvc.SCENE);
						box.position.x = x * SL_Tile.SQ;
						box.position.y = this.NUMBER * SL_Tile.sq;
						box.position.z = y * SL_Tile.SQ;
						break;
					}
					
				}
				this.TILES[index++] = newTile;
			}
		}
		
		this.updateItems(x1, y1, x2, y2, gwsMessage, BabylonSrvc);
	};
	
	this.updateItems = function(x1, y1, x2, y2, gwsMessage, BabylonSrvc) {
		var numItems = gwsMessage.read16();
		for (var i = 0; i < numItems; i++) {
			var item = SL_Item.fromMessage(gwsMessage);
			BabylonSrvc.addItem(item);
			this.ITEMS.push(item);
		}
	};
	
	///////////
	// Items //
	///////////
	this.itemsAtPosition = function(x, y) {
		var items = [];
		for (var i in this.ITEMS) {
			var item = this.ITEMS[i];
			if (item.isAtPosition(x, y)) {
				items.push(item);
			}
		}
		return items;
	};
	
	this.pickupItem = function(x, y) {
		var items = this.itemsAtPosition(x, y);
		if (items.length > 0) {
			return items[items.length-1];
		}
	};
	
	this.addItem = function(item) {
		this.ITEMS.push(item);
	};
	
	this.getItem = function(itemId) {
		for (var i in this.ITEMS) {
			var item = this.ITEMS[i];
			if (item.id === itemId) {
				return item;
			}
		}
	};
	
	this.removeItem = function(itemId) {
		var item = this.getItem(itemId);
		if (item) {
			var index = this.ITEMS.indexOf(item);
			this.ITEMS.splice(index, 1);
		}
		return item;
	};
	
	return this.clear();
}
SL_FLOOR = null;
