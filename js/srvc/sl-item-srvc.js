'use strict';
angular.module('gwf4')
.service('ItemSrvc', function() {
	
	var ItemSrvc = this;
	
	///////////
	// Cache //
	///////////
	ItemSrvc.CACHE = {};
	
	ItemSrvc.itemAction = function(item, idx) {
		return ItemSrvc.IdToAction(item.actions[idx]);
	};
	
	ItemSrvc.IdToAction = function(id) {
		return SL_CONFIG.actions[id-1];
	};
	
	ItemSrvc.actionToId = function(action) {
		return SL_CONFIG.actions.indexOf(action) + 1;
	};
	
	return ItemSrvc;
});
