'use strict';

angular.module('taurus.buysellModule')
    .controller('BuysellCtrl', ["$scope", '$rootScope', "authenticationService", "buysellService", "$auth", "$cookies", "$state", "urlService", "$mdMedia", "$timeout", "$mdDialog", "toastMessagesService", "$interval", "$firebaseArray", "$firebaseObject", "$mdExpansionPanel", "$http",
      function ($scope, $rootScope, authenticationService, buysellService, $auth, $cookies, $state, urlService, $mdMedia, $timeout, $mdDialog, toastMessagesService, $interval, $firebaseArray, $firebaseObject, $mdExpansionPanel, $http) {

            $rootScope.$mdMedia = $mdMedia;
            var vm = this;
			
			
            vm.buyBrokerageForm;
            vm.disableOrderButton = false;
            vm.hideProgressBar = true;
            vm.brokerageMinBuyAmount = localStorage.getItem('min_value');
            vm.brokerageMaxBuyAmount = localStorage.getItem('max_value');

            vm.brokerageMinSellAmount = 0.0001;
            vm.brokerageMaxSellAmount = 100;
            vm.instantOrderData = {
                approx: 0,
                fee: 0,
                net: 0,
            };
			
//radio btn			
    $scope.submit = function() {
      alert('submit');
    };
	
    $scope.data = {
      group1 : 'INR',
      group2 : 'INR',
      
    };			

            vm.goToBuysell = function goToBuysell() {
                $state.go('buysell');
            };

            vm.getInstantOrderApproximation = function getInstantOrderApproximation(orderAmount, buySell) {
                var instantOrderApproxParams = {
                    book: "btc_cad",
                    direction: buySell.toLowerCase(),
                    amount: 10000,
                };
                buysellService.getInstantOrderApproximation(instantOrderApproxParams, function successBlock(data) {
                    console.log(data);
                    vm.instantOrderData.approx = data.total;
                    vm.instantOrderData.fee = data.fees;
                    vm.instantOrderData.net = data.net;
                }, function failureBlock(error) {
                    console.log(error.data);
                });
            };


            vm.submitBrokerageOrder = function submitBrokerageOrder(type) {
                var orderData = {
                    user: parseInt(localStorage.getItem('client'))
                };

                if (type === 'SELL') {
                    vm.disableOrderButton = true;
                    vm.hideProgressBar = false;
                    orderData.amount = parseFloat(vm.sellBrokerage.amount);

                    buysellService.sellOrder(orderData, function (response) {
                        vm.disableOrderButton = false;
                        vm.hideProgressBar = true;
                        toastMessagesService.successToast('Sell order executed');
                    }, function (error) {
                        vm.disableOrderButton = false;
                        vm.hideProgressBar = true;
                        toastMessagesService.failureToast('Error on sell order execution');
                    });
                } else if (type === 'BUY') {

                    vm.disableOrderButton = true;
                    vm.hideProgressBar = false;
                    orderData.amount = parseFloat(vm.buyBrokerage.amount);

                    buysellService.buyOrder(orderData, function (response) {
                        console.log(response);
                        vm.disableOrderButton = false;
                        vm.hideProgressBar = true;
                        toastMessagesService.successToast('Buy order executed');
                    }, function (error) {
                        console.log(error);
                        vm.disableOrderButton = false;
                        vm.hideProgressBar = true;
                        toastMessagesService.failureToast('Error on buy order execution');
                    });
                }
            };

            vm.disableScrollOnNumberInput = function disableScrollOnNumberInput() {
                $(':input[type=number]').on('mousewheel', function (e) {
                    $(this).blur();
                });
            };

}]); //controller
