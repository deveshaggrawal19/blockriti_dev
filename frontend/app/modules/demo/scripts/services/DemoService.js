angular.module("taurus.demoModule").service('demoService', ['httpService',
		 'urlService',  'baseConfig', '$auth', '$state', '$rootScope', '$cookieStore',
    function (httpService, urlService, baseConfig, $auth, $state, $rootScope, $cookieStore) {
			"use strict";

			this.getCurrentSell= function (successCallback,errorCallback){
          httpService.getData(urlService.getUrl('CURRENT_SELL'), null, function (response) {
              if (response && response.data)
                  successCallback(response.data);
          }, function (error) {
              errorCallback(error);
          });
     	};


     this.getRecentTrade= function (successCallback,errorCallback){
         httpService.getData(urlService.getUrl('RECENT_TRADE'), null, function (response) {
             if (response && response.data)
                 successCallback(response.data);
         }, function (error) {
             errorCallback(error);
         });
    	};


      this.getCurrentBuy= function (successCallback,errorCallback){
          httpService.getData(urlService.getUrl('CURRENT_BUY'), null, function (response) {
              if (response && response.data)
                  successCallback(response.data);
          }, function (error) {
              errorCallback(error);
          });
     	};

	 this.getGraphData= function (req,successCallback,errorCallback){
	     httpService.getData(urlService.getUrl('GRAPH_DATA'), null, function (response) {
	         if (response && response.data)
	             successCallback(response);
	     }, function (error) {
	         errorCallback(error);
	     });
		};

    this.getInstantOrderApproximation = function (instantOrderApproxParams, successCallback,errorCallback){
        httpService.postData(urlService.getUrl('GET_RATE'), instantOrderApproxParams, function (response) {
          if (response && response.data)
            successCallback(response.data);
        }, function (error) {
          errorCallback(error);
        });
    };


        // this.getTrades = function (successCallback,errorCallback){
        //     httpService.getData('http://cors.io/?u=https://api.taurusexchange.com/order_book', null, function (response) {
        //         if (response)
        //             successCallback(response);
        //     }, function (error) {
        //         errorCallback(error);
        //     });
        // };
				//
        // this.getTransactions = function (successCallback,errorCallback){
        //      httpService.getData('http://cors.io/?u=https://api.taurusexchange.com/transactions', null, function (response) {
        //          if (response && response.data)
        //              successCallback(response);
        //      }, function (error) {
        //          errorCallback(error);
        //      });
        // };
}
]);
