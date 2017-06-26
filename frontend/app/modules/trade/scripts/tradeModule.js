'use strict';

angular.module('taurus.tradeModule', ['taurus.depositWithdrawModule', 'taurus.loggedInNavModule']);
angular.module('taurus.tradeModule').config(function($stateProvider, $authProvider) {
	$stateProvider.state('trade', {
		url: '/trade',
		controller: 'TradeCtrl as vm',
		templateUrl: 'modules/trade/views/trade.html',
		resolve: {
			user: ['$auth', '$q', function ($auth, $q) {
				return $auth.isAuthenticated() || $q.reject({
						unAuthorized: true
					});
			}]
		}
	});
});
