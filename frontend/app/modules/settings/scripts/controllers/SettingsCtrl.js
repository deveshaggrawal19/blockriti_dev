'use strict';
angular.module('taurus.settingsModule')
    .controller('SettingsCtrl', ["$mdExpansionPanel", 'settingsService', 'toastMessagesService', "$scope", "$state", "$mdDialog", "clipboard", function ($mdExpansionPanel, settingsService, toastMessagesService, $scope, $state, $mdDialog, clipboard) {


        var vm = this;
        vm.api;
        vm.passwordChange;
        vm.referralLink;
        vm.phone;
        vm.pgp = 'Disabled';
        vm.gauth = 'Disabled';
        vm.qrimgsrc = null; //"https://chart.googleapis.com//chart?chs=150x150&chld=M|0&cht=qr&chl=otpauth%3A%2F%2Ftotp%2FTaurus%3Fsecret%3DJHOBO763Y43YQHQS";
        vm.pmail = 'Disabled';
        vm.psms = 'Disabled';
        vm.submitPasswordChange = submitPasswordChange;
        vm.submitPgpKey = submitPgpKey;
        vm.submitGoogleAuth = submitGoogleAuth;
        vm.submitApi = submitApi;
        vm.toggleAuthSwitch = toggleAuthSwitch;
        vm.getUserInfo = getUserInfo;
        vm.togglePmailSwitch = togglePmailSwitch;
        vm.togglePsmsSwitch = togglePsmsSwitch;
        $scope.userinfo = {};
        vm.continueVerification = continueVerification;
        vm.protectimusSmsNumber;
        // vm.changeProtectimusSms = changeProtectimusSms;
        vm.smsState = 'Disabled';
        vm.uploadedVerificationDocuments;
        vm.statusIcon = statusIcon;
        vm.statusTooltip = statusTooltip;
        vm.googleAuthForm;
        vm.googleAuthCode = "";
        vm.showGoogleAuthSubmit = true;
        vm.changePasswordProgressBar = true;
        vm.googleAuthenticatorProgressBar = true;
        vm.protectimusProgressBar = true;
        vm.pgpProgressBar = true;
        vm.userApis;
        vm.deleteApi = deleteApi;
        vm.apiProgressBar = true;
        vm.copyToClipboard = copyToClipboard;

        var twoFactorAuthToast = function (data) {
            switch (data.code) {
                case 101:
                    toastMessagesService.failureToast('Incorrect authentication method');
                    break;
                case 102:
                    toastMessagesService.failureToast('Incorrect telephone number');
                    break;
                case 103:
                    toastMessagesService.failureToast('Token access restricted');
                    break;
                case 104:
                    toastMessagesService.failureToast('Database entry does not exist');
                    break;
                default:
                    toastMessagesService.failureToast('Two factor authentication error');
            };
        };

        var changePasswordToast = function (data) {
            switch (data.code) {
                case 130:
                    toastMessagesService.successToast('Password changed');
                    break;
                case 122:
                    toastMessagesService.failureToast('Incorrect current password');
                    break;
                case 123:
                    toastMessagesService.failureToast('New and confirm passwords do not match');
                    break;
                default:
                    toastMessagesService.failureToast('Password change error');
            };
        };

        var pgpToast = function (data) {
            switch (data.code) {
                case 171:
                    toastMessagesService.failureToast('PGP key empty');
                    break;
                case 172:
                    toastMessagesService.failureToast('Invalid PGP key');
                    break;
                case 173:
                    toastMessagesService.failureToast('Flag should not be empty');
                    break;
                default:
                    toastMessagesService.failureToast('PGP error');
            };
        };

        var addApiToast = function(data) {
            switch (data.code) {
                case 190:
                    toastMessagesService.successToast('API added');
                    break;
                case 181:
                    toastMessagesService.failureToast('Not allowed more than 3 APIs');
                    break;
                case 182:
                    toastMessagesService.failureToast('Invalid bitcoin address');
                    break;
                case 183:
                    toastMessagesService.failureToast('Secret cannot be empty');
                    break;                    
                case 184:
                    toastMessagesService.failureToast('Only letters, spaces, and dashes allowed in API name');
                    break;
                case 185:
                    toastMessagesService.failureToast('API name length maximum is 30 characters');
                    break;
                case 186:
                    toastMessagesService.failureToast('Save error. Please try again.');
                    break;
                default:
                    toastMessagesService.failureToast('Create API error');
            };
        };

        var deleteApiToast = function(data) {
            switch (data.code) {
                case 300:
                    toastMessagesService.successToast('API deleted');
                    break;
                case 291:
                    toastMessagesService.failureToast('Code cannot be empty');
                    break;                    
                case 292:
                    toastMessagesService.failureToast('API does not exist');
                    break;
                default:
                    toastMessagesService.failureToast('Create API error');
            };
        };

        var disableOtherAuthenticationToast = function () {
            toastMessagesService.warnToast('You can only have one Two Factor Authentication method enabled at a time. Disable the other to contine.');
        };

        // var statusColors = {
        //     enabled:            'rgb(76,175,80)', //green
        //     disabled:           'rgb(255,87,34)', //orange (warn)

        // };
        vm.statusColor = statusColor;
        function statusColor(status) {
            switch(true) {
                case /Enabled/.test(status):
                    //green (accent)
                    return 'rgb(76,175,80)';
                case /Disabled/.test(status):
                    //md-warn, orange/red
                    return 'rgb(255,87,34)';
                default:
                    return ;
            }
        };


        vm.submitPhone = submitPhone;
        vm.togglePgpSwitch = togglePgpSwitch;
        vm.generateSecret = generateSecret;


        function continueVerification() {
            $state.go('verification');
        };

        function getCurrentSecuritySettings() {
            var type = localStorage.getItem('type');
            if (type == '2fauth') {
                vm.gauth = 'Enabled';
            } else if (type == 'pmail') {
                vm.pmail = 'Enabled';
            } else if (type == 'psms') {
                vm.psms = 'Enabled';
            };
        };

        var setLocalStorageType = function (type) {
            localStorage.setItem('type', type);
        };

        // var otherTwoFactorAuthEnabled = function (currentSwitch) {
        //     if 
        // };


        function getTwoFaDep() {
            settingsService.getTwoFaDep(function successBlock(data) {
                console.log(data);
            }, function failureBlock(error) {
                console.log(error);
            });

        };

        /*****************************Get User Info*******************************************/
        function getUserInfo() {
            var data = {"test": "true"};
            settingsService.getUserInfo(data, function successBlock(data) {
                $scope.userinfo = data;
                $scope.verification_status;
                console.log(data);
                if (data.pgp_status == 1) {
                    vm.pgp = 'Enabled';
                };
                console.log("data.verification_level: " + data.verification_level);
                switch (data.verification_level) {
                    case '-1':
                        $scope.verification_status = 'Email not verified';
                        break;
                    case '1':
                        $scope.verification_status = 'Pending admin approval';
                        break;
                    case '2':
                        $scope.verification_status = 'Documents approved';
                        break;
                    default:
                        console.log(status);
                        return;
                };
                console.log($scope.userinfo);
            }, function failureBlock(error) {
                console.log(error);
            });
        };


        getTwoFaDep();

        var getVerificationDocuments = function () {
            settingsService.getUploadedDocuments(function successBlock(response) {
                vm.uploadedVerificationDocuments = response;
            }, function failureBlock(error) {
            });
        };

        function statusIcon(status) {
            switch (status) {
                case 'approve':
                    return 'mdi-check-circle';
                case 'reject':
                    return 'mdi-alert-circle';
                case 'pending':
                    return 'mdi-timer-sand';
                default:
                    console.log(status);
                    return;
            };
        };

        function statusTooltip(status) {
            switch (status) {
                case 'approve':
                    return 'Approved';
                case 'reject':
                    return 'Rejected';
                case 'pending':
                    return 'Pending';
                default:
                    console.log(status);
                    return;
            };
        };


        //Auto Opens User Information Panel on page load
        $mdExpansionPanel().waitFor('userInfoPanel').then(function (instance) {
            instance.expand();
        });


        vm.passwordChange = {
            currentPassword:    '',
            newPassword:        '',
            retypePassword:     '',
        };

        function submitPasswordChange(data) {
            console.log(data);
            vm.changePasswordProgressBar = false;
            settingsService.changePassword(data, function (successResponse) {
                    vm.changePasswordProgressBar = true;
                    console.log('success');
                    console.log(successResponse);
                    vm.passwordChange = {
                        currentPassword:    '',
                        newPassword:        '',
                        retypePassword:     '',
                    };
                    changePasswordToast(successResponse);
                    vm.passwordChangeForm.$setUntouched();
                    vm.passwordChangeForm.$setPristine();
                    $mdExpansionPanel().waitFor('changePasswordPanel').then(function (instance) {
                        instance.collapse();
                    });
                },
                function (errorResponse) {
                    vm.changePasswordProgressBar = true;
                    console.log('failure');
                    console.log(errorResponse);
                    vm.passwordChange = {
                        currentPassword:    '',
                        newPassword:        '',
                        retypePassword:     '',
                    };
                    changePasswordToast(errorResponse.data);
                    vm.passwordChangeForm.$setUntouched();
                    vm.passwordChangeForm.$setPristine();
                });
        };

        function toggleAuthSwitch() {
            //If the two other types aren't enabled then continue. Otherwise, set switch to disabled and display toast
            if (localStorage.getItem('type') != 'psms' && localStorage.getItem('type') != 'pemail') {
                if (vm.gauth == 'Enabled') {
                    settingsService.getSecuritySettings(function (successResponse) {
                        vm.secret = successResponse.secret;
                        vm.qrimgsrc = successResponse.qrUrl;
                        vm.enabled = 'true';
                        console.log(successResponse);
                        // setLocalStorageType('2fauth');
                    }, function (errorResponse) {
                        console.log(errorResponse);
                    });
                } else if (localStorage.getItem('type') === '2fauth') {
                    vm.googleAuthenticatorProgressBar = false;
                    vm.qrimgsrc = null;
                    vm.enabled = 'false';
                    var inputParam = {"enable": vm.enabled}
                    settingsService.change2fa(inputParam, function (successResponse) {
                        console.log(successResponse);
                        vm.googleAuthenticatorProgressBar = true;
                        setLocalStorageType('pin');
                        vm.showGoogleAuthSubmit = true;
                        toastMessagesService.successToast('Google Authenticator Disabled');
                    }, function (errorResponse) {
                        console.log(errorResponse);
                        vm.googleAuthenticatorProgressBar = true;
                        twoFactorAuthToast(errorResponse);
                    });
                };
            } else {
                vm.gauth = 'Disabled';
                disableOtherAuthenticationToast();
            };
        };

        function copyToClipboard(textToCopy) {
            console.log(1);
            clipboard.copyText(textToCopy);
        };

        var confirmGoogleAuthEmergencyCode = function (code) {
            $mdDialog.show({
                // controller: 'SettingsCtrl as settingsCtrl',
                scope: $scope,
                preserveScope: true,
                template:   
                    '<md-dialog aria-label="Google Authenticator Emergency Code" class="google-auth-emergency-code-container">' + 
                        '<md-dialog-content class="md-dialog-content" layout="column" layout-align="start center" flex>' + 
                            '<h1>Google Authenticator Emergency Code</h1>' + 
                            '<p>Record and keep the following code in case you lose access to your Google Authenticator account.</p>' +
                            '<div layout="column" layout-align="center center">' +
                                '<code>' + code + '</code>' +
                                '<md-button ng-click="settingsCtrl.copyToClipboard(&quot;' + code + '&quot;)">' +
                                    'Copy' +
                                '</md-button>' +
                            '</div>' + 
                        '</md-dialog-content>' + 
                        '<md-dialog-actions layout="row">' +
                            '<span flex></span>' +
                            '<md-button type="button" ng-click="cancel()">' +
                                'Close' +
                            '</md-button>' +
                        '</md-dialog-actions>' +
                    '</md-dialog>',
                parent: angular.element(document.body),
                clickOutsideToClose: false,
                fullscreen: true,
            });
        };

        //Google Authenticator
        function submitGoogleAuth(authCode) {
            vm.googleAuthenticatorProgressBar = false;
            var inputParam = {"code": authCode, "two_factor_secret": vm.secret, "enable": vm.enabled};
            settingsService.change2fa(inputParam, function (successResponse) {
                console.log(successResponse);
                vm.googleAuthenticatorProgressBar = true;
                // toastMessagesService.successToast('Google Authenticator enabled');
                vm.googleAuthCode = "";
                vm.googleAuthForm.$setPristine();
                vm.googleAuthForm.$setUntouched();
                setLocalStorageType('2fauth');
                vm.showGoogleAuthSubmit = false;

                confirmGoogleAuthEmergencyCode(successResponse.reset_code);

                // vm.showGoogleAuthSubmit = false;
                // $mdExpansionPanel().waitFor('googleAuthPanel').then(function (instance) {
                //     instance.collapse();
                // });
            }, function (errorResponse) {
                console.log(errorResponse);
                vm.googleAuthenticatorProgressBar = true;
                twoFactorAuthToast(errorResponse.data);
                vm.googleAuthCode = "";
                vm.googleAuthForm.$setPristine();
                vm.googleAuthForm.$setUntouched();
            });
        };


        //Protectimus email
        function togglePmailSwitch() {
            //If the two other 2fa types aren't enabled then continue. Otherwise, set switch to disabled and display toast
            if (localStorage.getItem('type') != 'psms' && localStorage.getItem('type') != '2fauth') {
                vm.protectimusProgressBar = false;
                if (vm.pmail == 'Enabled') {
                    var inputParam = {"enable": "true"};
                    settingsService.changePmail(inputParam, function (successResponse) {
                        console.log(successResponse);
                        vm.protectimusProgressBar = true;
                        toastMessagesService.successToast('Protectimus email enabled');
                        setLocalStorageType('pmail');
                    }, function (errorResponse) {
                        console.log(errorResponse);
                        vm.protectimusProgressBar = true;
                        twoFactorAuthToast(errorResponse.data);
                        vm.pmail = 'Disabled';
                    });
                } else {
                    var inputParam = {"enable": "false"};
                    settingsService.changePmail(inputParam, function (successResponse) {
                        console.log(successResponse);
                        vm.protectimusProgressBar = true;
                        toastMessagesService.successToast('Protectimus email disabled');
                        setLocalStorageType('pin');
                    }, function (errorResponse) {
                        console.log(errorResponse);
                        vm.protectimusProgressBar = true;
                        twoFactorAuthToast(errorResponse.data);
                    });
                };
            } else {
                vm.pmail = 'Disabled';
                disableOtherAuthenticationToast();
            };

        };

        //Protectimus sms
        function togglePsmsSwitch() {
            //If the two other 2fa types aren't enabled then continue. Otherwise, set switch to disabled and display toast
            if ((localStorage.getItem('type') != 'pmail') && (localStorage.getItem('type') != '2fauth') ) {
                if (vm.psms == 'Enabled') {
                    // var inputParam = {"enable": "true"};
                    // settingsService.changePsms(inputParam, function (successResponse) {
                    //     console.log(successResponse);
                    //     setLocalStorageType('psms');
                    //     toastMessagesService.successToast('Protectimus SMS Enabled');
                    // }, function (errorResponse) {
                    //     console.log(errorResponse);
                    //     twoFactorAuthToast(errorResponse);
                    // });
                } else if (localStorage.getItem('type') == 'psms') {
                    var inputParam = {"enable": "false"};
                    vm.protectimusProgressBar = false;
                    settingsService.changePsms(inputParam, function (successResponse) {
                        console.log(successResponse);
                        vm.protectimusProgressBar = true;
                        setLocalStorageType('pin');
                        toastMessagesService.successToast('Protectimus SMS Disabled');
                    }, function (errorResponse) {
                        console.log(errorResponse);
                        vm.protectimusProgressBar = true;
                        twoFactorAuthToast(errorResponse.data);
                    });
                }
            } else {
                vm.psms = 'Disabled';
                disableOtherAuthenticationToast();
            };
        };


        function submitPhone() {
            console.log(vm.phone);
            vm.protectimusProgressBar = false;
            var inputParam = {"phone": vm.phone, "enable": "true"};
            settingsService.changePsms(inputParam, function (successResponse) {
                console.log(successResponse);
                vm.protectimusProgressBar = true;
                toastMessagesService.successToast('Protectimus SMS Enabled');
                setLocalStorageType('psms');
            }, function (errorResponse) {
                console.log(errorResponse.data);
                vm.protectimusProgressBar = true;
                twoFactorAuthToast(errorResponse.data);
            });
        }

        //PGP
        function togglePgpSwitch() {
            if (vm.pgp == 'Enabled') {
                var inputParam = {"enable": "true"};
            } else {
                var inputParam = {"enable": "false"};
                settingsService.changePgp(inputParam, function (successResponse) {
                    console.log(successResponse);
                    toastMessagesService.successToast('PGP Disabled');
                }, function (errorResponse) {
                    console.log(errorResponse);
                });
            }

        };

        function submitPgpKey(pgpKey) {
            console.log(pgpKey);
            vm.pgpProgressBar = false;
            var inputParam = {"pgpkey": pgpKey, "enable": "true"};
            settingsService.changePgp(inputParam, function (successResponse) {
                console.log(successResponse);
                vm.pgpProgressBar = true;
                toastMessagesService.successToast('PGP Enabled');
            }, function (errorResponse) {
                console.log(errorResponse);
                vm.pgpProgressBar = true;
                pgpToast(errorResponse.data)
            });
        };

        //API
        vm.api = {
            name:       '',
            secret:     '',
            withdraw:   '',
        };

        vm.showApiList;
        var apiListEmpty = function () {
            if (Array.isArray(vm.userApis)) {
                vm.showApiList = false;
            } else if (Object.keys(vm.userApis).length === 0) {
                vm.showApiList = false;
            } else {
                vm.showApiList = true;
            }
        };

        function submitApi(apiData) {
            console.log(apiData);
            console.log(vm.userApis);
            if (Object.keys(vm.userApis).length < 3) {
                vm.apiProgressBar = false;
                settingsService.addApi(apiData, function (successResponse) {
                    addApiToast(successResponse);
                    vm.apiProgressBar = true;
                    toastMessagesService.successToast('API added');
                    vm.api = {
                        name:       '',
                        secret:     '',
                        withdraw:   '',
                    };
                    generateSecret();
                    vm.apiForm.$setUntouched();
                    vm.apiForm.$setPristine();
                    getApis();
                }, function (errorResponse) {
                    vm.apiProgressBar = true;
                    addApiToast(errorResponse.data);
                });
            } else {
                toastMessagesService.failureToast('Only 3 APIs allowed at a time. Delete an API to continue.');
            };
        };

        function generateSecret() {
            var length = 40;
            var chars = "abcdefghijklmnopqrstuvwxyz!@#$%^&*()-+<>ABCDEFGHIJKLMNOP1234567890";
            var secret = "";
            for (var x = 0; x < length; x++) {
                var i = Math.floor(Math.random() * chars.length);
                secret += chars.charAt(i);
            }
            vm.api.secret = secret;
        };

        var getApis = function() {
            settingsService.getApis(function (successResponse) {
                console.log(successResponse);
                vm.userApis = successResponse;
                apiListEmpty();
            }, function (errorResponse) {
                console.log(errorResponse);
            });
        };
        getApis();


        function deleteApi(code) {
            var deleteParams = {"code": code};
            vm.apiProgressBar = false;
            settingsService.deleteApi(deleteParams, function (successResponse) {
                console.log(successResponse);
                deleteApiToast(successResponse); 
                vm.apiProgressBar = true;
                delete vm.userApis[code];
                console.log(vm.userApis);
                apiListEmpty();
            }, function (errorResponse) {
                vm.apiProgressBar = true;
                console.log(errorResponse);
                deleteApiToast(errorResponse.data); 
            });
        };

            function getReferral() {
                var data = {"test": "true"};
                settingsService.getReferral(data, function successBlock(data) {
                    vm.referralLink = data;
                    var holder = vm.referralLink.referralLink.split('?');
                    vm.referralLink.referralLink = holder[0] + '#/home?' + holder[1];
                    console.log(vm.referralLink)
                    }, function (errorResponse) {
                    console.log(errorResponse);
                }
                );
            };

        $scope.init = function () {
            //  $scope.depositSummary();
            // $scope.withdrawalSummary();
            generateSecret();
            getReferral();
            getUserInfo();
            getVerificationDocuments();
            getCurrentSecuritySettings();
        };
        $scope.init();

        $scope.cancel = function () {
            $mdDialog.cancel();
        };

    }]); //controller
