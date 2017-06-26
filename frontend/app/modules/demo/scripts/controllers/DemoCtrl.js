"use strict";

angular.module("taurus.demoModule").controller("DemoCtrl", ["$scope", '$rootScope', "toastMessagesService", "demoService", "$auth", "$cookies", "$state", "urlService", "$mdMedia", "$timeout", "$mdDialog", "$interval", "$firebaseArray", "$firebaseObject", "$mdSidenav",
    function($scope, $rootScope, toastMessagesService, demoService, $auth, $cookies, $state, urlService, $mdMedia, $timeout, $mdDialog, $interval, $firebaseArray, $firebaseObject, $mdSidenav) {
        $rootScope.$mdMedia = $mdMedia;
        var vm = this;

        $(document).ready(function() {
            $(window).trigger('resize');
        });

 
/*new cancel*/	 





	  
	
	  
/*cancel new end*/		
		
        vm.marketValues;
        vm.displayOpen;
        vm.displayCurrent;
        vm.displayRecent;

        vm.activated = true;
        vm.tradePageFab = true;
        vm.menuItems = [
            {
                name: 'About',
                url: 'about',
                icon: 'mdi mdi-information'
            },
            {
                name: 'FAQ',
                url: 'faq',
                icon: 'mdi mdi-comment-question-outline'
            },
            {
                name: 'Help',
                url: 'help',
                icon: 'mdi mdi-help'
            },
            {
                name: 'Fee Schedule',
                url: 'fee-schedule',
                icon: 'mdi mdi-currency-usd'
            },
            {
                name: 'Terms of Service',
                url: 'terms-of-service',
                icon: 'mdi mdi-book-open'
            },
            {
                name: 'Privacy Policy',
                url: 'privacy-policy',
                icon: 'mdi mdi-lock'
            },
            {
                name: 'API',
                url: 'api',
                icon: 'mdi mdi-code-braces'
            }
        ];
        vm.displayGraph = true;
        vm.changeTypes = [
            {
                name: "Recent",
                icon: "images/noeffect/most-recent.svg"
                // icon: "mdi-lock"
            },
            {
                name: "Current",
                icon: "images/noeffect/current-sell-buy.svg"
                // icon: "mdi-book-open-variant"
            },
            {
                name: "Open",
                icon: "images/noeffect/open-close3.svg",
                // icon: "mdi-lock-open",
            }
        ];
        vm.demoOpenOrders = [];
        vm.demoCloseOrders = [];
        vm.balance = {
            btc_available : 8000,
            cad_available : 250000,
            btc_locked : 0,
            cad_locked : 0
        };

        vm.changeTile = function(tile) {
            var arr = ['Graph', 'Open', 'Current', 'Recent'];
            var whichTile = arr.indexOf(tile);
            switch (whichTile) {
                case 0:
                    vm.displayGraph = true;
                    vm.displayOpen = false;
                    vm.displayCurrent = false;
                    vm.displayRecent = false;
                    break;
                case 1:
                    vm.displayGraph = false;
                    vm.displayOpen = true;
                    vm.displayCurrent = false;
                    vm.displayRecent = false;
                    break;
                case 2:
                    vm.displayGraph = false;
                    vm.displayOpen = false;
                    vm.displayCurrent = true;
                    vm.displayRecent = false;
                    $(document).ready(function() {
                        $(window).trigger('resize');
                    });
                    break;
                case 3:
                    vm.displayGraph = false;
                    vm.displayOpen = false;
                    vm.displayCurrent = false;
                    vm.displayRecent = true;
                    break;
            }
        };
        vm.openSideNavBar = function() {
            $mdSidenav('logged-in-nav-sidebar').open();
        };
        vm.showHomeDialog = function(ev, name) {

            var name = name;
            $mdDialog.show({
                controller: 'HomeCtrl as home',
                templateUrl: 'modules/home/views/' + name + '.html',
                parent: angular.element(document.body),
                targetEvent: ev,
                clickOutsideToClose: true,
                fullscreen: true
            });
        };
        vm.goRegister = function(ev) {
            $mdDialog.show({
                controller: 'AuthenticationCtrl as authentication',
                templateUrl: 'modules/authentication/views/register.html',
                parent: angular.element(document.body),
                targetEvent: ev,
                clickOutsideToClose: true,
                fullscreen: true,
            });
        };
        vm.goHome = function() {
            $state.go('home');
        };
        vm.loader = function($scope, $interval) {
            var j = 0,
                counter = 0;
            vm.modes = [];
            vm.activated = true;
            vm.determinateValue = 30;
            vm.toggleActivation = function() {
                if (!vm.activated) vm.modes = [];
                if (vm.activated) j = counter = 0;
            };

            setInterval(function() {
                vm.determinateValue += 1;
                if (vm.determinateValue > 100) {
                    vm.determinateValue = 30;
                }
                if ((j < 5) && !vm.modes[j] && vm.activated) {
                    vm.modes[j] = 'indeterminate';
                }
                if (counter++ % 4 == 0) j++;
            }, 100, 0, true);
        };
        vm.getMarketValue = function () {
            var getMarketValue = firebase.database().ref().child("getMarketOverview");
            var data = $firebaseObject(getMarketValue);
            console.log(data);
            vm.marketValues = data;

        };

		// hideing cancel row
		
            $scope.IsVisible = true;
            $scope.ShowHide = function () {
                //If DIV is visible it will be hidden and vice versa.
                $scope.IsVisible = $scope.IsVisible ? false : true;
            }
			
			
		// hideing code ends
		
		
        $scope.data = {};
        $scope.currentSell = {};
        $scope.currentTrade = {};
        $scope.currentBuy = {};
        $scope.engineBuy = {};
        $scope.engineSell = {};
        $scope.engineSellMarket = {};
        $scope.defaultZoomLevel = 24;


        $scope.getCurrentSell = function() {
            var getCurrentSell = firebase.database().ref().child("getCurrentSell").child("sell");
            var data = $firebaseArray(getCurrentSell);
            console.log(data);
            $scope.currentSell = data;
        };
        $scope.getRecentTrade = function() {
            var getMostRecentTrades = firebase.database().ref().child("getMostRecentTrades").child("trades");
            var data = $firebaseArray(getMostRecentTrades);
            console.log(data);
            $scope.currentTrade = data;
            $('.material_block').hide();
        };
        $scope.getCurrentBuy = function() {
            var getCurrentBuy = firebase.database().ref().child("getCurrentBuy").child("buy");
            var data = $firebaseArray(getCurrentBuy);
            console.log(data);
            $scope.currentBuy = data;
        };
        $scope.getGraphData = function() {
            var getGraphData = firebase.database().ref().child("1HrGraphData").limitToLast(340);
            var data = $firebaseArray(getGraphData);
            console.log("Graph Data");
            console.log(data);
            $timeout(function() {
                $scope.data = data;
            }, 1000);
        };
        $scope.init = function() {
            vm.getMarketValue();
            $scope.getCurrentSell();
            $scope.getRecentTrade();
            $scope.getCurrentBuy();
            $scope.getGraphData();
			
        };

        $scope.init();
		
		

        $scope.$on("OpenOrderData",function (event, orderDetails) {
            var orderData = {
                type:orderDetails.orderData.type,
                rate : orderDetails.orderData.rate,
                amount : orderDetails.orderData.amount,
                value: orderDetails.orderData.rate * orderDetails.orderData.amount
            };

            if (orderData.type.toLowerCase().trim() == "buy") {
                vm.balance.cad_locked += orderData.value;
                vm.balance.cad_available -= orderData.value;
            }
            else{
                vm.balance.btc_locked += orderData.amount;
                vm.balance.btc_available -= orderData.amount;
            }

            processOrder(orderData, orderDetails.priorityType);
        });

        function processOrder (orderData, priorityType){
            var dataSource = orderData.type.toLowerCase().trim() == "buy" ? $scope.currentSell : $scope.currentBuy;
            priorityType = priorityType.toLowerCase().trim();

            var requiredSellOrder;
            var orderRate = parseFloat(orderData.rate);
            var orderAmt = parseFloat(orderData.amount);

            for(var idx = 0 ; idx < dataSource.length; idx++){
                var sellOrder = dataSource[idx];
                var sellRate = parseFloat(sellOrder.rate);

                if(orderRate == sellRate && orderAmt == parseFloat(sellOrder.amount)) {
                    requiredSellOrder = sellOrder;
                    $scope.currentSell.splice(idx, 1);
                    break;
                }
            }

            if(!!requiredSellOrder){
                $scope.currentTrade.unshift(orderData);
                $scope.currentTrade.unshift(requiredSellOrder);
                vm.demoCloseOrders.unshift(orderData);

                for(var j=0;j< vm.demoOpenOrders.length; j++){
                    var openOrder = vm.demoOpenOrders[j];
                    if(orderRate == parseFloat(openOrder.rate) && orderAmt == parseFloat(openOrder.amount)) {
                        requiredSellOrder = sellOrder;
                        vm.demoOpenOrders.splice(j, 1);
                        break;
                    }
                }
                toastMessagesService.successToast("Order executed successfully.");

                if (orderData.type.toLowerCase().trim() == "buy") {
                    vm.balance.cad_locked -= orderData.value;
                    vm.balance.btc_locked -= orderData.amount;
                } else vm.balance.btc_locked -= orderData.amount;
            }
            else{
                if (priorityType == "instant") {
                    if(orderData.type.toLowerCase().trim() == "buy"){
                        toastMessagesService.failureToast("No seller available yet.");
                    }
                    else{
                        toastMessagesService.failureToast("No buyer available yet.");
                    }

                    if (orderData.type.toLowerCase().trim() == "buy") {
                        vm.balance.cad_locked -= orderData.value;
                        vm.balance.cad_available += orderData.value;
                    }
                    else{
                        vm.balance.btc_locked -= orderData.amount;
                        vm.balance.btc_available += orderData.amount;
                    }
                }
                else if (priorityType == "limit") {
                    vm.demoOpenOrders.unshift(orderData);
                    if(orderData.type.toLowerCase().trim() == "buy"){
                        $scope.currentBuy.unshift(orderData);
                    }
                    else{
                        $scope.currentSell.unshift(orderData);
                    }
                    toastMessagesService.successToast("Order placed successfully.");
                }
            }
        }
    }
]);