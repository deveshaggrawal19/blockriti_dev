"use strict";

/**
 * Creating "taurus" module and injecting third party module or developed module
 *
 */
angular.module("taurus", [
      //Third party module Injection
       	"ui.router",
       	"ngMask",
        "ngAnimate",
        "satellizer",
        "ngSanitize",
        // "ui.bootstrap",
       // "datatables",
        "ncy-angular-breadcrumb",
        "ngMaterial",
        "ngCookies",
        "ngFileUpload",
        "rzModule",
        "angularjs-crypto",
        "md.data.table",
        "material.components.expansionPanels",
        "base64",
        "ngMessages",
        "btford.socket-io",
        "firebase",
      //Developed Module Injection

        "baseModule",
        "taurus.autheticationModule",
       /* "taurus.dashBoardModule",*/
        "taurus.homeModule",
        "taurus.tradeModule",
        "taurus.demoModule",
        "taurus.walletModule",
        "taurus.settingsModule",
		
		"taurus.dashboardModule",
		"taurus.escrowModule",
		"taurus.merchantModule",
		"taurus.buysellModule",
		
        "taurus.verificationModule",
        // "taurus.shareEmailModule",

]);

/**
 * Set log in page while running taurus module
 *
 */
angular.module('taurus').run(['$state', function ($state) {
    $state.go('home');
}]);

//Adds filter to capitalize first letter of a given string. For use on /wallet and /trade pages
angular.module('taurus').filter('capitalize', function() {
    return function(input) {
      return (!!input) ? input.charAt(0).toUpperCase() + input.substr(1).toLowerCase() : '';
    }
});

angular.module('taurus').filter('nineDigitsBitcoin', ["$filter",function($filter) {
    return function(input) {
      if (input) {
        var inputString = input.toString();
        if (input == 0) {
            return "Ƀ" + "0";
        }
        return $filter('currency')(inputString,"Ƀ",8);
        //return "Ƀ" + Number(inputString).toPrecision(9);
      };
    }
}]);

/**
 * Apply default configuration to taurus module
 *
 */

angular.module("taurus").config(
    function ($urlRouterProvider, $stateProvider, $httpProvider,
        $authProvider, $breadcrumbProvider, $mdThemingProvider, $locationProvider) {

          $mdThemingProvider.theme('default')
          .primaryPalette('blue-grey', {
            'default': '500'
          })
          .accentPalette('green', {
            'default': '500'
          });

          //alt theme used in FAQ (off /home) - wanted to use red md-warn but already was in used as regular orange color elsewhere
          $mdThemingProvider.theme('altTheme')
          .accentPalette('green', {
            'default': '500'
          })
          .warnPalette('red');

          //for toasts
          $mdThemingProvider.theme('success-toast');
          $mdThemingProvider.theme('failure-toast');
          $mdThemingProvider.theme('warn-toast');

          // $locationProvider.html5Mode(true);




    	/*$httpProvider.defaults.headers.common = {};
  	  $httpProvider.defaults.headers.post = {};
  	  $httpProvider.defaults.headers.put = {};
  	  $httpProvider.defaults.headers.patch = {};*/

       /* $stateProvider.state('home1', {
            url: '/home1',
            abstract: true,
            controller: 'HomeCtrl',
            resolve: {
                user: ['$auth', '$q', function ($auth, $q) {
                    return $auth.isAuthenticated() || $q.reject({
                        unAuthorized: true
                    });
                }]
            },
            templateUrl: 'app/views/home.html'
            //templateUrl: 'app/modules/home/views/home.html'
        }).state('home.view', {
            url: '/view',
            templateUrl: 'app/views/homeView.html',
            ncyBreadcrumb: {
                label: "Home"
            }
        });*/

       /* $breadcrumbProvider.setOptions({
            prefixStateName: 'home.view',
            template: 'bootstrap3'
        });*/

    });

/**
 * Catch state change error event and apply language flag while running taurus module
 *
 */
angular.module('taurus').run(
        [
                '$rootScope',"$state",

                function ($rootScope,$state) {
           $rootScope.$on('$stateChangeError', function (event,
                toState, toParams, fromState, fromParams, error) {
                if (error.unAuthorized) {
                    $state.go('home');
                } else if (error.authorized) {
                    $state.go('home.view');
                }
            });

                }]);


angular.module('taurus').run(['cfCryptoHttpInterceptor', function(cfCryptoHttpInterceptor) {
    cfCryptoHttpInterceptor.base64Key = "16rdKQfqN3L4TY7YktgxBw==";
}]);
