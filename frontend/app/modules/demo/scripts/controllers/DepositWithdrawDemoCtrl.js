"use strict";
angular
    .module("taurus.demoModule")
    .controller("DepositWithdrawDemoCtrl", ["$scope", "$mdDialog", "$base64", function ($scope, $mdDialog, $base64) {

        var vm = this;



        
        vm.tooltipVisible;

        vm.showTabDialog = showTabDialog;

        function showTabDialog(ev) {
            var name = name;
            $mdDialog.show({
                controller: 'DepositWithdrawCtrl',
                templateUrl: 'modules/deposit-withdraw/views/help.html',
                parent: angular.element(document.body),
                targetEvent: ev,
                clickOutsideToClose: true,
                fullscreen: true,
            });
        };

        $scope.cancel = function () {
            $mdDialog.cancel();
        };

    }]); // controller
