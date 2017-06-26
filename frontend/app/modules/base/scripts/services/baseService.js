"use strict";
/**
 * Creating base service for handling basic functions used in all modules   
 * 
 */
angular.module('baseModule').factory('baseService', function () {

    var baseService = {};

    /**
     * This function is used to set user object in cache. 
     * @param {user} object: user is logged in user object 
     * 
     */
    baseService.setLoggedInUser = function (user) {
        localStorage.setItem("loggedInUser", angular.toJson(user));
    };

    /**
     * This function is used to get logged in user object. 
     * 
     */
    baseService.getLoggedInUser = function () {
        if (localStorage.getItem("loggedInUser") !== null) {
            return angular.fromJson(localStorage.getItem("loggedInUser"));
        } else {
            return null;
        }
    };

    /**
     * This function is used to get state params value from local storage
     * 
     * @param stateParams
     * @param stateView
     */
    baseService.getStateParamsFromLocalStarage = function (stateParams, stateView) {

        if (!stateView) {
            if (localStorage.getItem(stateView) !== null) {
                return angular.fromJson(localStorage.getItem(stateView));
            } else {
                return stateParams;
            }
        } else {
            return stateParams;
        }
    };

    /**
     * This function is used to set state params value to local storage
     * 
     * @param stateView
     * @param stateParams
     */
    baseService.setStateParamsToLocalStarage = function (stateView, jsonObj) {

        if (!stateView) {
            delete localStorage.removeItem(stateView);
            localStorage.setItem(stateView, angular.toJson(jsonObj));
        }
        /*return true;*/
    };

    /**
     * This function is used to set language object in cache. 
     * @param {language} object: language is user selected language
     * 
     */
    baseService.setUserLanguage = function (language) {
        localStorage.setItem("userLanguage", angular.toJson(language));
    };

    /**
     * This function is used to get user language object. 
     * 
     */
    baseService.getUserLanguage = function () {
        if (localStorage.getItem("userLanguage") !== null) {
            return angular.fromJson(localStorage.getItem("userLanguage"));
        } else {
            return null;
        }
    };

    return baseService;

});