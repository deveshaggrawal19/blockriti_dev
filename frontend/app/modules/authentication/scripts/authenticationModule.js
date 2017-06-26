"use strict";
/**
 * Creating authentication module for authenticate user,register user etc.   
 * 
 */
angular.module("taurus.autheticationModule", ["taurus.compareToModule", "material.components.keyboard"]);
angular.module("taurus.autheticationModule").config(function ($stateProvider, $authProvider) {
    
    $stateProvider
        .state('login', {
            url: '/login?token',
            templateUrl: 'modules/authentication/views/login.html',
            resolve: {
                user: ['$auth', '$q',
                function ($auth, $q) {
                        if ($auth.isAuthenticated()) {
                            return $q.reject({
                                authorized: true
                            });
                        }
                }
            ]
            },
            controller: 'AuthenticationCtrl'
        })
        // .state('authenticate', {
        //     url: '/authenticate',
        //     templateUrl: 'modules/authentication/views/code.html',
        //     controller: 'AuthenticationCtrl'
        // })
        // .state('logout', {
        //     url: '/logout',
        //     controller: 'AuthenticationCtrl'
        // })
        .state('forgotId', {
            url: '/forgotid',
            templateUrl: 'modules/authentication/views/forgot-id.html',
            controller: 'AuthenticationCtrl'
        })
        .state('forgotPassword', {
            url: '/forgotpassword',
            templateUrl: 'modules/authentication/views/forgot-password.html',
            controller: 'AuthenticationCtrl'
        })
        .state('resetPassword', {
            url: '/reminder/complete:q',
            templateUrl: 'modules/authentication/views/reset-password.html',
            controller: 'ResetPasswordCtrl as resetPasswordCtrl'
        })
        .state('verifyEmail', {
            url: '/verify_email:email:secret',
            templateUrl: 'modules/authentication/views/verify-email.html',
            controller: 'VerifyEmailCtrl as verifyEmailCtrl'
        })
        // .state('newUserSignIn', {
        //     url: '/newusersignin',
        //     templateUrl: 'modules/authentication/views/new-user-sign-in.html',
        //     controller: 'RegisterCtrl'
        // })
        // .state('individualUserSignUp', {
        //     url: '/individualusersignup',
        //     templateUrl: 'modules/authentication/views/individual-user-sign-up.html',
        //     controller: 'RegisterCtrl'
        // })

    $authProvider.httpInterceptor = function () {
            return true;
        },
        $authProvider.withCredentials = true;
    $authProvider.tokenRoot = null;
    $authProvider.signupUrl = '/auth/signup';
    $authProvider.unlinkUrl = '/auth/unlink/';
    $authProvider.tokenName = 'token';
    $authProvider.tokenPrefix = 'taurus';
    $authProvider.authHeader = 'Authorization';
    $authProvider.authToken = 'Bearer';
    $authProvider.storageType = 'localStorage';

});



