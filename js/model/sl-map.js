function SL_Floor(config) {

	this.WIDTH = config.width;
	this.HEIGHT = config.height;
	this.TILES = new Uint8Array(config.width * config.height);
	
	this.clear = function() {
		var index = 0;
		for (var y = 0; y < this.HEIGHT; y++) {
			for (var x = 0; x < this.WIDTH; x++) {
				this.TILES[index++] = 0;
			}
		}
		return this;
	};

	this.update = function(x1, y1, x2, y2, gwsMessage) {
		for (var y = y1; y <= y2; y++) {
			var index = y * this.WIDTH + x1;
			for (var x = x1; x <= x2; x++) {
				this.TILES[index++] = gwsMessage.read8();
			}
		}
	};
	
	return this.clear();
}

function SL_Map() {
	
	this.CONFIG = {};
	this.FLOORS = {};
	
	this.floorFor = function(player) { return this.floor(player.z); };
	this.floor = function(z) {
		this.FLOORS[--z] = this.FLOORS[z] ? this.FLOORS[z] : new SL_Floor(this.CONFIG);
		return this.FLOORS[z];
	};
	
	this.init = function(config) {
		this.FLOORS = {};
		this.CONFIG = config;
	};
	
	this.update = function(gwsMessage) {
		var x1 = gwsMessage.read8(), y1 = gwsMessage.read8();
		var x2 = gwsMessage.read8(), y2 = gwsMessage.read8();
		var z = gwsMessage.read8();
		this.floor(z).update(x1, y1, x2, y2, gwsMessage);
	};
	
	return this;
}
