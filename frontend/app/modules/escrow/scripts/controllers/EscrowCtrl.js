'use strict';

angular.module('taurus.escrowModule')
    .controller('EscrowCtrl', ["$scope", '$rootScope', "authenticationService", "escrowService", "$auth", "$cookies", "$state", "urlService", "$mdMedia", "$timeout", "$mdDialog", "toastMessagesService", "$interval", "$firebaseArray", "$firebaseObject", "$mdExpansionPanel", "$http",
      function ($scope, $rootScope, authenticationService, escrowService, $auth, $cookies, $state, urlService, $mdMedia, $timeout, $mdDialog, toastMessagesService, $interval, $firebaseArray, $firebaseObject, $mdExpansionPanel, $http) {

            $rootScope.$mdMedia = $mdMedia;
            $scope.escrowSecret = {
                selected: ""
            };

            var vm = this;
            vm.hideProgressBar = true;
            vm.disableReleaseFundsBtn = true;
            //var token = "eyJhbGciOiJSUzI1NiIsImtpZCI6ImMyNmNiZTk5NjVhZWFiY2YyMGZiM2E4YjFhYWY0MzQ5OTExYzVmMjAifQ.eyJpc3MiOiJodHRwczovL3NlY3VyZXRva2VuLmdvb2dsZS5jb20vYnRjbW9uayIsImF1ZCI6ImJ0Y21vbmsiLCJhdXRoX3RpbWUiOjE0OTEwMjk5MjEsInVzZXJfaWQiOiJ1c2VyOjE3MDUzIiwic3ViIjoidXNlcjoxNzA1MyIsImlhdCI6MTQ5MTAzMzU1MywiZXhwIjoxNDkxMDM3MTUzLCJmaXJlYmFzZSI6eyJpZGVudGl0aWVzIjp7fSwic2lnbl9pbl9wcm92aWRlciI6ImN1c3RvbSJ9fQ.LTlcMk8KJmKxRWGu0zRjNnEpNL6k_6qBqGcemjWmNGjbj6X837Y91jJVG_zHZ44xG6z5YunMRebvw_hSd89rQQleL-uPKu8hheu8LLzgz3Q_IbG_g13UejFdTrEmysEvd-qR7b-Bo9zGFiUZ_MO_lDL6JboW7NwRfcVd_HBqxAKtn2ahGkHdWdK-ZqtkNoHJwgaBsNV4sXbTMNWb2YqMbRGXANZvXGWYaE5ANcMx3KPwl8qcvIfJKCM4Rr_JOV4-gldsusBVR_3uT6LHMjbuO1z-Atzrs8ePXAmm0WqtkXG37_m3IMpiLfsS9jT7zhEx-tAQQIxvKaGg6Ju4YBCAZw";

            $scope.items = ["Crypto Currency", "Physical Good", "Digital Good"];
            $scope.selectedItem;
            $scope.getSelectedText = function () {
                if ($scope.selectedItem !== undefined) {
                    return "You have selected: Item " + $scope.selectedItem;
                } else {
                    return "Select Commodity";
                }
            };

            vm.goToEscrow = function goToEscrow() {
                $state.go('escrow');
            };

            vm.toggleBtnNotation = function (elem) {
                var $el = $("#btn-list-expand-collapse");

                if ($el.hasClass("fa-chevron-down")) {
                    $("#secretkeys-list").fadeOut("slow", function () {
                        $el.removeClass("fa-chevron-down").addClass("fa-chevron-up");
                    });
                } else {
                    $("#secretkeys-list").fadeIn("slow", function () {
                        $el.removeClass("fa-chevron-up").addClass("fa-chevron-down");
                    });
                }
            }

            vm.getEscrowLink = function () {
                firebase.auth().currentUser.getToken(true).then(function (token) {
                    console.log(token);
                    var reqData = {
                        jwt: token,
                        extraData: {
                            commodity: "dash"
                        }
                    };
                    vm.hideProgressBar = false;
                    escrowService.generateEscrowLink(reqData, function (newLink) {
                        console.log(newLink);
                        $scope.activeEscrowLink = newLink;
                        toastMessagesService.successToast('Escrow link generated');
                        vm.hideProgressBar = true;
                    }, function (error) {
                        toastMessagesService.failureToast('Error in generating the escrow link');
                        vm.hideProgressBar = true;
                    });
                });
            };

            vm.getAllEscrows = function () {
                firebase.auth().currentUser.getToken(true).then(function (token) {
                    console.log(token);
                    vm.hideProgressBar = false;
                    escrowService.getEscrowList({
                        jwt: token
                    }, function (response) {
                        $scope.escrowSecretList = response.secrets;
                        vm.hideProgressBar = true;
                    }, function (error) {
                        console.log(error);
                        toastMessagesService.failureToast('Error in fetching secret list');
                        vm.hideProgressBar = true;
                    });
                });
            };

            $scope.$watch('escrowSecret.selected', function () {
                if (!!$scope.escrowSecret && !!$scope.escrowSecret.selected && $scope.escrowSecret.selected.length > 0) {
                    vm.hideProgressBar = false;
                    escrowService.getEscrowSecretInfo($scope.escrowSecret.selected, function (data) {
                        console.log(data);
                        $scope.secretData = data;
                        $(".escrow-info").css("display", "none");
                        $("#" + $scope.escrowSecret.selected).find(".escrow-info").css("display", "inline-block");
                        vm.hideProgressBar = true;
                        vm.disableReleaseFundsBtn = false;
                    }, function (error) {
                        console.log(error);
                        toastMessagesService.failureToast('Error in getting escrow secret details');
                        vm.hideProgressBar = true;
                    });
                }
            })

            vm.releaseFunds = function () {
                if (!!$scope.escrowSecret && !!$scope.escrowSecret.selected && $scope.escrowSecret.selected.length > 0) {
                    vm.hideProgressBar = false;
                    vm.disableReleaseFundsBtn = true;
                    escrowService.releaseFunds($scope.escrowSecret.selected, function () {
                        vm.hideProgressBar = true;
                        vm.disableReleaseFundsBtn = false;
                        toastMessagesService.successToast('Funds released successfully');
                    }, function () {
                        toastMessagesService.failureToast('Error in releasing funds');
                        vm.hideProgressBar = true;
                        vm.disableReleaseFundsBtn = false;
                    });
                } else {
                    toastMessagesService.failureToast('Select escrow secret from list first');
                }
            };

  }]); //controller
