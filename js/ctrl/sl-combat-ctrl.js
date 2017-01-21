'use strict';
angular.module('gwf4')
.controller('SLCombatCtrl', function($scope, $q, ItemSrvc, WebsocketSrvc, BabylonSrvc) {
	
	$scope.data.itemActions = {};
	$scope.data.combatCode = 0x0000;
	$scope.data.combatItem = null;
	$scope.data.combatActions = null;
	
	$scope.onWeaponLeft = function(player) { $scope.onWeapon(player, 0x2023, player.leftWeapon()); };
	$scope.onWeaponRight = function(player) { $scope.onWeapon(player, 0x2024, player.rightWeapon()); };
	$scope.onWeapon = function(player, code, weapon) {
		console.log('SLCombatCtrl.onWeapon()', player, weapon);
		$scope.refocus();
		
		$scope.data.combatCode = code;
		$scope.data.combatItem = weapon;
		$scope.data.combatActions = [];
		for (var i = 0; i < 3; i++) {
			var action = ItemSrvc.itemAction(weapon, i);
			if (action) {
				$scope.data.combatActions.push(action);
			}
		}
	};

	$scope.onAttack = function(player, combatIndex) {
		console.log('SLCombatCtrl.onAttack()', player, combatIndex);
		$scope.refocus();
		var gwsMessage = new GWS_Message().
			cmd($scope.data.combatCode).
			write8(BabylonSrvc.DIR.charCodeAt(0)).
			write32($scope.data.combatItem.id).
			write8($scope.data.combatItem.actions[combatIndex]);
		$scope.data.combatCode = 0;
		$scope.data.combatItem = null;
		$scope.data.combatActions = null;
		return WebsocketSrvc.sendBinary(gwsMessage);
	};

});
