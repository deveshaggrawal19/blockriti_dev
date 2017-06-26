angular.module('taurus.autheticationModule').controller('VerifyEmailCtrl', ["$scope", "$location", "authenticationService", "$base64", "toastMessagesService", "$state", 
    function ($scope, $location, authenticationService, $base64, toastMessagesService, $state) {
     
        var vm = this;
        // vm.submitResetPassword = submitResetPassword;
        var verifyEmail = {
            "email":    "",
            "secret":   "",
        };
        vm.verifiedStatus = "Verifying email...";


        var verifyEmailToast = function (data) {
            switch (data.code) {
                case 150:
                    toastMessagesService.successToast('Email verified!');
                    break;
                case 141:
                    toastMessagesService.failureToast('Invalid or expired link');
                    break;
                case 142:
                    toastMessagesService.failureToast('Malformed token');
                    break;    
             default:
                 toastMessagesService.failureToast('Email verification error');
            };
        };

        var submitEmailVerification = function() {
            var urlParams = $location.search();
            console.log(urlParams);
            verifyEmail = {
                "data":    urlParams.q,
            };

            authenticationService.verifyEmail(verifyEmail, function successBlock(data) {
                verifyEmailToast(data);
                vm.verifiedStatus = "Email Verified!";
                $state.go('home');
            }, function failureBlock(error) {
                verifyEmailToast(error.data);
                vm.verifiedStatus = "Verification error.";
            });
        };
        submitEmailVerification();


    }]);
