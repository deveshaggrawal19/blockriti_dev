"use strict";

angular.module("taurus.depositWithdrawModule").service('depositWithdrawService', ['httpService',
    'urlService',  'baseConfig', '$auth', '$state', '$rootScope', '$cookieStore',
    function (httpService, urlService, baseConfig, $auth, $state, $rootScope, $cookieStore) {


        this.getDepositBTC = function (successCallback,errorCallback){
            httpService.getData(urlService.getUrl('DEPOSIT_BTC'), null, function (response) {
                if (response && response.data)
                    successCallback(response.data);
            }, function (error) {
                errorCallback(error);
            });
        };

        this.getDepositCoupon = function (depositParams, successCallback,errorCallback){
            httpService.postData(urlService.getUrl('DEPOSIT_COUPON'), depositParams, function (response) {
                if (response && response.data)
                    successCallback(response.data);
            }, function (error) {
                errorCallback(error);
            });
        };

        this.getDepositInterac = function (depositParams, successCallback,errorCallback){
            httpService.postData(urlService.getUrl('DEPOSIT_INTERAC'), depositParams, function (response) {
                if (response && response.data)
                    successCallback(response.data);
            }, function (error) {
                errorCallback(error);
            });
        };


        this.getWithdrawBTC = function (withdrawParams, successCallback,errorCallback){
            httpService.postData(urlService.getUrl('WITHDRAW_BTC'), withdrawParams, function (response) {
                if (response && response.data)
                    successCallback(response.data);
            }, function (error) {
                errorCallback(error);
            });
        };

        this.getWithdrawWire = function (withdrawParams,successCallback,errorCallback){
            httpService.postData(urlService.getUrl('WITHDRAW_WIRE'), withdrawParams, function (response) {
                if (response && response.data)
                    successCallback(response.data);
            }, function (error) {
                errorCallback(error);
            });
        };

        this.getWithdrawCheque = function (withdrawParams,successCallback,errorCallback){
            httpService.postData(urlService.getUrl('WITHDRAW_CHEQUE'), withdrawParams, function (response) {
                if (response && response.data)
                    successCallback(response.data);
            }, function (error) {
                errorCallback(error);
            });
        };

        this.getWithdrawCoupon = function (withdrawParams,successCallback,errorCallback){
            httpService.postData(urlService.getUrl('WITHDRAW_COUPON'), withdrawParams, function (response) {
                if (response && response.data)
                    successCallback(response.data);
            }, function (error) {
                errorCallback(error);
            });
        };

        this.getDepositBank = function (withdrawParams,successCallback,errorCallback){
            httpService.postData(urlService.getUrl('DEPOSIT_BANK'), withdrawParams, function (response) {
                if (response && response.data)
                    successCallback(response.data);
            }, function (error) {
                errorCallback(error);
            });
        };

        this.getInteracAllowed = function (withdrawParams,successCallback,errorCallback){
            httpService.postData(urlService.getUrl('INTERAC_ALLOWED'), withdrawParams, function (response) {
                if (response && response.data)
                    successCallback(response.data);
            }, function (error) {
                errorCallback(error);
            });
        };

        this.getUserInfo=function(req,successCallback, errorCallback) {

            httpService.postData(urlService.getUrl('USER_INFO'), req, function (response) {
                if (response && response.data)
                    successCallback(response.data);
            }, function (error) {
                errorCallback(error);
            });
        }
    }
]);
