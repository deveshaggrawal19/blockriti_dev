"use strict";

angular.module("taurus.autheticationModule").service('registerService', ['$rootScope','httpService',
    'urlService',
    function ($rootScope, httpService, urlService) {

        this.registerPost = function (registerParamObject, successCallback, errorCallback) {

            httpService.postData(urlService.getUrl('REGISTER'), registerParamObject, function (response) {
                if (response && response.data)
                    successCallback(response.data);
            }, function (error) {
                errorCallback(error);
            });
        };


}
]);