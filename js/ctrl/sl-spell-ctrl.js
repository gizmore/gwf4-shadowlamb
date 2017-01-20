'use strict';
angular.module('gwf4')
.controller('SLSpellCtrl', function($scope, WebsocketSrvc) {
	
	$scope.data = {
		runes: SL_CONFIG.runes,
		spell: [],
	};
	
	$scope.init = function() {
		$scope.data.spell = [];
	};

	$scope.spelldepth = function() {
		return $scope.data.spell.length;
	};
	
	$scope.spell = function() {
		return $scope.data.spell.join(' ');
	};

	$scope.rune = function(row, col) {
		console.log('SLSpellCtrl.rune()', row, col);
		$scope.refocus();
		var gwsMessage = new GWS_Message().cmd(0x2030).sync().write8(row).write8(col);
		return WebsocketSrvc.sendBinary(gwsMessage).then($scope.addRune);
	};
	
	$scope.addRune = function(gwsMessage) {
		console.log('SLSpellCtrl.addRune()', gwsMessage);
	};

	$scope.abort = function() {
		$scope.refocus();
		$scope.init();
	};

	$scope.cast = function() {
		$scope.refocus();
		$scope.init();
		var gwsMessage = new GWS_Message().cmd(0x2031);
		return WebsocketSrvc.sendBinary(gwsMessage);
	};
	
	$scope.init();
});
