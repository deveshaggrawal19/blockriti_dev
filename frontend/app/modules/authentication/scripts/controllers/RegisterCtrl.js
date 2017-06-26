/*"use strict";
*//**
 * Creating Register controller for registering user in to system
 *
 */
angular.module("taurus.autheticationModule").controller("RegisterCtrl", ["$scope",'$rootScope', 'registerService',"$auth","$cookies","$state","urlService","$mdSidenav", "$mdDialog", "$mdToast", "toastMessagesService", "authenticationService", "$base64", "$location", "ShareEmailService",
    function ($scope, $rootScope, registerService, $auth, $cookies, $state, urlService, $mdSidenav, $mdDialog, $mdToast, toastMessagesService, authenticationService, $base64, $location, ShareEmailService) {

        var vm = this;
        $scope.user = {};
        $scope.register = {};
        $scope.sysOrgList = [];
        $scope.languageList = [];
        $rootScope.showSpinner = false;
        $rootScope.showSpinner = false;
        vm.showProgressBar = true;
        vm.submitAttempt = false;  //used to track if submit attempt has been made in order to display validation for checkbox for TOS
        vm.goToTermsOfService = goToTermsOfService;
        vm.showTermsOfService = false;
        vm.registerForm;
        vm.registerTosForm;
        vm.registerBasicInfoWidth;


        function goToTermsOfService() {
            vm.registerBasicInfoWidth = parseInt($('#register-basic-info').css('width')) + 2 * parseInt($('#register-basic-info').css('padding'));
            if (vm.registerForm.$valid) {
                vm.showTermsOfService = true;
            }
        };
        //***************************** Individual user sign up Functionality ********************************************//*
         var registerToast = function (data) {
             switch (data.code) {
                 case 140:
                     toastMessagesService.successToast('Please check your email to verify your account');
                     break;
                 case 131:
                     toastMessagesService.failureToast('Email address invalid');
                     break;
                 case 132:
                     toastMessagesService.failureToast('Confirm password does not match');
                     break;
                 case 133:
                     toastMessagesService.failureToast('Email address in use');
                     break;
                 case 134:
                     toastMessagesService.failureToast('First name cannot be empty');
                     break;
                 case 135:
                     toastMessagesService.failureToast('Last name cannot be empty');
                     break;
                 default:
                     toastMessagesService.failureToast('Registration error');
             };
         };

        vm.register = {
            "email":                ShareEmailService.getEmail(),
            "firstName":            "",
            "lastName":             "",
            "phone":                "",
            "password":             "",
            "passwordConfirm":      "",
            "g-recaptcha-response": "",
            "referrer":             "",
            "tos":                  undefined, 
        };

        $scope.$watch(function () { 
            return ShareEmailService.getEmail(); 
        }, function (newValue, oldValue) {
            if (newValue !== oldValue) {
                vm.register.email = newValue;
            };
        });        

        var urlParams = $location.search();
        if (urlParams.hasOwnProperty('ref')) {
            vm.register.referrer = urlParams.ref;
        };


        vm.registerSubmit = registerSubmit;

        function registerSubmit() {
            vm.registerTosForm.tos.$setTouched(); 
            if (vm.registerTosForm.$valid) {
                vm.showProgressBar = false;
                var googleRecaptchaResponseToken = grecaptcha.getResponse();
                if (googleRecaptchaResponseToken) {
                    if (vm.register.email && vm.register.firstName && vm.register.lastName && vm.register.password) {
                        authenticationService.fetchKey(function (successResponse) {
                            localStorage.setItem("pkey", successResponse.key);
                            var crypt = new JSEncrypt();
                            crypt.setKey($base64.decode(localStorage.getItem('pkey')));

                            var registerParams = {
                                "email":                vm.register.email,
                                "first_name":           vm.register.firstName,
                                "last_name":            vm.register.lastName,
                                "phone":                vm.register.phone,
                                "g-recaptcha-response": googleRecaptchaResponseToken,
                                //"password": vm.register.password, "confirm_password": vm.register.passwordConfirm
                                "data":                 crypt.encrypt(JSON.stringify({"password": vm.register.password, "confirm_password": vm.register.passwordConfirm}))
                            };
                            if (vm.register.referrer) {
                                registerParams.referrer = vm.register.referrer;
                            };

                            registerService.registerPost(registerParams, function successBlock(data) {
                                registerToast(data);
                                vm.showProgressBar = true;
                                $scope.cancel();
                            }, function failureBlock(error) {
                                registerToast(error.data);
                                vm.showProgressBar = true;
                                vm.register.tos = "";
                                vm.showTermsOfService = false;
                            });
                        }, 
                        function(errorResponse) {
                             vm.showProgressBar = true;
                             
                        });

                        // });
                    } else {
                        vm.showProgressBar = true;
                    };
                } else {
                    toastMessagesService.failureToast('You must first prove you are not a robot');
                    vm.showProgressBar = true;

                };
            }
        };


        // var testRegister =
        // {
        //     "first_name": 'Nach',
        //     "last_name": 'kuk',
        //     "email": 'nachiket.test@alulimtech.com',
        //     "data": 'SNLqdxmjTN5E27/w8dQuYzsDCoAYywqEiSP1YAhqP9CPBd3SdG6yPBdsNQmxk/1Y7vNjyd8rckA6sa0qtdQO7Zmxkafui9sJE3ai0B8F80lwIgcCla8K5izrBTIvGSNueot0+rwVUlQb8ouKH6rOjXF1zm0g7yigKWd8JhV04/0='
        //
        // };

        // $scope.registerPost = function registerPost(registerParamObject) {
        //     console.log("hi samey");
        //     registerService.registerPost(function successBlock(data) {
        //         console.log("this is the register call" + data);
        //     },
        //         function failureBlock(error) {
        //
        //     });
        //
        // }

        // $scope.registerPost(testRegister);
       /* $scope.onCancelClick = function () {
            authenticationService.logout();
        };

        $scope.loadListOfOrganizations = function () {
            registerService.loadListOfOrganizations(function (successResponse) {
                    if (!utilService.isEmpty(successResponse)) {
                        $scope.sysOrgList = successResponse;
                    }
                },
                function (errorResponse) {});
        };
		*/
        /***************************** New user sign in Functionality ********************************************/
/*
        $scope.loadListOfLanguages = function () {
            $scope.user.signIn = baseService.getLoggedInUser();
            registerService.loadListOfLanguages(function (successResponse) {
                    if (!utilService.isEmpty(successResponse)) {
                        $scope.languageList = successResponse;
                    }
                },
                function (errorResponse) {});
        };

        $scope.newUserSignIn = function (newUserSignInForm) {
            if (newUserSignInForm.$valid) {
                 Validate new password and confirm password as per Business Rule
                if (newUserSignInForm.$valid) {
                    if ($scope.user.signIn.cell1 === $scope.user.signIn.cell2) {
                        notify("Error", $translate.instant("PRI_ALT_MOBILE_NUM_DIFFERENT"));
                        $scope.user.signIn.cell2 = "";
                    } else if ($scope.user.signIn.email1 === $scope.user.signIn.email2) {
                        notify("Error", $translate.instant("PRI_ALT_EMAIL_DIFFERENT"));
                        $scope.user.signIn.email2 = "";
                    } else {
                        $rootScope.showSpinner = true;
                        $scope.updateNewUserSignInDetails();
                    }
                }
            }
        };
*/
        $scope.checkPassword = function () {
            var inValidNewPassword = utilService.checkPasswordPolicy($scope.user.signIn.newPassword);
            if (inValidNewPassword) {
                notify("Error", $translate.instant("INVALID_NEW_PASSWORD"));
                $scope.user.signIn.newPassword = "";
                $scope.user.signIn.confirmPassword = "";
            } else if ($scope.user.signIn.newPassword !== $scope.user.signIn.confirmPassword) {
                $scope.user.signIn.confirmPassword = "";
            }

        };

        $scope.repeatPasswordCheck = function () {
            var inValidRepeatNewPassword = utilService.checkPasswordPolicy($scope.user.signIn.confirmPassword);
            if (!inValidRepeatNewPassword) {
                if ($scope.user.signIn.newPassword !== $scope.user.signIn.confirmPassword) {
                    notify("Error", $translate.instant("NEWPASS_CONFIRMPASS_SAME"));
                    $scope.user.signIn.confirmPassword = "";
                }
            } else {
                notify("Error", $translate.instant("INVALID_CONFIRM_PASSWORD"));
                $scope.user.signIn.confirmPassword = "";
            }
        };

        $scope.updateNewUserSignInDetails = function () {

            registerService.newUserSignIn($scope.user.signIn, function (successResponse) {
                    $rootScope.showSpinner = false;
                    if (!utilService.isNullOrEmpty(successResponse.successMessage)) {
                        notify("success", successResponse.successMessage);
                    }
                    $scope.user.signIn = {};
                    authenticationService.logout();
                },
                function (errorResponse) {
                    $rootScope.showSpinner = false;
                });
        };


        $scope.cancel = function () {
            $mdDialog.cancel();
        };


}]);
