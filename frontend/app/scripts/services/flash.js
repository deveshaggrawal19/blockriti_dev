"use strict";
/**
 * Creating Flash factory for displaying flash messages in taurus application   
 * 
 */
angular.module("taurus").factory('Flash', ['$rootScope', '$timeout',
    function($rootScope, $timeout) {

        var dataFactory = {},
            timeOut;
        
        /**        
         * This function is used for to create flash messages
         * @param {type} string : type of message
         * @param {text} string : message text
         * @param {addClass} string : class name
         * 
         */
        dataFactory.create = function(type, text, addClass) {
           
            var $this = this;
            $timeout.cancel(timeOut);
            $rootScope.flash.type = type;
            $rootScope.flash.text = text;
            $rootScope.flash.addClass = addClass;
            $timeout(function() {
                $rootScope.hasFlash = true;
            }, 100);
            timeOut = $timeout(function() {
                $this.dismiss();
            }, $rootScope.flash.timeout);
        };

        /**        
         * This function is used for to cancel flash message after timeout
         * 
         */
        dataFactory.pause = function() {
            $timeout.cancel(timeOut);
        };

        /**        
         * This function is used for to dissmiss flash message
         * 
         */
        dataFactory.dismiss = function() {
            $timeout.cancel(timeOut);
            $timeout(function() {
                $rootScope.hasFlash = false;
            });
        };
        return dataFactory;
    }
]);
