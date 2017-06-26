"use strict";
angular.module("taurus.tradeModule")
    .directive("instantLimit", function() {
        return {
            templateUrl:    'modules/trade/views/instantLimit.html',
            controller:     'instantLimitCtrl as instantLimit'
        }
    });
