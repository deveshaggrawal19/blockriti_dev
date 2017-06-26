'use strict';

angular.module('taurus.merchantModule', ['taurus.depositWithdrawModule', 'taurus.loggedInNavModule']);
angular.module('taurus.merchantModule').config(function($stateProvider, $authProvider) {
	$stateProvider.state('merchant', {
		url: '/merchant',
		controller: 'MerchantCtrl as vm',
		templateUrl: 'modules/merchant/views/merchant.html',
		resolve: {
			user: ['$auth', '$q', function ($auth, $q) {
				return $auth.isAuthenticated() || $q.reject({
						unAuthorized: true
					});
			}]
		}
	});
});
