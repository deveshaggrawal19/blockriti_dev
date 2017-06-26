"use strict";
angular.module('taurus.demoModule')
    .controller('instantLimitDemoCtrl', ["$rootScope","$scope", "demoService", "toastMessagesService", function($rootScope, $scope, demoService, toastMessagesService) {

        var vm = this;

        vm.orderInstant = {
            amount: "",
            major: "btc",
            minor: "cad"
        };
        vm.orderLimit = {
            amount: 0,
            major: "btc",
            minor: "cad",
            rate: 0,
            value : 0
        };
        vm.limitOrderForm;
        vm.showProgressBar = true;
        vm.disableOrderButton = false;
        vm.instantLimitLabel = "Instant or Limit Order?";
        vm.buySellLabel = "Buy or Sell?";
        vm.instantLimitContent = true;
        vm.showBuySell = false;
        vm.BuySellContent = false;
        vm.instantOrderData = {
            type : "",
            amount: "",
            rate : "",
            approx: "",
            fee: "",
            net: ""
        };
        vm.limitOrderData = {
            type : "",
            amount: "",
            rate : "",
            approx: "",
            fee: "",
            net: ""
        };

        $scope.selectedTab = 0;

        $scope.$watch(function() {
            return vm.buySellLabel;
        }, function() {
            if (vm.buySellLabel === 'SELL') {
                vm.sellBitcoin = true;

                vm.instantAmountMin = "0.005";
                vm.instantAmountMax = "1000";
                vm.instantStepAmount = "0.001";
            } else {
                vm.sellBitcoin = false;

                vm.instantAmountMin = '1';
                vm.instantAmountMax = '500000';
                vm.instantStepAmount = "0.01";
            }
        });
        $scope.$watch('selectedTab', function(tab) {
            switch (tab) {
                case 0:
                    vm.instantLimitContent = true;
                    vm.instantLimitLabel = "Instant or Limit Order?"
                    vm.showBuySell = false;
                    vm.buySellContent = false;
                    vm.buySellLabel = "Buy or Sell?";
                    vm.showAmount = false;
                    break;
                case 1:
                    vm.instantLimitContent = false;
                    vm.showBuySell = true;
                    vm.buySellLabel = "Buy or Sell?";
                    vm.buySellContent = true;
                    vm.showAmount = false;
                    break;
                case 2:
                    vm.showAmount = true;
                    break;
            }
        });
        $scope.addTab = function(title, view) {
            view = view || title + " Content View";
            tabs.push({
                title: title,
                content: view,
                disabled: false
            });
        };
        $scope.removeTab = function(tab) {
            var index = tabs.indexOf(tab);
            tabs.splice(index, 1);
        };

        vm.changeSelectedTab = function (tabIndex) {
            vm.selectedTab = tabIndex;
        };
        vm.createBuySell = function() {
            vm.showBuySell = true;
            $scope.selectedTab = 1;
            vm.instantLimitContent = false;
            vm.buySellContent = true;
        };
        vm.createAmount = function () {
            vm.showAmount = true;
            $scope.selectedTab = 2;
            vm.buySellContent = false;
        };
        vm.disableScrollOnNumberInput = function() {
            $(':input[type=number]').on('mousewheel', function(e) {
                $(this).blur();
            });
        };
        vm.getInstantOrderApproximation = function(orderAmount, buySell) {
            var instantOrderApproxParams = {
                book: "btc_cad",
                direction: buySell.toLowerCase(),
                amount: orderAmount
            };

            orderAmount = !orderAmount ? 0 : parseFloat(orderAmount);
            vm.instantOrderData.type = buySell.toLowerCase();
            vm.instantOrderData.rate = 70000.00;

            if(buySell.toLowerCase().trim() === "sell") {
                vm.instantOrderData.amount = orderAmount;
                vm.instantOrderData.approx = orderAmount * vm.instantOrderData.rate;
                vm.instantOrderData.fee = 0;//vm.instantOrderData.approx * 0.10;
                vm.instantOrderData.net = vm.instantOrderData.approx - vm.instantOrderData.fee;
            }
            else if(buySell.toLowerCase().trim() === "buy") {
                vm.instantOrderData.amount = orderAmount/70000;
                vm.instantOrderData.approx = orderAmount / vm.instantOrderData.rate;
                vm.instantOrderData.fee = 0;//(orderAmount * 0.10);
                vm.instantOrderData.net = (orderAmount - vm.instantOrderData.fee) / vm.instantOrderData.rate;

            }
            /*demoService.getInstantOrderApproximation(instantOrderApproxParams, function successBlock(data) {
                console.log(data);
                vm.instantOrderData.approx  = data.total;
                vm.instantOrderData.fee     = data.fees;
                vm.instantOrderData.net     = data.net;
              }, function failureBlock(error) {
                console.log(error.data);
              });*/
        };
        vm.addInstantOrder = function(){
            $rootScope.$broadcast('OpenOrderData', {
                orderData :vm.instantOrderData,
                priorityType : vm.instantLimitLabel
            });
        };
        vm.addLimitOrder =function (buySell) {
            if(vm.limitOrderForm.$valid) {
                vm.limitOrderData.amount = vm.orderLimit.amount;
                vm.limitOrderData.rate = vm.orderLimit.rate;
                vm.limitOrderData.type = buySell.toLowerCase();

                $rootScope.$broadcast('OpenOrderData', {
                    orderData: vm.limitOrderData,
                    priorityType: vm.instantLimitLabel
                });
            }
        }
    }]);