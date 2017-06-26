"use strict";
/**
 * Creating Log In controller  for validate user and log in to system
 *
 */
angular.module("taurus.tradeModule").controller("TradeCtrl", ["$scope", '$rootScope', "authenticationService", "tradeService", "$auth", "$cookies", "$state", "urlService", "$mdMedia", "$timeout", "$mdDialog", "toastMessagesService", "$interval", "$firebaseArray", "$firebaseObject",
    function ($scope, $rootScope, authenticationService, tradeService, $auth, $cookies, $state, urlService, $mdMedia, $timeout, $mdDialog, toastMessagesService, $interval, $firebaseArray, $firebaseObject) {

      $rootScope.$mdMedia = $mdMedia;
      var vm              = this;
      vm.activated        = true;
      vm.loader           = loader;
      vm.tradePageFab     = true;

      // Required to correctly size inside open orders (sometimes doesn't correctly size height)
      // This causes the height calculation to be redone.
      $(document).ready(function() {
         $(window).trigger('resize');
      });


	    function loader ($scope, $interval) {
            var  j= 0, counter = 0;
            vm.modes = [ ];
            vm.activated = true;
            vm.determinateValue = 30;
            vm.toggleActivation = function() {
               if ( !vm.activated ) vm.modes = [ ];
               if (  vm.activated ) j = counter = 0;
            };

            setInterval(function() {
               vm.determinateValue += 1;
               if (vm.determinateValue > 100) {
                  vm.determinateValue = 30;
               }
               if ( (j < 5) && !vm.modes[j] && vm.activated ) {
                  vm.modes[j] = 'indeterminate';
               }
               if ( counter++ % 4 == 0 ) j++;
            }, 100, 0, true);
         }

     $scope.currentSell = {};
     $scope.currentTrade = {};
     $scope.currentBuy = {};
     $scope.engineBuy = {};
     $scope.engineSell = {};
     $scope.engineSellMarket = {};
     $scope.data = {};
     $scope.closedOrders = {};
     vm.graphSource = 'BTC Monk';


     vm.marketValues;
     vm.getMarketValue = getMarketValue;
		 var param={"test":"true"};
		 /*
     function getMarketValue() {
         tradeService.getMarketValue(param,function successBlock(data){

            vm.marketValues = data;

            // console.log(data.lastPrice);
         },function failureBlock(){
         });
     };*/

        function getMarketValue() {
          var getMarketValue = firebase.database().ref().child("getMarketOverview");
          var data = $firebaseObject(getMarketValue);
          console.log(data);
          vm.marketValues = data;
        };

     getMarketValue();

/*     $scope.getCurrentSell=function () {
         tradeService.getCurrentSell(function successBlock(data){

         $scope.currentSell = data.sell;

         },function failureBlock(){
         });
         };*/

        $scope.getCurrentSell=function () {
            var getCurrentSell = firebase.database().ref().child("getCurrentSell").child("sell");
            var data = $firebaseArray(getCurrentSell);
             console.log(data);
            $scope.currentSell = data;
        };


/*     $scope.getRecentTrade=function () {
    	 tradeService.getRecentTrade(function successBlock(data){

    		 $scope.currentTrade = data.trades;
			  $('.material_block').hide();
    	 },function failureBlock(){
         });
     };*/

        $scope.getRecentTrade=function () {

            var getMostRecentTrades = firebase.database().ref().child("getMostRecentTrades").child("trades");
            var data = $firebaseArray(getMostRecentTrades);
            console.log(data);
            $scope.currentTrade = data;
            $('.material_block').hide();
        };


/*     $scope.getCurrentBuy=function () {
         tradeService.getCurrentBuy(function successBlock(data){
         $scope.currentBuy = data.buy;
         },function failureBlock(){
         });
         };*/


        $scope.getCurrentBuy=function () {
            var getCurrentBuy = firebase.database().ref().child("getCurrentBuy").child("buy");
             var data = $firebaseArray(getCurrentBuy);
             console.log(data);
             $scope.currentBuy = data;
            };

/*     $scope.getGraphData=function () {
      	 tradeService.getGraphData(function successBlock(data){
        		 var chartData = JSON.parse(data.chartdata);
    			   $timeout(function () {
        		     $scope.data = chartData;
    			   },1000);
           },function failureBlock(){
           });
     };*/

        $scope.defaultZoom;
        $scope.getGraphData = function (graphTimeInterval, graphLimitToLast, graphDefaultZoom) {
          //var getGraphData = firebase.database().ref().child("getGraphData").child("chartdata");
          var getGraphData = firebase.database().ref().child(graphTimeInterval).limitToLast(graphLimitToLast);
          var data = $firebaseArray(getGraphData);
          $scope.defaultZoom = graphDefaultZoom; //default zoom - Eg. 24 for 1h data shows last 24 hours by default (user can zoom out or pan to view earlier data)
          console.log(data);
          console.log(data.length);
          // var chartData = JSON.parse(data);
          $timeout(function () {
            $scope.data = data;
          }, 1000);
        };



    /*    $scope.getOrders=function () {
      	 tradeService.getOrders(function successBlock(data){
    			  $timeout(function () {
              $scope.orders = data.orders;
              console.log($scope.orders);
            },1000);
         },function failureBlock(error){
         });
     };
    */


        /*$scope.getOrders=function () {
            tradeService.getOrders(function successBlock(data){
            $timeout(function () {
            $scope.orders = data.orders;
            console.log($scope.orders);
            },1000);
            },function failureBlock(error){
                toastMessagesService.failureToast('Error Orders');
            });
        };*/

        $scope.getOrders=function () {
            var accessToken = localStorage.getItem("access_token");
            authenticationService.authenticateFirebaseDb(accessToken).then(function(result){
                var userId = "user:"+localStorage.getItem('client');
                console.log("User logged in");
                var getOpenOrdersRef = firebase.database().ref().child("profileData/"+userId).child("getOrders");
                var data = $firebaseArray(getOpenOrdersRef);
                $scope.orders = data;
            }).catch(function(error){
                console.error(error);
            });
        };


        /*$scope.getClosedOrders=function () {
          tradeService.getClosedOrders(function successBlock(data){
          // tradeService.getOrders(function successBlock(data){
    				  $timeout(function () {
                    $scope.closedOrders = data.entries;
                    // $scope.closedOrders = data.closedOrders;
    				  },1000);
          },function failureBlock(error){
              console.log(error);
          });
        };*/

        $scope.getClosedOrders=function () {
            var accessToken = localStorage.getItem("access_token");
            authenticationService.authenticateFirebaseDb(accessToken).then(function(result){
                var userId = "user:"+localStorage.getItem('client');
                var getClosedOrders = firebase.database().ref().child("profileData/"+userId).child("getClosedOrders");
                var data = $firebaseArray(getClosedOrders);
                $scope.closedOrders = data;
            }).catch(function(error){
                console.error(error);
            });
        };

        function getBalance() {
          var accessToken = localStorage.getItem("access_token");
          tradeService.authenticateFirebaseDb(accessToken).then(function(result){
              var userId = "user:"+localStorage.getItem('client');
              var balancesRef = firebase.database().ref().child("profileData/"+userId+"/balances");
              var data = $firebaseObject(balancesRef);
              console.log(data);
              vm.balance = data;
          }).catch(function(error){
              console.error(error);
          });
      };



     $scope.init=function() {
     vm.loader();
       $scope.getCurrentSell();
       $scope.getRecentTrade();
       $scope.getCurrentBuy();
       $scope.getGraphData("1HrGraphData", 360, 24); //limit to last 360 values, default zoom to last 24
       getBalance();
    	//  $scope.getEngineBuy();
    	//  $scope.getEngineBuyMarket();
    	//  $scope.getEngineSell();
    	//  $scope.getEngineSellMarket();
    	 $scope.getOrders();
       $scope.getClosedOrders();
		   /* $timeout(function () {
			 $('.material_block').hide();
		   },4000); */
     };

     $scope.init();

    //  vm.asks;
    //  vm.buys;
    //  vm.isLarge;
    //  vm.isMedium;
    //  vm.isSmall;
    //  vm.orders;
    //  vm.transactions;



      vm.cancelOrder            = cancelOrder;
      vm.confirmCancelAllOrders = confirmCancelAllOrders;
        var engineToast = function(code) {
            switch(code) {
                case 241:
                    toastMessagesService.successToast('Error 241 please contact support');
                    break;
                case 242:
                    toastMessagesService.failureToast('Order Not Found');
                    break;
                case 250:
                    toastMessagesService.failureToast('Order has been canceled');
                    break;

            };
        };

      function cancelOrder(orderId, ordersIndex) {
          //$scope.orders.splice(ordersIndex, 1); //not required as $firebaseArray will be taking care of this for getOrders
          var cancelOrderID = {
              "id":orderId.uid
          };

          tradeService.cancelOrder(cancelOrderID, function successBlock(data){
              console.log(data);
              engineToast(data.code);
          },function failureBlock(error){
              console.log(error.data.code);
              engineToast(error.data.code);
          });

      };



        function confirmCancelAllOrders(ev) {
            // get updated order list
            $scope.getOrders();

            var confirm = $mdDialog.confirm()
                                   .title('Cancel All Orders')
                                   .textContent('Are you sure you want to cancel all open orders?')
                                   .ariaLabel('Cancel all orders')
                                   .targetEvent(ev)
                                   .ok('Yes')
                                   .cancel('No');

           $mdDialog.show(confirm).then(function() {
              //yes
              var ordersCount = $scope.orders.length;
              if(ordersCount > 0) {
                  var cancelAllOrders = {
                      id: [],
                  };
                  //loops through $scope.orders, storing each uid in cancelAllOrders.id
                  for(var i=0; i < ordersCount; i++) {
                      cancelAllOrders.id.push($scope.orders[i].uid);
                  };
                  tradeService.cancelOrder(cancelAllOrders, function successBlock(data){
                      console.log(data);
                      engineToast(data.code);
                      $scope.orders = "";
                  },function failureBlock(error){
                      console.log(error.data.code);
                      engineToast(error.data.code);
                  });
              };
         }, function() {
           //no
         });

      };

      vm.displayGraph    = true;
      vm.displayOpen;
      vm.displayCurrent;
      vm.displayRecent;
      vm.changeTile      = changeTile;
      vm.changeTypes     = [
         // {
             // name: "Graph",
             // icon: "images/svg/",
             // icon: "mdi-chart-line",
         // },
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

     // sm and xs - through FAB, change first card
     function changeTile(tile) {
         var arr = ['Graph', 'Open', 'Current', 'Recent'];
         var whichTile = arr.indexOf(tile);
         switch(whichTile) {
             case 0:
                 vm.displayGraph    = true;
                 vm.displayOpen     = false;
                 vm.displayCurrent  = false;
                 vm.displayRecent   = false;
                 break;
             case 1:
                 vm.displayGraph    = false;
                 vm.displayOpen     = true;
                 vm.displayCurrent  = false;
                 vm.displayRecent   = false;
                 break;
             case 2:
                 vm.displayGraph    = false;
                 vm.displayOpen     = false;
                 vm.displayCurrent  = true;
                 vm.displayRecent   = false;
                 $(document).ready(function() {
                    $(window).trigger('resize');
                 });
                 break;
             case 3:
                 vm.displayGraph    = false;
                 vm.displayOpen     = false;
                 vm.displayCurrent  = false;
                 vm.displayRecent   = true;
                 break;
         }
     };



    }
    ]);
