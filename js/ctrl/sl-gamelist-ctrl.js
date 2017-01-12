'use strict';
angular.module('gwf4')
.controller('SLGamelistCtrl', function($scope, $state, WebsocketSrvc) {
	
	$scope.data = {
		games: [],
	};
	
	$scope.init = function() {
		console.log('SLGamelistCtrl.init()');
		$scope.hideGWFContent();
		$scope.closeSidenavs();
		$scope.refresh();
	};
	
	$scope.refresh = function() {
		console.log('SLGamelistCtrl.refresh()');
		WebsocketSrvc.withConn(function() {
			WebsocketSrvc.sendCommand('sl_gamelist', '', false).then($scope.refreshed);
		});
	};
	
	$scope.refreshed = function(result) {
		console.log('SLGamelistCtrl.refreshed()', result);
		$scope.data.games = JSON.parse(result);
	};
	
	$scope.newGame = function() {
		console.log('SLGamelistCtrl.newGame()');
		WebsocketSrvc.withConn(function() {
			WebsocketSrvc.sendCommand('sl_newgame', '', false).then($scope.refresh);
		});
	};
	
	$scope.joinGame = function(game) {
		console.log('SLGamelistCtrl.joinGame()');
		$state.go('game', {gamename:game.name});
	};
	
	$scope.init();
});
