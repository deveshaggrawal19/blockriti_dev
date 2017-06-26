'use strict';
angular.module('taurus.verificationModule', ['taurus.depositWithdrawModule', 'taurus.loggedInNavModule']);
// angular.module('taurus.verificationModule').config(function($stateProvider, $authProvider) {
angular.module('taurus.verificationModule').config(function($stateProvider, $authProvider) {
    $stateProvider.state('verification', {
        url : '/verification',
        controller : 'VerificationCtrl as vm',
        templateUrl: 'modules/verification/views/verification.html',
        resolve: {
            user: ['$auth', '$q', function ($auth, $q) {
                return $auth.isAuthenticated() || $q.reject({
                        unAuthorized: true
                    });
            }]
        }
    })

});
