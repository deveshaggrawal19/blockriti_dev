"use strict";
/**
 * Creating httpInterceptor factory for
 *
 */
angular.module("taurus").factory("httpInterceptor", ["$rootScope", "$q","$cookies","$base64",
    function ($rootScope, $q, $cookies, $base64) {

        var numLoadings = 0;

        return {
            request: function (config) {
                if (config && config.headers && localStorage.getItem('pkey')) {
                	var crypt = new JSEncrypt();
            		crypt.setKey($base64.decode(localStorage.getItem('pkey')));
                	if (config.url.indexOf('rest/user/login')!=-1) {
                		config.headers['MERCHANT_CODE'] = "webUIn-g";
                		config.data={"data":crypt.encrypt(JSON.stringify(config.data))};
                	} else if (config.url.indexOf('rest/user/register') != -1 || config.url.indexOf('/rest/user/forgotConfirm') != -1) {
                        config.headers['MERCHANT_CODE'] = "webUIn-g";
                        config.data = JSON.stringify(config.data);
                    } else if (config.url.indexOf('/rest/user/authenticate') != -1) {
                		config.headers['MERCHANT_CODE']   = "webUIn-g";
                		config.headers['AUTH_USER']       = localStorage.getItem('client');
                        //config.headers['AUTH_TOKEN']      = localStorage.getItem('taurus_token');
                    	config.data={"data":crypt.encrypt(JSON.stringify(config.data))};
                    } else if (localStorage.getItem("isLoggedIn") && config.url.indexOf('rest/user/changePassword')!=-1 ) {  // For /settings - change password
                        config.headers['AUTH_USER']     = localStorage.getItem('client');
                        config.headers['AUTH_TOKEN']    = localStorage.getItem('taurus_token');
                        config.headers['MERCHANT_CODE'] = "webUIn-g";
                        config.data                     = { "data":crypt.encrypt(JSON.stringify(config.data)) };
                    } else if (localStorage.getItem("isLoggedIn") && config.url.indexOf('rest/user/changePin')!=-1 ) {  // For /settings - change pin
                        config.headers['AUTH_USER']     = localStorage.getItem('client');
                        config.headers['AUTH_TOKEN']    = localStorage.getItem('taurus_token');
                        config.headers['MERCHANT_CODE'] = "webUIn-g";
                        config.data = {"data": crypt.encrypt(JSON.stringify(config.data))};
                    } else if (localStorage.getItem("isLoggedIn") && config.url.indexOf('rest/user/setPin')!=-1 ) {  // For /settings - set pin
                        config.headers['AUTH_USER']     = localStorage.getItem('client');
                        //config.headers['AUTH_TOKEN']    = localStorage.getItem('taurus_token');
                        config.headers['MERCHANT_CODE'] = "webUIn-g";
                        config.data = {"data": crypt.encrypt(JSON.stringify(config.data))};
                    } else if (localStorage.getItem("isLoggedIn") && config.url.indexOf('/rest/withdrawal/')!=-1 ) {
                        config.headers['AUTH_USER']     = localStorage.getItem('client');
                        config.headers['AUTH_TOKEN']    = localStorage.getItem('taurus_token');
                        config.headers['MERCHANT_CODE'] = "webUIn-g";
                    } else if (localStorage.getItem("isLoggedIn") && config.url.indexOf('rest') != -1) {
                        	config.headers['AUTH_USER']    = localStorage.getItem('client');
                        	config.headers['AUTH_TOKEN']   = localStorage.getItem('taurus_token');
                        }
                } else if (config.url.indexOf('rest/user/verifyEmail') != -1) {
                    config.data = {"data": JSON.stringify(config.data)};
                };
                numLoadings++;

                return config || $q.when(config)

            },
            response: function (response) {

                if ((--numLoadings) === 0) {}

                return response || $q.when(response);

            },
            responseError: function (response) {

                if (!(--numLoadings)) {}

                if(!!response.data){
                    if(response.data.code >=21 && response.data.code <=25){
                        localStorage.clear();
                        localStorage.setItem('sessionExpired',true);
                        location.reload();
                    }
                }
                //applicationExceptionService.exceptionHandle(response);
                return $q.reject(response);
            }
        };


    }
]);

angular.module("taurus").config(function ($httpProvider) {
    $httpProvider.interceptors.push('httpInterceptor');
});
