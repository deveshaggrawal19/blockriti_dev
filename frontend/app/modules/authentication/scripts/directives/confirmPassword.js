"use strict";

angular.module("taurus.autheticationModule").directive("confirmPassword", function () {
    return {
        require: "ngModel",
        scope: {
            actualPassword: "=confirmPassword"
        },
        link: function (scope, element, attrs, ngModelCtrl) {
            function verifyPassword(passwordValue) {
                if (!scope.actualPassword) {
                    scope.actualPassword = ""
                }
                var noMatch = passwordValue != scope.actualPassword
                ngModelCtrl.$setValidity('noMatch', !noMatch);
                return (noMatch) ? noMatch : false;
            }
            ngModelCtrl.$parsers.unshift(verifyPassword);
            scope.$watch("actualPassword", function (value) {
                ngModelCtrl.$setValidity("noMatch", value === ngModelCtrl.$viewValue);
            });
        }
    };
});