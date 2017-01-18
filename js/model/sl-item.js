function SL_Item() {

	this.iconPath = function() { return sprintf('%sindex.php?mo=Shadowlamb&me=Icon&name=%s', GWF_WEB_ROOT, this.name); };
	this.icon = function() { return sprintf('<img src="%s" alt="%s" />', this.iconPath(), this.name); };
	this.isAtPosition = function(x, y) { return (this.x == x) && (this.y == y); };
	
	this.flyZ = function() {
		return this.flying ? -1 : 4;
	};
	
	this.setupCursor = function() {
		$('#game-canvas').css('cursor', this.iconPath());
	};
	
	this.restoreCursor = function() {
		$('#game-canvas').css('cursor', 'auto');
	};
	
	this.move = function(x, y, z) {
		this.x = x; this.y = y; this.z = z;
		if (this.mesh) {
			var SQ = SL_Tile.SQ,
				sq = SL_Tile.sq;
			this.mesh.position.x = this.x*SQ;
			this.mesh.position.y = this.z*sq - this.flyZ();
			this.mesh.position.z = this.y*SQ;
		}
	};
	
	this.destroyMesh = function() {
		this.mesh.dispose();
		this.mesh = undefined;
	};
	
	this.createMesh = function() {
		SL_Item.BabylonSrvc.addItem(this);
	};
	
	return this;
}

///////////
// Cache //
///////////
SL_Item.CACHE = {};
SL_Item.getById = function(itemId) {
	if (itemId <= 0) {
		return null;
	}
	else if (SL_Item.CACHE[itemId]) {
		return SL_Item.CACHE[itemId];
	}
	else {
		var item = new SL_Item();
		item.id = itemId;
		SL_Item.CACHE[itemId] = item;
		SL_Item.WebsocketSrvc.sendBinary(new GWS_Message().cmd(0x2003).write32(itemId));
	}
};

/////////////
// Factory //
/////////////
SL_Item.nameFromInt = function(nameInt) { return SL_CONFIG.items[nameInt]; };
SL_Item.slotFromInt = function(slotInt) { return SL_CONFIG.slots[slotInt-1]; };
SL_Item.slotInt = function(slot) { var ix = SL_CONFIG.slots.indexOf(slot); return ix < 0 ? 0 : ix+1; };
SL_Item.itemsFromMessage = function(gwsMessage) {
	var items = [];
	var numItems = gwsMessage.read16();
	for (var i = 0; i < numItems; i++) {
		items.push(SL_Item.fromMessage(gwsMessage));
	}
	return items;
};
SL_Item.fromMessage = function(gwsMessage) {
	var item = new SL_Item();
	item.id = gwsMessage.read32();
	item.x = gwsMessage.read8();
	item.y = gwsMessage.read8();
	item.z = gwsMessage.read8();
	item.slot = SL_Item.slotFromInt(gwsMessage.read8());
	item.name = SL_Item.nameFromInt(gwsMessage.read16());
	item.weight = gwsMessage.read16();
	SL_Item.parseStats(item, gwsMessage, SL_CONFIG.combat);
	SL_Item.parseStats(item, gwsMessage, SL_CONFIG.attributes);
	SL_Item.parseStats(item, gwsMessage, SL_CONFIG.skills);
	SL_Item.CACHE[item.id] = item;
	console.log('SL_Item.fromMessage()', item);
	return item;
};
SL_Item.parseStats = function(item, gwsMessage, fields) {
	for (var i in fields) {
		item[fields[i]] = gwsMessage.read8();
	}
};
