angular.module('taurus.autheticationModule').controller('ResetPasswordCtrl', ["$scope", "$location", "authenticationService", "$base64", "toastMessagesService", "$state",  function ($scope, $location, authenticationService, $base64, toastMessagesService, $state) {
     
        var vm = this;
        vm.submitResetPassword = submitResetPassword;
        vm.resetPassword = {
            "code":             "",
            "password":         "",
            "confirmPassword":  "",
        };
        vm.passwordResetForm;
        vm.showProgressBar = true;


        var resetPasswordToast = function (data) {
            switch (data.code) {
             case 260:
                 toastMessagesService.successToast('Password reset successfully');
                 break;
             case 251:
                 toastMessagesService.failureToast('Code incorrect');
                 break;
             case 252:
                 toastMessagesService.failureToast('Password and confirm password do not match');
                 break;
             default:
                 toastMessagesService.failureToast('Password reset error');
            };
        };



        authenticationService.fetchKey(function (successResponse) {
            localStorage.setItem("pkey", successResponse.key);

        }, function(errorResponse) {
            vm.failureToast('Error ' + errorResponse);
        });
    
        vm.resetPassword.code = $location.search().q;

        function submitResetPassword(resetPasswordInput) {
            vm.showProgressBar = false;
            var crypt = new JSEncrypt();
            crypt.setKey($base64.decode(localStorage.getItem('pkey')));

            var resetPasswordParams = {
                "code": resetPasswordInput.code,
                // "data": crypt.encrypt({"password": resetPasswordInput.password, "confirm_password": resetPasswordInput.passwordConfirm})
                "data": crypt.encrypt(JSON.stringify({"password": resetPasswordInput.password, "confirm_password": resetPasswordInput.confirmPassword}))
            };

            authenticationService.forgotConfirm(resetPasswordParams, function successBlock(data) {
                vm.showProgressBar = true;
                resetPasswordToast(data);
                $state.go('home');
            }, function failureBlock(error) {
                vm.showProgressBar = true;
                vm.resetPassword.password = "";
                vm.resetPassword.confirmPassword = "";
                vm.passwordResetForm.$setUntouched();
                vm.passwordResetForm.$setPristine();
                resetPasswordToast(error.data);
            });
        };

    }]);

    // .config(function($routeProvider, $locationProvider) {
    //     $routeProvider
    //         .when('/reminder/complete/:token', {
    //             templateUrl: 'modules/authentication/views/reset-password.html',
    //             controller: 'ResetPasswordController',

    //         })
    // });