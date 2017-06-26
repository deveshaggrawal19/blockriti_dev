'use strict';

angular.module('taurus.dashboardModule', ['taurus.depositWithdrawModule', 'taurus.loggedInNavModule']);
angular.module('taurus.dashboardModule').config(function($stateProvider, $authProvider) {
	$stateProvider.state('dashboard', {
		url: '/dashboard',
		controller: 'DashboardCtrl as vm',
		templateUrl: 'modules/dashboard/views/dashboard.html',
		resolve: {
			user: ['$auth', '$q', function ($auth, $q) {
				return $auth.isAuthenticated() || $q.reject({
						unAuthorized: true
					});
			}]
		}
	});
});
