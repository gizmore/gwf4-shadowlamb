function SL_Map() {
	
	this.CONFIG = {};
	this.FLOORS = {};
	
	this.floorFor = function(player) { return this.floor(player.z); };
	this.floor = function(z) {
		this.FLOORS[--z] = this.FLOORS[z] ? this.FLOORS[z] : new SL_Floor(this.CONFIG, z+1);
		return this.FLOORS[z];
	};
	
	this.setCurrentFloor = function(floor) {
		for (var i in this.FLOORS) {
			this.FLOORS[i].INITED = this.FLOORS[i].CURRENT = false;
		}
		floor.CURRENT = true;
		SL_FLOOR = floor;
	}
	
	this.init = function(config) {
		this.FLOORS = {};
		this.CONFIG = config;
	};
	
	this.update = function(gwsMessage, BabylonSrvc) {
		var x1 = gwsMessage.read8(), y1 = gwsMessage.read8();
		var x2 = gwsMessage.read8(), y2 = gwsMessage.read8();
		var z = gwsMessage.read8();
		var floor = this.floor(z);
//		var inited = !!floor.INITED;
		floor.update(x1, y1, x2, y2, gwsMessage, BabylonSrvc);
	};

	
	return this;
}
