'use strict';
angular.module('gwf4')
.service('ActionSrvc', function(EffectSrvc) {
	
	var ActionSrvc = this;
	
	ActionSrvc.execute = function(action, attacker, defender, item, direction) {
		if (ActionSrvc[action]) {
			ActionSrvc[action](attacker, defender, item, direction);
		}
		else {
			console.log('ActionSrvc.execute()', action, attacker, defender, item, direction);
		}
	};
	
	ActionSrvc.Throw = function(attacker, defender, item, direction) {
		console.log('ActionSrvc.Throw()', attacker, defender, item, direction);
		attacker.equipment[item.slot] = false;
		item.slot = 'air';
		EffectSrvc.onThrow(attacker, item);
	};

	ActionSrvc.Stab = function(attacker, defender, item, direction) {
		console.log('ActionSrvc.Stab()', attacker, defender, item, direction);
	};
	
	return ActionSrvc;
});
