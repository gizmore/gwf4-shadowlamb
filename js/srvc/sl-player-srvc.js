'use strict';
angular.module('gwf4')
.service('PlayerSrvc', function() {
	
	var PlayerSrvc = this;
	
	///////////
	// Cache //
	///////////
	PlayerSrvc.OWN = null;
	PlayerSrvc.CACHE = {};
	
//	PlayerSrvc.updatePlayerCache = function(json) {
//		var name = json.user_name;
//		var cache = PlayerSrvc.CACHE;
//		cache[name] = !cache[name] ? new TGC_Player(json) : cache[name].update(json);
//		return cache[name];
//	};

	PlayerSrvc.getOrAddPlayer = function(id, player) {
		return PlayerSrvc.hasPlayer(id) ? PlayerSrvc.getPlayer(id) : PlayerSrvc.addPlayer(player||{id:id});
	};

	PlayerSrvc.getPlayer = function(id) {
		return PlayerSrvc.CACHE[id];
	};

	PlayerSrvc.hasPlayer = function(id) {
		return !!PlayerSrvc.CACHE[id];
	};
	
	PlayerSrvc.addPlayer = function(player) {
		PlayerSrvc.CACHE[player.id] = player;
	};

	PlayerSrvc.removePlayer = function(player) {
		if (PlayerSrvc.OWN === player) {
			PlayerSrvc.OWN = null;
		}
		PlayerSrvc.CACHE[player.id] = undefined;
	};

	return PlayerSrvc;
});
