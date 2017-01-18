'use strict';
angular.module('gwf4')
.service('PlayerSrvc', function() {
	
	var PlayerSrvc = this;
	
	///////////
	// Cache //
	///////////
	PlayerSrvc.CACHE = {};
	
	PlayerSrvc.getOrAddPlayer = function(id, player) {
		return PlayerSrvc.hasPlayer(id) ? PlayerSrvc.getPlayer(id) : PlayerSrvc.addPlayer(player||PlayerSrvc.freshPlayer(id));
	};

	PlayerSrvc.getPlayer = function(id) { return PlayerSrvc.CACHE[id]; };
	PlayerSrvc.hasPlayer = function(id) { return !!PlayerSrvc.CACHE[id]; };
	PlayerSrvc.freshPlayer = function(id) { var player = new SL_Player(); player.id = id; return player; };
	PlayerSrvc.addPlayer = function(player) { PlayerSrvc.CACHE[player.id] = player; return player; };
	PlayerSrvc.removePlayer = function(player) { PlayerSrvc.CACHE[player.id] = undefined; };
	
	return PlayerSrvc;
});
