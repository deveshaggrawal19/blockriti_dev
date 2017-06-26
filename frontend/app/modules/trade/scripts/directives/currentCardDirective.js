"use strict";
angular.module("taurus.tradeModule")
    .directive("currentCard", function() {
        return {
            templateUrl: 'modules/trade/views/currentCard.html',
            scope: {
                orders:     "=",
                cardTitle:  "@cardTitle"
            }
        }
    }); //directive
