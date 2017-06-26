"use strict";

angular.module("taurus.settingsModule").service('settingsService', ['httpService',
		 'urlService', 'baseConfig', '$auth', '$state', '$rootScope', '$cookieStore',
    function (httpService, urlService, baseConfig, $auth, $state, $rootScope, $cookieStore) {

		this.getTwoFaDep = function (successCallback,errorCallback){
			httpService.getData(urlService.getUrl('TWOFA_DEP'), null, function (response) {
				if (response && response.data)
					successCallback(response.data);
			}, function (error) {
				errorCallback(error);
			});
		};

		
		    //***************************** Change Password Functionality ********************************************//*
        this.changePassword = function (passwordChangeObject, successCallback, errorCallback) {
       
            console.log('changePassword Service');
           httpService.postData(urlService.getUrl('CHANGE_PASSWORD'), passwordChangeObject, function (response) {
                if (response && response.data)
                    successCallback(response.data);
            }, function (error) {
                errorCallback(error);
            });
        };

        //***************************** Change Pin Functionality ********************************************//*
        this.changePin = function (pinChangeObject, successCallback, errorCallback) {

            console.log('changePin Service');
            httpService.postData(urlService.getUrl('CHANGE_PIN'), pinChangeObject, function (response) {
                if (response && response.data)
                    successCallback(response.data);
            }, function (error) {
                errorCallback(error);
            });
        };
		
		/*******Google Authentication*************/
		this.getSecuritySettings = function (successCallback, errorCallback) {
           httpService.getData(urlService.getUrl('GET_SECURITY_SETTINGS'), null, function (response) {
                if (response && response.data)
                    successCallback(response.data);
            }, function (error) {
                errorCallback(error);
            });
        };
        this.change2fa = function (inputParamObject, successCallback, errorCallback) {

            httpService.postData(urlService.getUrl('CHANGE_2FAUTH'), inputParamObject, function (response) {
                 if (response && response.data)
                     successCallback(response.data);
             }, function (error) {
                 errorCallback(error);
             });
         };
        /*******Protectimus email Authentication*************/

        this.changePmail = function (inputParamObject, successCallback, errorCallback) {

            httpService.postData(urlService.getUrl('CHANGE_PMAIL'), inputParamObject, function (response) {
                if (response && response.data)
                    successCallback(response.data);
            }, function (error) {
                errorCallback(error);
            });
        };

        /*******Protectimus SMS Authentication*************/

        this.changePsms = function (inputParamObject, successCallback, errorCallback) {

            httpService.postData(urlService.getUrl('CHANGE_SMS'), inputParamObject, function (response) {
                if (response && response.data)
                    successCallback(response.data);
            }, function (error) {
                errorCallback(error);
            });
        };

         /*******************User Info*************/
		this.getUserInfo=function(req,successCallback, errorCallback) {
			
			    httpService.postData(urlService.getUrl('USER_INFO'), req, function (response) {
                 if (response && response.data)
                     successCallback(response.data);
             }, function (error) {
                 errorCallback(error);
             });
		}

        /*******PGP*************/

        this.changePgp = function (inputParamObject, successCallback, errorCallback) {

            httpService.postData(urlService.getUrl('CHANGE_PGP'), inputParamObject, function (response) {
                if (response && response.data)
                    successCallback(response.data);
            }, function (error) {
                errorCallback(error);
            });
        };

        /******* API *************/

        this.addApi = function (inputParamObject, successCallback, errorCallback) {

            httpService.postData(urlService.getUrl('API_CONF'), inputParamObject, function (response) {
                if (response && response.data)
                    successCallback(response.data);
            }, function (error) {
                errorCallback(error);
            });
        };

        this.getApis = function (successCallback, errorCallback) {

            httpService.postData(urlService.getUrl('GET_APIS'), {"test": "true"}, function (response) {
                if (response && response.data)
                    successCallback(response.data);
            }, function (error) {
                errorCallback(error);
            });
        };

        this.deleteApi = function (inputParamObject, successCallback, errorCallback) {

            httpService.postData(urlService.getUrl('DELETE_API'), inputParamObject, function (response) {
                if (response && response.data)
                    successCallback(response.data);
            }, function (error) {
                errorCallback(error);
            });
        };




        /*******Referral link*************/

        this.getReferral = function (inputParamObject, successCallback, errorCallback) {

            httpService.postData(urlService.getUrl('REFERRAL'), inputParamObject, function (response) {
                if (response && response.data)
                    successCallback(response.data);
            }, function (error) {
                errorCallback(error);
            });
        };

        /******* Uploaded Documents *************/
        this.getUploadedDocuments = function (successCallback, errorCallback) {
            httpService.getData(urlService.getUrl('UPLOADED_DOCUMENTS'), {"test": "true"}, function (response) {
                if (response && response.data)
                    successCallback(response.data);
            }, function (error) {
                errorCallback(error);
            });
        };

    }
]);

