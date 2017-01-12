'use strict';
angular.module('gwf4')
.controller('SLCtrl', function($scope, WebsocketSrvc, CommandSrvc, PlayerSrvc) {
	
	$scope.data = {
		map: new SL_Map(),
	};

	$scope.$on('$stateChangeSuccess', function(event, toState, toParams, fromState, fromParams) {
//		console.log('SLCtrl.$on-stateChangeSuccess()', toState, toParams);
		$scope.init(toParams.gamename);
	});
	
	$scope.init = function(gamename) {
//		console.log('SLCtrl.init()', gamename);
		$scope.hideGWFContent();
		$scope.closeSidenavs();
		WebsocketSrvc.withConn(function() {
			WebsocketSrvc.sendCommand('sl_joingame', gamename, false).then($scope.joinedGame, $scope.joinFailure);
		});
	};
	
	$scope.joinedGame = function(result) {
		console.log('SLCtrl.joinedGame()', result);
		var data = JSON.parse(result);
		$scope.data.map.init(data.config);
	};

	$scope.joinFailure = function(error) {
		console.log('SLCtrl.joinFailure()', error);
	};
	
	//////////
	// Move //
	//////////
	$scope.move = function(direction) {
		console.log('SLCtrl.move()', direction);
		var gwsMessage = new GWS_Message().cmd(0x2002).sync().write8(direction.charCodeAt(0));
		return WebsocketSrvc.sendBinary(gwsMessage).then($scope.afterMove);
	};

	$scope.afterMove = function(gwsMessage) {
		console.log('SLCtrl.afterMove()', gwsMessage);
	};
	
	//////////////
	// Handlers //
	//////////////
	CommandSrvc.xcmd_2001 = function(gwsMessage) {
		console.log('SLCtrl.xcmd_2001 POS()');
		var player = PlayerSrvc.getOrAddPlayer(gwsMessage.read32());
		player.move(gwsMessage.read8(), gwsMessage.read8(), gwsMessage.read8());
	};
	CommandSrvc.xcmd_2002 = function(gwsMessage) {
		console.log('SLCtrl.xcmd_2002 OWN()');
		var player = PlayerSrvc.OWN = new SL_Player();
		player.id = gwsMessage.read32();
		
		PlayerSrvc.addPlayer(player);
	};
	CommandSrvc.xcmd_2003 = function(gwsMessage) {
		console.log('SLCtrl.xcmd_2003 PLAYER()');
	};
	CommandSrvc.xcmd_2004 = function(gwsMessage) {
		console.log('SLCtrl.xcmd_2004 MAP()');
		$scope.data.map.update(gwsMessage);
	};

});
