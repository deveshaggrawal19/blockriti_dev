"use strict";
angular.module('taurus.tradeModule')
    .controller('instantLimitCtrl', ["$scope", "tradeService", "toastMessagesService", "authenticationService", "loggedInNavService", "amountVisibleService", "$firebaseObject", function($scope, tradeService, toastMessagesService, authenticationService, loggedInNavService, amountVisibleService, $firebaseObject) {

      var vm = this;

      vm.submitInstantOrder = submitInstantOrder;
      vm.submitLimitOrder   = submitLimitOrder;

      vm.orderInstant = {
        amount:   "",
        major:    "btc",
        minor:    "cad"
      };
      vm.orderLimit = {
        "amount":   "",
        "major":    "btc",
        "minor":    "cad",
        "rate":     ""
      };

      vm.limitOrderForm;
      vm.showProgressBar = true;
      vm.getInstantStepAmount = getInstantStepAmount;
      vm.instantStepAmount  = "0.01";

      vm.sellBitcoin; //boolean - instead of instantLimit.buySellLabel === "SELL" ternary

      vm.instantAmountMin;
      vm.instantAmountMax;
      vm.limitAmountMin;
      vm.limitAmountMax;
      vm.limitRateMin = localStorage.getItem('min_rate');
      vm.limitRateMax = localStorage.getItem('max_rate');

      $scope.$watch(function() {
        return vm.buySellLabel;
      }, function() {
        if (vm.buySellLabel === 'SELL') {
          vm.sellBitcoin = true;

          vm.instantAmountMin   = localStorage.getItem('min_amount');
          vm.instantAmountMax   = localStorage.getItem('max_amount');
          vm.instantStepAmount  = "0.001";
          // vm.limitAmountMin   = localStorage.getItem('min_amount');
          // vm.limitAmountMax   = localStorage.getItem('max_amount');
        } else {
          vm.sellBitcoin = false;

          vm.instantAmountMin   = localStorage.getItem('min_value');
          vm.instantAmountMax   = localStorage.getItem('max_value');
          vm.instantStepAmount  = "0.01";
          // vm.limitAmountMin   = localStorage.getItem('min_amount');
          // vm.limitAmountMax   = localStorage.getItem('max_amount');
        };
        vm.limitAmountMin = localStorage.getItem('min_amount');
        vm.limitAmountMax = localStorage.getItem('max_amount');
      });

      function getInstantStepAmount() {
        return vm.sellBitcoin ? "0.001" : "0.01";
      };

      vm.disableScrollOnNumberInput = disableScrollOnNumberInput;

      // Prevents user from accidentally scrolling to change number when entering 'amount' or 'rate'
      function disableScrollOnNumberInput() {
        $(':input[type=number]').on('mousewheel',function(e){ $(this).blur(); });
      }; 


      //Toast messages for Instant Order failures (both Buy and Sell)
      var engineToast = function (data) {
          switch (data.code) {
              case 68:
                  toastMessagesService.successToast('Order executed');
                  break;
              case 54:
                  toastMessagesService.failureToast('Invalid order book format');
                  break;
              case 55:
                  toastMessagesService.failureToast('Incorrect amount');
                  break;
              case 56:
                  toastMessagesService.failureToast('Must be logged in to order');
                  break;
              case 57:
                  toastMessagesService.failureToast('Exceeds available balance');
                  break;
              case 58:
                  toastMessagesService.failureToast('Approx BTC Below minimum allowed amount of ' + data.amount);
                  break;
              case 59:
                  toastMessagesService.failureToast('Above maximum allowed amount');
                  break;
              case 60:
                  toastMessagesService.failureToast('Invalid parameter keys');
                  break;
              case 61:
                  toastMessagesService.failureToast('Incorrect rate');
                  break;
              case 62:
                  toastMessagesService.failureToast('Rate is below min allowed amount of ' + data.amount);
                  break;
              case 63:
                  toastMessagesService.failureToast('Rate is above max allowed amount of ' + data.amount);
                  break;
              case 64:
                  toastMessagesService.failureToast('Amount is below min allowed');
                  break;
              case 65:
                  toastMessagesService.failureToast('Amount is above max allowed.');
                  break;
              case 66:
                  toastMessagesService.failureToast('Value is below min allowed');
                  break;
              case 67:
                  toastMessagesService.failureToast('Value is above max allowed');
                  break;
              case 281:
                  toastMessagesService.failureToast('No buyer available, please try after some time');
                  break;
              case 282:
                  toastMessagesService.failureToast('No seller available, please try after some time');
                  break;
              default:
                  toastMessagesService.failureToast('Error on order execute');
          };
      };

      vm.disableOrderButton   = false;
      $scope.$watch(function() {
           return vm.disableOrderButton;
       }, function (value) {
       });

      $scope.$watch(function() {
          return vm.orderLimit.amount;
      }, function (value) {
      });

      $scope.$watch(function() {
           return vm.orderLimit.rate;
      }, function (value) {
      });


      function submitInstantOrder(type) {
        if (type === 'SELL') {
           vm.disableOrderButton = true;
           vm.showProgressBar = false;
         	 tradeService.getEngineSellMarket(vm.orderInstant, function successBlock(data){
                console.log(data);
                engineToast(data);
                $scope.selectedTab = 0;
                vm.orderInstant.amount = "";
                vm.disableOrderButton = false;
                vm.showProgressBar = true;
                // data.code === 'Success' ? toastMessagesService.successToast('Order executed') : "";
            }, function failureBlock(error){
                console.log(error.data);
                engineToast(error.data);
                vm.disableOrderButton = false;
                vm.showProgressBar = true;
            });
        } else if (type === 'BUY') {
            vm.disableOrderButton = true;
            vm.showProgressBar = false;
            tradeService.getEngineBuyMarket(vm.orderInstant, function successBlock(data){
                console.log(data);
                engineToast(data);
                $scope.selectedTab = 0;
                vm.orderInstant.amount = "";
                vm.disableOrderButton = false;
                vm.showProgressBar = true;
                // data.code === 'Success' ? toastMessagesService.successToast('Order executed') : "";
            }, function failureBlock(error){
                console.log(error.data);
                engineToast(error.data);
                vm.disableOrderButton = false;
                vm.showProgressBar = true;
            });
        }
      };

      function submitLimitOrder(type) {
        if (type === 'SELL') {
           vm.disableOrderButton = true;
           vm.showProgressBar = false;
         	 tradeService.getEngineSell(vm.orderLimit, function successBlock(data){
                console.log(data);
                engineToast(data);
                vm.orderLimit.amount  = "";
                vm.orderLimit.rate    = "";
                vm.disableOrderButton = false;
                vm.showProgressBar = true;
                vm.limitOrderForm.$setUntouched();
                vm.limitOrderForm.$setPristine();
            }, function failureBlock(error){
                console.log(error.data);
                engineToast(error.data);
                vm.disableOrderButton = false;
                vm.showProgressBar = true;
            });
        } else if (type === 'BUY') {
           vm.disableOrderButton = true;
           vm.showProgressBar = false;
         	 tradeService.getEngineBuy(vm.orderLimit, function successBlock(data){
                console.log(data);
                engineToast(data);
                vm.orderLimit.amount  = "";
                vm.orderLimit.rate    = "";
                vm.disableOrderButton = false;
                vm.showProgressBar = true;
                vm.limitOrderForm.$setUntouched();
                vm.limitOrderForm.$setPristine();
            }, function failureBlock(error){
                console.log(error.data);
                engineToast(error.data);
                vm.disableOrderButton = false;
                vm.showProgressBar = true;
            });
        }
      };


      $scope.selectedTab      = 0;
      vm.instantLimitLabel    = "Buy or Sell?";
      vm.buySellLabel         = "Buy or Sell?";
      vm.instantLimitContent  = true;
      vm.showBuySell          = false;
      vm.BuySellContent       = false;
      vm.showAmount           = amountVisibleService.showAmount;
      // vm.showAmount           = false;


      // vm.buySellContent       = false;

      vm.changeSelectedTab    = changeSelectedTab;
      vm.createBuySell        = createBuySell;
      // vm.instantLimitSelected = instantLimitSelected;
      vm.createAmount         = createAmount;

      function changeSelectedTab(tabIndex) {
          vm.selectedTab = tabIndex;
      };

      function createBuySell() {
          vm.showBuySell          = true;
          $scope.selectedTab      = 1;
          vm.instantLimitContent  = false;
          vm.buySellContent       = true;
      };

      function createAmount() {
          vm.showAmount       = true;
          $scope.selectedTab  = 2;
          amountVisibleService.setShowAmount(true);
          vm.buySellContent   = false;
      };

      $scope.$watch('selectedTab', function (tab) {
          switch (tab) {
            case 0:
                vm.instantLimitContent  = true;
                vm.instantLimitLabel    = "Buy or Sell?"
                vm.showBuySell          = false;
                vm.buySellContent       = false;
                vm.buySellLabel         = "Buy or Sell?";
                vm.showAmount           = false;
                amountVisibleService.setShowAmount(false);
                break;
            case 1:
                vm.instantLimitContent  = false;
                vm.showBuySell          = true;
                vm.buySellLabel         = "Buy or Sell?";
                vm.buySellContent       = true;
                vm.showAmount           = false;
                amountVisibleService.setShowAmount(false);
                break;
            case 2:
                vm.showAmount           = true;
                amountVisibleService.setShowAmount(true);
                break;
          }
      });


      $scope.addTab = function (title, view) {
        view = view || title + " Content View";
        tabs.push({ title: title, content: view, disabled: false});
      };
      $scope.removeTab = function (tab) {
        var index = tabs.indexOf(tab);
        tabs.splice(index, 1);
      };

      vm.getInstantOrderApproximation = getInstantOrderApproximation;
      vm.instantOrderData = {
        approx: "",
        fee:    "",
        net:    "",
      };

      function getInstantOrderApproximation(orderAmount, buySell) {
        var instantOrderApproxParams = {
          book:       "btc_cad",
          direction:  buySell.toLowerCase(),
          amount:     orderAmount, 
        };
        console.log(instantOrderApproxParams);
        tradeService.getInstantOrderApproximation(instantOrderApproxParams, function successBlock(data) {
            console.log(data);
            vm.instantOrderData.approx  = data.total;
            vm.instantOrderData.fee     = data.fees;
            vm.instantOrderData.net     = data.net;
          }, function failureBlock(error) {
              console.log(error.data);
          });
      };




  }]);
