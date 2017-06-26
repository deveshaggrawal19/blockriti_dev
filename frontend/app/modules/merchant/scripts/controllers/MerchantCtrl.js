'use strict';

angular.module('taurus.merchantModule')
    .controller('MerchantCtrl', ["$scope", '$rootScope', "authenticationService", "merchantService", "$auth", "$cookies", "$state", "urlService", "$mdMedia", "$timeout", "$mdDialog", "toastMessagesService", "$interval", "$firebaseArray", "$firebaseObject", "$mdExpansionPanel", "$http",
      function ($scope, $rootScope, authenticationService, merchantService, $auth, $cookies, $state, urlService, $mdMedia, $timeout, $mdDialog, toastMessagesService, $interval, $firebaseArray, $firebaseObject, $mdExpansionPanel, $http) {

            $rootScope.$mdMedia = $mdMedia;
            var vm = this;
            vm.sellerId = localStorage.getItem('client');
            vm.walletAddress = 'Dummy_Address'; //localStorage.getItem('btc_address');
            vm.currencies = ['BTC'];
            vm.hideProgressBar = true;
            vm.newSeller = false;

            $scope.payout = {
                sellerId: vm.sellerId,
                address: vm.walletAddress,
                currency: 'USD',
                amount: 0
            };

            vm.goToMerchant = function goToMerchant() {
                $state.go('merchant');
            };

            vm.getSellerBalanceInfo = function () {
                vm.hideProgressBar = false;

                merchantService.getSellerBalance(vm.sellerId, function (data) {
                    if (!data.error) {
                        console.log(data);
                        vm.sellerBalanceInfo = data;
                    } else {
                        vm.newSeller = true;
                    }
                    vm.hideProgressBar = true;
                }, function () {
                    toastMessagesService.failureToast('Error in fetching balance');
                    vm.hideProgressBar = true;
                });
            };

            vm.processPayout = function () {
                vm.hideProgressBar = false;

                merchantService.processPayout($scope.payout, function (data) {
                    toastMessagesService.successToast('Payout processed');
                    vm.hideProgressBar = true;
                }, function () {
                    toastMessagesService.failureToast('Error in processing payout');
                    vm.hideProgressBar = true;
                });
            };

            vm.activateSeller = function (event) {
                var $currentBtnEl = $(event.target);
                var sellerInfo = {
                    sellerId: vm.sellerId,
                    amount: '0.001',
                    currency: 'BTC',
                    customer: 'DummyCustomer',
                    message: 'SellerActivation',
                    callbackUrl: 'white'
                };

                if (vm.newSeller) {
                    vm.hideProgressBar = false;
                    merchantService.processRequestPayment(sellerInfo, function (data) {
                        vm.sellerActivationData = data;
                        vm.newSeller = false;
                        vm.hideProgressBar = true;
                        toastMessagesService.successToast('Seller activated.');
                        $currentBtnEl.remove();
                    }, function () {
                        vm.hideProgressBar = true;
                        toastMessagesService.failureToast('Failed to activate seller.');
                    });
                } else {
                    toastMessagesService.successToast('Seller is already activated.');
                    $currentBtnEl.remove();
                }
            };

            function init() {
                vm.getSellerBalanceInfo();
            };

            init();

  }]); //controller
