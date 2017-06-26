"use strict";
/**
 * Creating httpService factory for handling ajax request in taurus application
 *
 */
angular.module('taurus').factory('httpService', function ($http) {
    var httpService = {};

    /**
     * This function is used for to send GET ajax call request
     * @param {url} string : url path
     * @param {jsonParams} object : parameters object
     * @param {successBlock} function : callBack function on success response
     * @param {errorBlock} function : callBack function on error response
     *
     */
    httpService.getRequest = function (url, successBlock, errorBlock) {
        return $http.get(url).then(function (response) {
            return successBlock(response); //Success block
        }, function (response) {
            return errorBlock(response); //Error block
        });
    };

    httpService.getData = function (url, jsonParams, successBlock, errorBlock) {
        return $http.get(url, {
            params: jsonParams
        }).then(function (response) {
            return successBlock(response); //Success block
        }, function (response) {
            return errorBlock(response); //Error block
        });
    };

    /**
     * This function is used for to send POST ajax call request
     * @param {url} string : url path
     * @param {jsonPostData} object : parameters object
     * @param {successBlock} function : callBack function on success response
     * @param {errorBlock} function : callBack function on error response
     *
     */
    httpService.postData = function (url, jsonPostData, successBlock, errorBlock) {
        return $http.post(url, jsonPostData).then(function (response) {
            return successBlock(response); //Success block
        }, function (response) {
            return errorBlock(response); //Error block
        });
    };

    /**
     * This function is used for to send PUT ajax call request
     * @param {url} string : url path
     * @param {jsonPutData} object : parameters object
     * @param {successBlock} function : callBack function on success response
     * @param {errorBlock} function : callBack function on error response
     *
     */
    httpService.putData = function (url, jsonPutData, successBlock, errorBlock) {
        return $http.put(url, jsonPutData).then(function (response) {
            return successBlock(response); //Success block
        }, function (response) {
            return errorBlock(response); //Error block
        });
    };

    /**
     * This function is used for to send DELETE ajax call request
     * @param {url} string : url path
     * @param {jsonDeleteData} object : parameters object
     * @param {successBlock} function : callBack function on success response
     * @param {errorBlock} function : callBack function on error response
     *
     */
    httpService.deleteData = function (url, jsonDeleteData, successBlock, errorBlock) {
        return $http.delete(url, jsonDeleteData).then(function (response) {
            return successBlock(response); //Success block
        }, function (response) {
            return errorBlock(response); //Error block
        });
    };

    /**
     * This function is used for to send POST ajax call request
     * @param {url} string : url path
     * @param {jsonPostData} object : parameters object
     * @param {header} object : headers for request object if needed
     * @param {successBlock} function : callBack function on success response
     * @param {errorBlock} function : callBack function on error response
     *
     */
    httpService.postDataWithHeader = function (url, jsonPostData, header, successBlock, errorBlock) {
        return $http.post(url, jsonPostData, header).then(function (response) {
            return successBlock(response); //Success block
        }, function (response) {
            return errorBlock(response); //Error block
        });
    };

    /**
     * This function is used for to send GET ajax call request
     * @param {url} string : url path
     * @param {jsonParams} object : parameters object
     * @param {successBlock} function : callBack function on success response
     * @param {errorBlock} function : callBack function on error response
     *
     */
    httpService.getImageData = function (url, jsonParams, successBlock, errorBlock) {
        return $http.get(url, {
            responseType: 'arraybuffer',
            params: jsonParams
        }).then(function (response) {
            return successBlock(response); //Success block
        }, function (response) {
            return errorBlock(response); //Error block
        });
    };


    httpService.getJsonData = function (url, successBlock, errorBlock) {
        $http.get(url).then(function (response) {
            return successBlock(response); //Success block
        }, function (response) {
            return errorBlock(response); //Error block
        });
    };


    /**
     * This function is used for to send POST File data through ajax call
     * @param {url} string : url path
     * @param {jsonPostData} object : formData parameters object
     * @param {successBlock} function : callBack function on success response
     * @param {errorBlock} function : callBack function on error response
     *
     */
    httpService.uploadFileData = function (url, jsonPostData, successBlock, errorBlock) {
        return $http.post(url, jsonPostData, {
            responseType: 'arraybuffer',
            headers: {
                'Content-Type': undefined
            },
            transformRequest: []
        }).then(function (response) {
            return successBlock(response); //Success block
        }, function (response) {
            return errorBlock(response); //Error block
        });
    };

    /**
     * This function is used for to get file input file stream data through ajax call
     * @param {url} string : url path
     * @param {jsonPostData} object : formData parameters object
     * @param {successBlock} function : callBack function on success response
     * @param {errorBlock} function : callBack function on error response
     *
     */
    httpService.downloadFileData = function (url, jsonPostData, successBlock, errorBlock) {
        return $http.post(url, jsonPostData, {
            responseType: 'arraybuffer'
        }).then(function (response) {
            return successBlock(response); //Success block
        }, function (response) {
            return errorBlock(response); //Error block
        });
    };

    return httpService;

});