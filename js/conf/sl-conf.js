angular.module('gwf4')
.config(function($stateProvider) {
	$stateProvider.state({
		name: 'gamelist',
		url: '/gamelist',
		controller: 'SLGamelistCtrl',
		templateUrl: GWF_WEB_ROOT+'module/Shadowlamb/js/tpl/gamelist.html',
		pageTitle: 'Gamelist'
	});
})
.run(function() {
});
