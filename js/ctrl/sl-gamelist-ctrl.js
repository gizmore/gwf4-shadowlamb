'use strict';
angular.module('gwf4')
.controller('SLGamelistCtrl', function($scope, WebsocketSrvc) {
	
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
			WebsocketSrvc.sendCommand('gamelist', '', false).then($scope.refreshed);
		});
	};
	
	$scope.refreshed = function(result) {
		console.log('SLGamelistCtrl.refreshed()', result);
	};
	
	$scope.init();
	
});
