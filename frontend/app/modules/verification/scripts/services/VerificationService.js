angular.module("taurus.verificationModule").service('verificationService', ['httpService', 'urlService', 'baseConfig', '$auth', '$state', '$rootScope', '$cookieStore', function (httpService, urlService, baseConfig, $auth, $state, $rootScope, $cookieStore) {
			"use strict";

        this.verification_first = function (verificationObject, successCallback, errorCallback) {
           httpService.postData(urlService.getUrl('VERIFY_USER'), verificationObject, function (response) {
                if (response && response.data) {
                    successCallback(response.data);
                }
            }, function (error) {
                errorCallback(error);
            });
        };
		
		 this.getVerification = function (req, successCallback, errorCallback) {
            httpService.postData(urlService.getUrl('GET_VERIFY'), req, function (response) {
                if (response && response.data) {
                    successCallback(response.data);
                }
            }, function (error) {
                errorCallback(error);
            });
        };

         this.getLevelOneDetails = function (successCallback, errorCallback) {
            httpService.postData(urlService.getUrl('GET_LEVEL_ONE_DETAILS'), {"test": "true"}, function (response) {
                if (response && response.data) {
                    successCallback(response.data);
                }
            }, function (error) {
                errorCallback(error);
            });
        };

        this.uploadFile = function (req, uploadUrl, successCallback, errorCallback) {
            httpService.postData(uploadUrl, req, function (response) {
                if (response && response.data) {
                    successCallback(response.data);
                }
            }, function (error) {
                errorCallback(error);
            });
        }

}
]);
