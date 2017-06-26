"use strict";

angular.module("taurus.autheticationModule").directive('complexPassword', function() {
  return {
    require: 'ngModel',
    link: function(scope, elm, attrs, ngModel) {

        ngModel.$parsers.unshift(function(password) {
          var hasUpperCase = /[A-Z]/.test(password);
          var hasLowerCase = /[a-z]/.test(password);
          var hasNumbers = /\d/.test(password);
          var hasNonalphas = /\W/.test(password);
          var characterGroupCount = hasUpperCase + hasLowerCase + hasNumbers + hasNonalphas;

          if (characterGroupCount >= 3) {
            ngModel.$setValidity('complexPassword', true);
            return password;
          }
          else {
            ngModel.$setValidity('complexPassword', false);
            return undefined;
          }

        });
      
    }
  }
});