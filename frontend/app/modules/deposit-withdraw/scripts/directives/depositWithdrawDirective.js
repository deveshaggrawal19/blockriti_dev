"use strict";

angular.module("taurus.depositWithdrawModule")
    .directive("depositWithdraw", function() {
       return {
          templateUrl:  'modules/deposit-withdraw/views/deposit-withdraw.html',
          controller:   'DepositWithdrawCtrl as dWCtrl'
       }
    });
