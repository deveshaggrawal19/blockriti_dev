"use strict";

angular.module("taurus.demoModule")
    .directive("depositWithdrawDemo", function() {
       return {
          templateUrl:  'modules/demo/views/deposit-withdraw-demo.html',
          controller:   'DepositWithdrawDemoCtrl as dWDemoCtrl'
       }
    });
