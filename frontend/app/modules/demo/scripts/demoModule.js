'use strict';

angular.module('taurus.demoModule', ['taurus.depositWithdrawModule', 'taurus.loggedInNavModule']);
angular.module('taurus.demoModule').config(function($stateProvider, $authProvider) {
	$stateProvider.state('demo', {
		url: '/demo',
		controller: 'DemoCtrl as vm',
		templateUrl: 'modules/demo/views/demo.html'
//TODO The stellizer auth.isAuthenticated will not work because of how genereal it is we will need to modify the service userStatusService

	});
});
