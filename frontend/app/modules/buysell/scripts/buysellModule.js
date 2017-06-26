'use strict';

angular.module('taurus.buysellModule', ['taurus.depositWithdrawModule', 'taurus.loggedInNavModule']);
angular.module('taurus.buysellModule').config(function($stateProvider, $authProvider) {
	$stateProvider.state('buysell', {
		url: '/buysell',
		controller: 'BuysellCtrl as vm',
		templateUrl: 'modules/buysell/views/buysell.html',
		resolve: {
			user: ['$auth', '$q', function ($auth, $q) {
				return $auth.isAuthenticated() || $q.reject({
						unAuthorized: true
					});
			}]
		}
	});
});
