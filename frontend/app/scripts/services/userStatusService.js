"use strict";
/**
 * 
 * Creating factory for User Status in taurus application.   
 * 
 */
angular.module("taurus").factory('userStatusService', function () {

    var userStatusServices = {};

    /**        
     * This function is used for returning wheather
     * 
     */

    var whetherLoggedInStatus = localStorage.isLoggedIn;
    var whetherUserIsOrgUser = angular.fromJson(localStorage.loggedInUser).isOrganizationUser;

    userStatusServices.isUserLoggedIn = function () {
        if (whetherLoggedInStatus)
            return true;
        else
            return false;
    }
    userStatusServices.isOrgUser = function () {
        if (whetherUserIsOrgUser)
            return true;
        else
            return false;
    }
    userStatusServices.userStatus = function () {
        var userStatus = {};
        if (whetherLoggedInStatus) {
            userStatus = {
                userData: {
                    id: angular.fromJson(localStorage.loggedInUser).id,
                    value: angular.fromJson(localStorage.loggedInUser).displayName,
                },
                isLoggedIn: whetherLoggedInStatus,
                isOrgUser: whetherUserIsOrgUser
            };
            return userStatus;
        }
    };
    return userStatusServices;
});