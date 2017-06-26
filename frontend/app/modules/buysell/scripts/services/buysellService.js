angular.module("taurus.buysellModule").service('buysellService', ['httpService', 'urlService', 'baseConfig', '$auth', '$state', '$rootScope', '$cookieStore', function (httpService, urlService, baseConfig, $auth, $state, $rootScope, $cookieStore) {
        "use strict";

        this.getInstantOrderApproximation = function (instantOrderApproxParams, successCallback, errorCallback) {
            httpService.postData(urlService.getUrl('GET_RATE'), instantOrderApproxParams, function (response) {
                if (response && response.data) {
                    successCallback(response.data);
                }
            }, function (error) {
                errorCallback(error);
            });
        };

        this.sellOrder = function (reqData, successCallback, errorCallback) {
            var finalUrl = urlService.appendParamsToUrl('BROKERAGE_SELL_ORDER', reqData);
            httpService.getRequest(finalUrl, function (response) {
                if (response && response.data) {
                    successCallback(response.data);
                }
            }, function (response) {
                errorCallback(response);
            });
        };

        this.buyOrder = function (reqData, successCallback, errorCallback) {
            var finalUrl = urlService.appendParamsToUrl('BROKERAGE_BUY_ORDER', reqData);
            httpService.getRequest(finalUrl, function (response) {
                if (response && response.data) {
                    successCallback(response.data);
                }
            }, function (response) {
                errorCallback(response);
            });
        }
}
]);
