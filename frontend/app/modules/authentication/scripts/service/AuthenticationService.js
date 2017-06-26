angular.module("taurus.autheticationModule").service('authenticationService', ['httpService',
		 'urlService',  'baseConfig', '$auth', '$state', '$rootScope', '$cookieStore',
    function (httpService, urlService, baseConfig, $auth, $state, $rootScope, $cookieStore) {
        "use strict";

        //***************************** Log In Functionality ********************************************//*

        this.fetchKey = function (successCallback, errorCallback) {

            httpService.getData(urlService.getUrl('FETCH_KEY'), null, function (response) {
                if (response && response.data)
                    successCallback(response.data);
            }, function (error) {
                errorCallback(error);
            });
        };


        this.login = function (loginParamObject, successCallback, errorCallback) {

           httpService.postData(urlService.getUrl('LOGIN'), loginParamObject, function (response) {
                if (response && response.data)
                    successCallback(response.data);
            }, function (error) {
                errorCallback(error);
            });
        };


        this.setPin = function (setPinParamObject, successCallback, errorCallback) {

            httpService.postData(urlService.getUrl('SET_PIN'), setPinParamObject, function (response) {
                if (response && response.data)
                    successCallback(response.data);
            }, function (error) {
                errorCallback(error);
            });
        };

        this.authenticate=function(authParamObject,successCallback, errorCallback){
        	 /*successCallback({
             });*/

        	 httpService.postData(urlService.getUrl('AUTHENTICATE'), authParamObject, function (response) {
             if (response && response.data)
                 successCallback(response.data);
         		}, function (error) {
             	errorCallback(error);
         	});
        };

        this.getUserDetails = function (loginParamObject, successCallback, errorCallback) {

            var paramObject = new Object();
            paramObject.userIdntification = loginParamObject.idEmailCellIdentity;
            var url = urlService.getUrl('GET_USER_BASIC_INFO');

            httpService.getData(url, null, function (response) {
                if (response && response.data)
                    successCallback(response.data);
            }, function (error) {
                errorCallback(error);
            });

        };

        this.switchUser = function (isOrganizationUserMode, successCallback, errorCallback) {

            var paramObject = new Object();
            paramObject.isOrganizationUserMode = !isOrganizationUserMode;
            var url = urlService.getParameterizedUrl('SWITCH_USER', paramObject);

            httpService.getData(url, null, function (response) {
                if (response && response.data)
                    successCallback(response.data);
            }, function (error) {
                errorCallback(error);
            });

        };

        //***************************** Log Out Functionality ********************************************//*


        this.logout = function (successCallback) {
            $auth.logout().then(function (response) {
                $rootScope.isLoggedIn = false;
                $rootScope.loggedInUserDetails = null;
                localStorage.removeItem("isLoggedIn");
                localStorage.removeItem("_id");
                localStorage.removeItem("_token");
                localStorage.removeItem("type");
                localStorage.removeItem("client");
                $state.go("login");
                if (successCallback) {
                    successCallback();
                }
            }).catch(function (error) {});
        };

        //***************************** Forgot ID Functionality ********************************************//*

        this.forgotId = function (forgotIdParamObject, successCallback, errorCallback) {

            var paramObject = new Object();
            paramObject.userIdentificationCode = forgotIdParamObject.emailCellIdentity;
            paramObject.documentType = forgotIdParamObject.documentType;
            paramObject.documentReference = forgotIdParamObject.documentRef;
            paramObject.retrievalText = forgotIdParamObject.retrivalText;

            httpService.postData(urlService.getUrl('FORGOT_ID'), paramObject, function (response) {
                if (response && response.data)
                    successCallback(response.data);
            }, function (error) {
                errorCallback(error);
            });

        };

        //***************************** Forgot Password Functionality **************************************//*


        this.forgotPassword = function (req, successCallback, errorCallback) {
            // if (forgotPasswordParamObject.email && forgotPasswordParamObject.retrivalText) {
                // var url = urlService.getUrl('FORGOT_PASSWORD');
                // url = url + "?loginId=" + forgotPasswordParamObject.idEmailCellIdentity + "&retrievalText=" + forgotPasswordParamObject.retrivalText;
            httpService.postData(urlService.getUrl('FORGOT_PASSWORD'), req, function (response) {
                if (response && response.data)
                    successCallback(response.data);
            }, function (error) {
                errorCallback(error);
            });
            // }
        };

        this.forgotConfirm = function (req, successCallback, errorCallback) {
            httpService.postData(urlService.getUrl('FORGOT_CONFIRM'), req, function (response) {
                if (response && response.data)
                    successCallback(response.data);
            }, function (error) {
                errorCallback(error);
            });
        };

        //***************************** Reset Password Functionality **************************************//*


        this.resetPassword = function (resetPasswordParamObject, successCallback, errorCallback) {

            if (resetPasswordParamObject.id && resetPasswordParamObject.oldPassword && resetPasswordParamObject.newPassword) {

                var paramObject = new Object();
                paramObject.id = resetPasswordParamObject.id;
                paramObject.currentPassword = resetPasswordParamObject.oldPassword;
                paramObject.newPassword = resetPasswordParamObject.newPassword;

                httpService.postData(urlService.getUrl('RESET_PASSWORD'), paramObject, function (response) {
                    if (response && response.data) {
                        successCallback(response.data);
                    };
                }, function (error) {
                    errorCallback(error);
                });
            }
        };

        this.verifyEmail = function (paramObject, successCallback, errorCallback) {
                httpService.postData(urlService.getUrl('VERIFY_EMAIL'), paramObject, function (response) {
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
