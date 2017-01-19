function SL_Player() {
	
	this.position = new BABYLON.Vector3(0, 0, 0);
	
	this.equipment = {};
	this.inventory = [];
	
	this.isOwn = function() { return SL_PLAYER === this; };
	
	this.move = function(x, y, z) {
		var p = this.position;
		var SQ = SL_Tile.SQ;
		var sq = SL_Tile.sq;
		p.x = x * SQ;
		p.y = z * sq;
		p.z = y * SQ;
		this.x = x;
		this.y = y;
		this.z = z;
	};

	this.init = function(BabylonSrvc, PlayerSrvc, WebsocketSrvc) {
		this.init = function(){};
		BabylonSrvc.initPlayer(this);
		if (!this.isOwn()) {
			WebsocketSrvc.sendBinary(new GWS_Message().cmd(0x2001).write32(this.id));
		}
	};
	
	
	this.updateOwn = function(gwsMessage) {
		this.updateOther(gwsMessage);
		this.updateXP(gwsMessage);
		this.loadItems(gwsMessage);
	};
	
	this.updateOther = function(gwsMessage) {
		this.name = gwsMessage.readString();
		this.race = gwsMessage.read8();
		this.gender = gwsMessage.read8();
		this.element = gwsMessage.read8();
		this.color = gwsMessage.read8();
		this.skill = gwsMessage.read8();
		this.base_level = gwsMessage.read16();
		this.level = gwsMessage.read16();
		this.hp = gwsMessage.read16();
		this.max_hp = gwsMessage.read16();
		this.mp = gwsMessage.read16();
		this.max_mp = gwsMessage.read16();
		this.updateFields(gwsMessage, SL_CONFIG.attributes);
		this.updateFields(gwsMessage, SL_CONFIG.skills);
	};
	
	this.updateFields = function(gwsMessage, fields) {
		for (var i in fields) {
			var field = fields[i]
			this['base_'+field] = gwsMessage.read8();
			this[field] = gwsMessage.read8();
		}
	};

	this.updateXP = function(gwsMessage) {
		for (var i in SL_CONFIG.skills) {
			var skill = SL_CONFIG.skills[i];
			this['xp_'+skill] = gwsMessage.read32();
		}
	};
	
	this.loadItems = function(gwsMessage) {
		var loaded = SL_Item.itemsFromMessage(gwsMessage);
		for (var i in loaded) {
			var item = loaded[i];
			this.equipment[item.slot] = item;
		}
		this.inventory = SL_Item.itemsFromMessage(gwsMessage);
	};
	
	this.leftWeapon = function() {
		return this.weapon('shield');
	};
	this.rightWeapon = function() {
		return this.weapon('weapon');
	};
	this.weapon = function(slot) {
		return this.equipment[slot] ? this.equipment[slot] : this.fists();
	};
	this.fists = function() {
		var item = new SL_Item();
		item.name = 'Fists';
		return item;
	};
	this.hand = function() {
		return this.equipment['hand'];
	};
	this.handItem = function(item) {
		this.equipment['hand'] = item;
		if (item) {
			item.slot = 'hand';
			item.setupCursor();
			item.destroyMesh();
		}
	};

	
	this.equip = function(item, slot) {
		console.log('SL_Player.equip()', item.name, slot);
		var old = this.equipment[slot];
		this.equipment[slot] = item;
		item.slot = slot;
		if (old) {
			old.slot = 'hand';
		}
	};
	
	this.unequip = function(slot) {
		
	};

	return this;
}

SL_PLAYER = null;
