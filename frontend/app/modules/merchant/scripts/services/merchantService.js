angular.module("taurus.merchantModule").service('merchantService', ['httpService', 'urlService', 'baseConfig', '$auth', '$state', '$rootScope', '$cookieStore', function (httpService, urlService, baseConfig, $auth, $state, $rootScope, $cookieStore) {
    "use strict";

    this.getSellerBalance = function (sellerId, successCallback, errorCallback) {
        var finalUrl = urlService.getPlainUrl('GET_SELLER_BALANCE');
        finalUrl = finalUrl.replace('{seller}', sellerId);

        httpService.getRequest(
            finalUrl,
            function (response) {
                successCallback(response.data);
            },
            function (response) {
                errorCallback(response);
            }
        );
    };

    this.processPayout = function (reqData, successCallback, errorCallback) {
        var finalUrl = urlService.getPlainUrl('PROCESS_PAYOUT');
        finalUrl = finalUrl.replace('{seller}', reqData.sellerId);
        finalUrl = finalUrl.replace('{amount}', reqData.amount);
        finalUrl = finalUrl.replace('{currency}', reqData.currency);
        finalUrl = finalUrl.replace('{address}', reqData.address);

        httpService.getRequest(
            finalUrl,
            function (response) {
                successCallback(response.data);
            },
            function (response) {
                errorCallback(response);
            }
        );
    };
    
    this.processRequestPayment = function (reqData, successCallback, errorCallback) {
        var finalUrl = urlService.getPlainUrl('PROCESS_REQ_PAYOUT');
        finalUrl = finalUrl.replace('{seller}', reqData.sellerId);
        finalUrl = finalUrl.replace('{amount}', reqData.amount);
        finalUrl = finalUrl.replace('{currency}', reqData.currency);
        finalUrl = finalUrl.replace('{message}', reqData.message);
        finalUrl = finalUrl.replace('{customer}', reqData.customer);
        finalUrl = finalUrl.replace('{callback_url}', encodeURIComponent(reqData.callbackUrl));
        
        httpService.getRequest(
            finalUrl,
            function (response) {
                successCallback(response.data);
            },
            function (response) {
                errorCallback(response);
            }
        );
    };
}]);
