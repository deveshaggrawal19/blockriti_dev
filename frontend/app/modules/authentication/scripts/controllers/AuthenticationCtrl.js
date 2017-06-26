"use strict";
/**
 * Creating Log In controller  for validate user and log in to system
 *
 */

angular.module("taurus.autheticationModule").controller("AuthenticationCtrl", ["$scope",'$rootScope', "authenticationService","$auth","$cookies","$state","urlService","$mdSidenav", "$mdDialog", "toastMessagesService","$location", "$timeout", "$mdKeyboard",

    function ($scope, $rootScope, authenticationService, $auth, $cookies, $state, urlService, $mdSidenav, $mdDialog, toastMessagesService, $location, $timeout, $mdKeyboard) {

        var vm = this;

        $scope.disableEmailCell = false;
        $scope.disableDocType = false;
        $scope.disableDocRef = true;
        $scope.error = false;

        $scope.errorEmailCell = false;
        $scope.errorDocType = false;
        $scope.errorDocRef = false;

        $rootScope.isLoggedIn = localStorage.getItem("isLoggedIn");

        vm.validateForgottenPasswordForm    = validateForgottenPasswordForm;
        vm.submitForgottenPassword          = submitForgottenPassword;
        vm.setPinSubmit                     = setPinSubmit;
        vm.forgottenPasswordData = {
            id:         "",
            dob:        "",
            country:    "",
        };
        vm.showProgressBar = true;

        vm.displaySecondAuthenticationType = displaySecondAuthenticationType;

        var loginSuccessResponse;

        $scope.$watch('loginSubmit', function() {
            if($scope.loginSubmit === true) {
                $timeout(function() {
                    $("#login-2fa-code").focus();
                    if (!$("#login-2fa-code").is(":focus")) {
                        $timeout(function() {
                            $("#login-2fa-code").focus();
                        }, 50);
                    };
                }, 50);
                $("#login-2fa-code").on('keydown',function(evt) {
                    if (evt.keyCode == 27) {
                       $mdKeyboard.hide();
                    };
                });
            } else {
                $mdKeyboard.hide();
            };
        });

        $scope.$watch(function() {
            return vm.needHelp;
        }, function() {
            if (vm.needHelp === true) {
                $timeout(function() {
                    $("#forgot-password-autofocus").focus();
                }, 50);
            };
        });


        var forgottenPasswordToast = function (data) {
            switch (data.code) {
                case 241:
                    toastMessagesService.failureToast('User does not exist');
                    break;
                case 242:
                    toastMessagesService.failureToast('Captcha is incorrect');
                    break;
                case 243:
                    toastMessagesService.failureToast('Email does not exist');
                    break;
                default:
                    toastMessagesService.failureToast('Error on forgotten password');
            };
        };

        var setPinToast = function (data) {
            switch (data.code) {
                case 270:
                    toastMessagesService.successToast('PIN successfully set');
                    break;
                case 261:
                    toastMessagesService.failureToast('PIN should be digits only');
                    break;
                case 262:
                    toastMessagesService.failureToast('PIN length must be between 4 and 6');
                    break;
                case 263:
                    toastMessagesService.failureToast('PIN already set');
                    break;
                case 264:
                    toastMessagesService.failureToast('User unauthorized');
                    break;                                    
                default:
                    toastMessagesService.failureToast('Error on set PIN');
            };
        };


        var authenticateToast = function (data) {
            switch (data.code) {
                case 40:
                    toastMessagesService.successToast('Logged in');
                    break;
                case 31:
                    toastMessagesService.failureToast('Code is incorrect');
                    break;
                case 32:
                    toastMessagesService.failureToast('Account blocked due to too many failed attempts.');
                    break;
                case 34:
                    toastMessagesService.failureToast('Code is incorrect');
                    break;
                case 39:
                    toastMessagesService.failureToast('Unrecognized authentication method');
                    break;                                    
                default:
                    toastMessagesService.failureToast('Error on authentication');
            };
        };

        function displaySecondAuthenticationType() {
            switch (vm.twoFa) {
                case '2fauth':
                    return 'Google Authenticator';
                case 'pin':
                    return 'Security PIN';
                case 'psms':
                    return 'Protectimus SMS';
                case 'pmail':
                    return 'Protectimus Mail';
                default:
                    return vm.twoFa;
            };
        };

        function validateForgottenPasswordForm(formData) {
              return formData.$valid;
        };

        function submitForgottenPassword(req) {

            var googleRecaptchaResponseToken = grecaptcha.getResponse();
            if (googleRecaptchaResponseToken) {
                var forgottenPasswordParams = {
                     "id":       req.email,
                     //"dob":      pad(req.dob.getDate(),2) + "/" + pad((req.dob.getMonth()+1),2) + "/" + req.dob.getFullYear(),
                     //"country":  req.country
                     "g-recaptcha-response": googleRecaptchaResponseToken
                };

                authenticationService.forgotPassword(forgottenPasswordParams, function successBlock(data) {

                    toastMessagesService.successToast('Please check your email');
                    $scope.cancel();
                }, function failureBlock(error) {
                    forgottenPasswordToast(error.data);
                    // toastMessagesService.failureToast('Error: ' + forgottenPasswordParams);
                });
            } else {
                toastMessagesService.failureToast('You must first prove you are not a robot');
            };
        };


        vm.demoRegister = {
            email: "",
        };
 
        vm.goDemo = goDemo;
        function goDemo() {
            $state.go('demo');
            $scope.cancel();
        };



        $scope.loginSubmit  = false;
        vm.needHelp         = false;
        vm.failureToast     = failureToast;
        vm.successToast     = successToast;

        function successToast(message) {
            toastMessagesService.successToast(message);
        };
        function failureToast(message) {
            toastMessagesService.failureToast(message);
        };



        function pad (str, max) {
            str = str.toString();
            return str.length < max ? pad("0" + str, max) : str;
        };




        /***************************** Log In Functionality ********************************************/
        $scope.authenticate = function () {
            if ($scope.code) {
                vm.showProgressBar = false;
                $('#login-2fa-code').blur();
                
        		var newObject = new Object();
                // newObject.type= localStorage.getItem("type");
        		newObject.type = loginSuccessResponse.type;

        		newObject.code = $scope.code;

                // newObject.session_id = localStorage.getItem("_id");
                newObject.session_id = loginSuccessResponse._id;

                // newObject.user_id = localStorage.getItem("client");
                newObject.user_id = loginSuccessResponse.client;
                authenticationService.authenticate(newObject, function(successResponse){
                    localStorage.setItem("taurus_token", successResponse._token);
                    localStorage.setItem("access_token", successResponse._accessToken);

                    vm.showProgressBar = true;
                    $scope.cancel();
                    // var verification_level = localStorage.getItem('verification_level');
		            var verification_level = loginSuccessResponse.verification_level;
                    console.log(successResponse);
                    localStorage.setItem("firebase_token", successResponse._accessToken);
                    firebase.auth().signInWithCustomToken(successResponse._accessToken).then(function (result) {
                        console.log(result);
                    }).catch(function (error) {
                        console.log(error);
                    });
                    authenticateToast(successResponse)
                        // if(verification_level == 0 || verification_level == 1 || verification_level == 2) {
                        // $state.go("verification");
                        // } else {
                        $state.go("trade");

                        // }
        		}, function(errorResponse) {
                    $("#login-2fa-code").focus();
                    $scope.code = "";
                    vm.showProgressBar = true;
                    authenticateToast(errorResponse.data);
            	});
        	}
        };

        $scope.login = function (userLoginForm) {

            if ($scope.user) {
                //var inValidPassword = utilService.checkPasswordPolicy($scope.user.login.password);
                if ($scope.user.login.id && $scope.user.login.password) {


                	authenticationService.fetchKey(function(successResponse) {


            			localStorage.setItem("pkey", successResponse.key);

            			/*------------------------------------------------*/
                    vm.showProgressBar = false;
                    authenticationService.login($scope.user.login, function (accessTokenSuccessResponse) {

                            if (accessTokenSuccessResponse) {

                                $auth.setToken(accessTokenSuccessResponse._token);

                                /*$cookies.put('_token', accessTokenSuccessResponse._token, {
                                    path: '/'s
                                });
                                $cookies.put('client', accessTokenSuccessResponse.client, {
                                    path: '/'
                                });*/
                                loginSuccessResponse = accessTokenSuccessResponse;
                                $rootScope.isLoggedIn = true;
                                localStorage.setItem("isLoggedIn", true);
                                localStorage.setItem("_id", accessTokenSuccessResponse._id);
                                localStorage.setItem("client", accessTokenSuccessResponse.client);
                                localStorage.setItem("type", accessTokenSuccessResponse.type);
								localStorage.setItem("verification_level", accessTokenSuccessResponse.verification_level);
                                localStorage.setItem("approval_level", accessTokenSuccessResponse.approval_level);
                                localStorage.setItem("first_login", accessTokenSuccessResponse.first_login);

                                for (var i in accessTokenSuccessResponse.limits.btc_cad) {
                                    localStorage.setItem(i, accessTokenSuccessResponse.limits.btc_cad[i]);
                                };

                                vm.twoFa = localStorage.getItem("type");
                                vm.isFirstLogin = localStorage.getItem("first_login");




                                // "limits": {
                                //         "btc_cad": {
                                                // "min_rate":"10.00",
                                                // "max_rate":"5000.00",
                                                // "min_amount":"0.00500000",
                                                // "max_amount":"1000.00000000",
                                                // "min_value":"1.00",
                                                // "max_value":"1000000.00"
                                        // }
                                // }




                               /* authenticationService.getUserDetails($scope.user.login, function (successResponse) {

                                        if (successResponse) {
                                            baseService.setLoggedInUser(successResponse);
                                            $rootScope.loggedInUserDetails = successResponse;
                                            $scope.user.login = {};
                                            $scope.selectLanguage();
                                            if (!successResponse.redirectPage) {
                                                if (successResponse.redirectPage === "FIRST_LOGIN") {
                                                    $state.go("newUserSignIn");
                                                } else if (successResponse.redirectPage === "RESET_PASSWORD_PAGE") {
                                                    $state.go("resetPassword");
                                                } else {
                                                    $state.go("home.view");
                                                }
                                            }
                                        }
                                    },
                                    function (errorResponse) {

                                        $scope.user.login.idEmailCellIdentity = "";
                                        $scope.user.login.password = "";
                                        $state.go("home.view");
                                    });
                            }*/
                                vm.showProgressBar = true;
                                $scope.loginSubmit = true;
                                // $state.go("authenticate");
                                //$scope.showAuthenticate();
                        }},
                        function (errorResponse) {
                            $("#login-email-input").focus();
                            $scope.user.login.id = "";
                            $scope.user.login.password = "";
                            vm.showProgressBar = true;
                            failureToast('Invalid Credentials');
                        });

                    /*------------------------------------------------*/

                	}, function (errorResponse) {
                        vm.failureToast('Invalid credentials');
                        vm.showProgressBar = true;
               		});
                }
            }
        };

        /***************************** setPin ********************************************/
        vm.setPin = {code: "", session_id:"",user_id:"" };
        function setPinSubmit () {
            vm.showProgressBar = false;
            var sessionId = localStorage.getItem("_id");
            var userId = localStorage.getItem("client");
            var inputParams = { code:vm.setPin.code, session_id: sessionId, user_id: userId  };

            authenticationService.setPin(inputParams, function (successResponse) {
                    localStorage.setItem("taurus_token", successResponse._token);
                    localStorage.setItem("access_token", successResponse._accessToken);
                    vm.showProgressBar = true;
                    console.log(successResponse);
                    setPinToast(successResponse);
                    $scope.cancel();
                    $state.go('verification');
                },
                function (errorResponse) {
                    vm.showProgressBar = true;
                    console.log(errorResponse);
                    setPinToast(errorResponse.data);
                });
        };




        /***************************** Log Out Functionality ********************************************/

        $scope.logout = function () {
        	  authenticationService.logout(function () {
              });
           /* var url = urlService.getUrl('LOGOUT');
            httpService.getData(url, null, function (response) {
                authenticationService.logout(function () {
                    $scope.loadMenuContents();
                });
            }, function (error) {
                authenticationService.logout(function () {
                    $scope.loadMenuContents();
                });
            });*/
        };

        /***************************** Forgot ID Functionality ********************************************/


        $scope.enableDisableField = function (docType) {
            $scope.user.forgotid.documentRef = "";
            if ($scope.user.forgotid.documentType === " ") {
                $scope.disableEmailCell = false;
                $scope.disableDocType = false;
                $scope.disableDocRef = true;

                $scope.errorEmailCell = true;
                $scope.errorDocRef = false;
            } else if (docType) {
                $scope.disableEmailCell = false;
                $scope.disableDocRef = true;
                $scope.disableDocType = true;

                $scope.errorDocType = false;
                $scope.errorDocRef = false;
            } else {
                $scope.disableDocRef = false;
                $scope.disableDocType = false;

                $scope.disableEmailCell = true;
                $scope.errorEmailCell = false;

            }
        };

        // $scope.forgotid = function (forgotIdForm) {
        //     if ($scope.user.forgotid) {
        //         $scope.errorEmailCell = true;
        //         $scope.errorDocType = true;
        //         $scope.errorDocRef = true;
        //     } else {
        //         if ($scope.user.forgotid.emailCellIdentity && $scope.user.forgotid.documentType) {
        //             $scope.errorEmailCell = true;
        //             $scope.errorDocType = true;
        //         } else {
        //             if (!$scope.user.forgotid.emailCellIdentity && !$scope.user.forgotid.retrivalText) {
        //                 $scope.sendforgotid();
        //             } else if (!$scope.user.forgotid.documentType && !$scope.user.forgotid.documentRef &&
        //                 !$scope.user.forgotid.retrivalText) {
        //                 $scope.sendforgotid();
        //             }
        //         }
        //     }
        // };

        // $scope.sendforgotid = function () {

        //     authenticationService.forgotId($scope.user.forgotid, function (successResponse) {

        //             if (!successResponse.successMessage) {
        //                 notify("success", successResponse.successMessage);
        //             }
        //             $scope.user.forgotPassword = {};
        //             $state.go("login");
        //         },
        //         function (errorResponse) {

        //             $scope.user.forgotid = {};
        //             $scope.user.forgotid.documentType = " ";
        //             $scope.errorEmailCell = true;
        //             $scope.errorDocType = true;
        //             $scope.errorDocRef = true;

        //             $scope.disableEmailCell = false;
        //             $scope.disableDocType = false;
        //             $scope.disableDocRef = true;
        //         });
        // };


        /***************************** Forgot Password Functionality **************************************/

        $scope.forgotpassword = function (forgotPasswordForm) {
            if (forgotPasswordForm.$valid) {

                authenticationService.forgotPassword($scope.user.forgotPassword, function (successResponse) {

                        if (!successResponse.successMessage) {
                            notify("success", successResponse.successMessage);
                        }
                        $scope.user.forgotPassword = {};
                        $state.go("login");
                    },
                    function (errorResponse) {

                        $scope.user.forgotPassword.idEmailCellIdentity = "";
                        $scope.user.forgotPassword.retrivalText = "";
                    });
            }
        };

        /***************************** Reset Password Functionality ****************************************/

        $scope.showPrePopulateId = function () {
            var userDetails = baseService.getLoggedInUser();
            if (!userDetails && !userDetails.userCode) {
                $scope.user = {
                    resetPassword: {
                        id: userDetails.userCode
                    }
                };
            }
        };

        $scope.resetpassword = function (resetPasswordForm) {

            if (resetPasswordForm.$valid) {
                $scope.updateresetpassword();
            }
        };

        $scope.checkValidPassword = function () {
            // Validate old password,new password and repeat new password as per Business Rule
            var inValidNewPassword = utilService.checkPasswordPolicy($scope.user.resetPassword.newPassword);
            if (inValidNewPassword) {
                notify("Error", $translate.instant("INVALID_NEW_PASSWORD"));
                $scope.user.resetPassword.newPassword = "";
                $scope.user.resetPassword.repeatNewPassword = "";
            } else if ($scope.user.resetPassword.newPassword !== $scope.user.resetPassword.repeatNewPassword) {
                $scope.user.resetPassword.repeatNewPassword = "";
            }
        };

        $scope.repeatPasswordCheck = function () {
            // Validate old password,new password and repeat new password as per Business Rule
            var inValidRepeatNewPassword = utilService.checkPasswordPolicy($scope.user.resetPassword.repeatNewPassword);
            if (!inValidRepeatNewPassword) {
                if ($scope.user.resetPassword.newPassword !== $scope.user.resetPassword.repeatNewPassword) {
                    notify("Error", $translate.instant("NEWPASS_CONFIRMPASS_SAME"));
                    $scope.error = true;
                    $scope.user.resetPassword.repeatNewPassword = "";
                }
            } else {
                notify("Error", $translate.instant("INVALID_CONFIRM_PASSWORD"));
                $scope.user.resetPassword.repeatNewPassword = "";
            }


        };

        $scope.updateresetpassword = function () {

            authenticationService.resetPassword($scope.user.resetPassword, function (successResponse) {

                    if (!successResponse.successMessage) {
                        notify("success", successResponse.successMessage);
                    }
                    $scope.user.resetPassword = {};
                    authenticationService.logout();
                },
                function (errorResponse) {

                    $scope.user.resetPassword.oldPassword = "";
                    $scope.user.resetPassword.newPassword = "";
                    $scope.user.resetPassword.repeatNewPassword = "";
                });
        };

        /**
         * [[Description]]
         */
        $scope.switchUser = function () {
            if (!$rootScope.loggedInUserDetails && !$rootScope.loggedInUserDetails.isOrganizationUser) {
                if (!$rootScope.loggedInUserDetails.isOrganizationUserMode) {
                    authenticationService.switchUser($rootScope.loggedInUserDetails.isOrganizationUserMode, function (successResponse) {
                            if (!successResponse) {
                                baseService.setLoggedInUser(successResponse);
                                $rootScope.loggedInUserDetails = successResponse;
                                $scope.loadMenuContents();
                                $state.go("home.view");
                            }
                        },
                        function (errorResponse) {});
                }
            }
        };

        $scope.onCancelClick = function () {
            authenticationService.logout();
        };


        /***************************** Common Functions ****************************************/

        $scope.goToIndividualUserSignUp = function () {
            $state.go("individualUserSignUp");
        };


        // $scope.showAuthDialog=function (ev, name) {
        //     var name = name;
        //     $mdDialog.show({
        //         controller: 'AuthenticationCtrl',
        //         templateUrl: 'modules/authentication/views/' + name + '.html',
        //         parent: angular.element(document.body),
        //         targetEvent: ev,
        //         clickOutsideToClose:true
        //     });
        // };
        $scope.cancel = function () {
            $mdDialog.cancel();
            $mdKeyboard.hide();
        };



    }
    ]);
