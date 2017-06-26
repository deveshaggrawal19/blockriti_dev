'use strict';

angular.module('taurus.walletModule', ['taurus.depositWithdrawModule', 'taurus.loggedInNavModule', 'angular-toArrayFilter']);
angular.module('taurus.walletModule').config(function($stateProvider, $authProvider) {
    $stateProvider.state('wallet', {
      url : '/wallet',
      controller : 'WalletCtrl as vm',
       templateUrl: 'modules/wallet/views/wallet.html',
        resolve: {
            user: ['$auth', '$q', function ($auth, $q) {
                return $auth.isAuthenticated() || $q.reject({
                        unAuthorized: true
                    });
            }]
        }
    });
});


