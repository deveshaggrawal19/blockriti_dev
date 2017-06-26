"use strict";
angular.module("taurus.demoModule")
    .directive("instantLimitDemo", function() {
        return {
            templateUrl:    'modules/demo/views/instantLimitDemo.html',
            controller:     'instantLimitDemoCtrl as instantLimitDemo'
        }
    });
