'use strict';
angular.module('gwf4')
.service('EffectSrvc', function() {
	
	var EffectSrvc = this;
	
	EffectSrvc.ouch = function() {
		console.log('EffectSrvc.ouch()');
	};
	
	EffectSrvc.onMiss = function(player) {
		console.log('EffectSrvc.onMiss()', player);
	};

	EffectSrvc.onThrow = function(player, item) {
		console.log('EffectSrvc.onThrow()', player, item);
	};

	EffectSrvc.onThrowCollision = function(item) {
		console.log('EffectSrvc.onThrowCollision()', item);
	};
	
	EffectSrvc.onPickupItem = function(item) {
		console.log('EffectSrvc.onPickupItem()', item);
	}
	
	EffectSrvc.onDamage = function(player, damage) {
		console.log('EffectSrvc.onDamage()', player, damage);
	};

	EffectSrvc.onKill = function(player) {
		console.log('EffectSrvc.onKill()', player);
	};

	return EffectSrvc;
});
