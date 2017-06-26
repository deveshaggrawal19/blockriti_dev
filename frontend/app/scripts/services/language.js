/*"use strict";
*//**
 * Creating Language factory for setting isRTL global flag in taurus application   
 * 
 *//*
angular.module("taurus").factory('Language', function ($translate) {

    var rtlLanguages = ['ar'];  //add the languages for RTL support. ar stands for arabic
    
    *//**
    *
    * This function is used for to check current language with rtl supported language     
    *  and set isRTL flag 
    *//*
    var isRtl = function() {
        var languageKey = $translate.proposedLanguage() || $translate.use();
        if(languageKey){
	        for (var i=0; i<rtlLanguages.length; i+=1) {
	            if (languageKey===rtlLanguages[i])
	                return true;
	        }
        }else{
        	$translate.use('en');
        }
        return false; 
    };
   
    return {
        isRtl: isRtl
    };
});*/