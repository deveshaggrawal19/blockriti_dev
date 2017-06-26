'use strict';
angular.module('taurus.settingsModule', ['taurus.depositWithdrawModule', 'taurus.loggedInNavModule', 'material.components.expansionPanels', 'angular-clipboard']);
angular.module('taurus.settingsModule').config(function($stateProvider, $authProvider) {
    $stateProvider.state('settings', {
        url : '/settings',
        controller : 'SettingsCtrl as settingsCtrl',
        templateUrl: 'modules/settings/views/settings.html',
        resolve: {
            user: ['$auth', '$q', function ($auth, $q) {
                return $auth.isAuthenticated() || $q.reject({
                        unAuthorized: true
                    });
            }]
        }
    });
});
