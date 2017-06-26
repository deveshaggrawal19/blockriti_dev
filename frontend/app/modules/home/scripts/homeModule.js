'use strict';

angular.module('taurus.homeModule', ['ngAnimate']);
angular.module('taurus.homeModule').config(function($stateProvider, $authProvider) {
    $stateProvider
    .state('home', {
      url:          '/home',
      //abstract: true,
      controller:   'HomeCtrl as home',
      templateUrl:  'modules/home/views/home.html',
    })
    .state('home.tos', {
      url:          '/tos',
      templateUrl:  'modules/home/views/terms-of-service.html',
      controller:   'HomeCtrl as home'
    })
});
