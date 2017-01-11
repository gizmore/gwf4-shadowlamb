'use strict';
angular.module('gwf4')
.controller('SLSidebarCtrl', function($scope, $state, WebsocketSrvc) {
	
	$scope.data = {
	};
	
	$scope.listGames = function() {
		console.log('SLSidebarCtrl.listGames()');
		$state.go('gamelist');
	};

});
