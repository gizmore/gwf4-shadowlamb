'use strict';
angular.module('gwf4')
.service('EffectSrvc', function() {
	
	var EffectSrvc = this;
	
	EffectSrvc.ouch = function() {
		console.log('EffectSrvc.ouch()');
	};

	EffectSrvc.onThrowCollision = function(item) {
		console.log('EffectSrvc.onThrowCollision()', item);
	};
	
	EffectSrvc.onPickupItem = function(item) {
		console.log('EffectSrvc.onPickupItem()', item);
	}

	return EffectSrvc;
});
