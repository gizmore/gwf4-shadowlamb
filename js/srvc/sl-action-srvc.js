'use strict';
angular.module('gwf4')
.service('ActionSrvc', function(PlayerSrvc, ItemSrvc, EffectSrvc) {
	
	var ActionSrvc = this;
	
	ActionSrvc.item = function(gwsMessage) { var itemId = gwsMessage.read32(); return itemId ? SL_Item.getById(itemId) : null; };
	ActionSrvc.player = function(gwsMessage) { var playerId = gwsMessage.read32(); return playerId ? PlayerSrvc.getOrAddPlayer(playerId) : null; };
	ActionSrvc.direction = function(gwsMessage) { return String.fromCharCode(gwsMessage.read8()); };
	
	ActionSrvc.execute = function(action, gwsMessage) {
		if (ActionSrvc[action]) {
			ActionSrvc[action](gwsMessage);
		}
		else {
			console.log('ActionSrvc.execute()', action);
		}
	};
	
	ActionSrvc.Throw = function(gwsMessage) {
		console.log('ActionSrvc.Throw()');
		var attacker = ActionSrvc.player(gwsMessage);
		var item = ActionSrvc.item(gwsMessage);
		attacker.equipment[item.slot] = false;
		item.slot = 'air';
		SL_FLOOR.addItem(item);
		EffectSrvc.onThrow(attacker, item);
		return true;
	};
	
	ActionSrvc.Stab = function(gwsMessage) {
		console.log('ActionSrvc.Stab()');
	};

	ActionSrvc.Miss = function(gwsMessage) {
		EffectSrvc.onMiss(ActionSrvc.player(gwsMessage));
	};
	
	ActionSrvc.Damage = function(gwsMessage) {
		console.log('ActionSrvc.Damage()');
		var player = ActionSrvc.player(gwsMessage);
		var damage = gwsMessage.read16();
		player.hp = gwsMessage.read16();
		player.max_hp = gwsMessage.read16();
		EffectSrvc.onDamage(player, damage);
	};

	ActionSrvc.Kill = function(gwsMessage) {
		console.log('ActionSrvc.Kill()');
		var player = ActionSrvc.player(gwsMessage);
		player.destroyMesh();
		PlayerSrvc.removePlayer(player);
		EffectSrvc.onKill(player);
	};
	
	return ActionSrvc;
});
