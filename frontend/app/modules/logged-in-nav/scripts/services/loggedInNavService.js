"use strict";

angular.module("taurus.loggedInNavModule").service('loggedInNavService', ['httpService',
    'urlService',  'baseConfig', '$auth', '$state', '$rootScope', '$cookieStore',
    function (httpService, urlService, baseConfig, $auth, $state, $rootScope, $cookieStore) {

        this.getBalance = function (successCallback, errorCallback) {
            httpService.getData(urlService.getUrl('BALANCE'), null, function (response) {
                if (response && response.data)
                    successCallback(response.data);
            }, function (error) {
                errorCallback(error);
            });
        };

        this.logout = function (successCallback, errorCallback) {
            httpService.getData(urlService.getUrl('LOGOUT'), null, function (response) {
                if (response && response.data)
                    successCallback(response.data);
            }, function (error) {
                errorCallback(error);
            });
        };

        this.authenticateFirebaseDb = function(authToken){
            return firebase.auth().signInWithCustomToken(authToken);
        };
    }
]);
