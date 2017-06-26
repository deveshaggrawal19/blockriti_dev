"use strict";

angular.module("taurus.loggedInNavModule")
    .directive("loggedInNav", function() {
       return {
          templateUrl:  'modules/logged-in-nav/views/logged-in-nav.html',
          controller:   'LoggedInNavCtrl as loggedInNav'
       }
    });
