'use strict';

angular.module('taurus.escrowModule', ['taurus.depositWithdrawModule', 'taurus.loggedInNavModule']);
angular.module('taurus.escrowModule').config(function($stateProvider, $authProvider) {
	$stateProvider.state('escrow', {
		url: '/escrow',
		controller: 'EscrowCtrl as vm',
		templateUrl: 'modules/escrow/views/escrow.html',
		resolve: {
			user: ['$auth', '$q', function ($auth, $q) {
				return $auth.isAuthenticated() || $q.reject({
						unAuthorized: true
					});
			}]
		}
	});
});
