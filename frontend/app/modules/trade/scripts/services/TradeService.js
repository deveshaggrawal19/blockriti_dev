angular.module("taurus.tradeModule").service('tradeService', ['httpService',
		 'urlService', 'baseConfig', '$auth', '$state', '$rootScope', '$cookieStore',
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

     this.getEngineBuy= function (req,successCallback,errorCallback){
         httpService.postData(urlService.getUrl('ENGINE_BUY'), req, function (response) {
             if (response && response.data)
                 successCallback(response.data);
         }, function (error) {
             errorCallback(error);
         });
    	};

     this.getEngineBuyMarket= function (req,successCallback,errorCallback){
         httpService.postData(urlService.getUrl('ENGINE_BUY_MARKET'), req, function (response) {
             if (response && response.data)
                 successCallback(response.data);
         }, function (error) {
             errorCallback(error);
         });
    	};

    	this.getEngineSell= function (req,successCallback,errorCallback){
	        httpService.postData(urlService.getUrl('ENGINE_SELL'), req, function (response) {
	            if (response && response.data)
	                successCallback(response.data);
	        }, function (error) {
	            errorCallback(error);
	        });
   		};

	   	this.getEngineSellMarket= function (req,successCallback,errorCallback){
		       httpService.postData(urlService.getUrl('ENGINE_SELL_MARKET'), req, function (response) {
		           if (response && response.data)
		               successCallback(response.data);
		       }, function (error) {
		           errorCallback(error);
		       });
	  	};

	  	this.getOrders= function (successCallback,errorCallback){
		      httpService.getData(urlService.getUrl('GET_ORDERS'), null, function (response) {
		          if (response && response.data)
		              successCallback(response.data);
		      }, function (error) {
		          errorCallback(error);
		      });
	 		};

		 this.getGraphData= function (successCallback,errorCallback){
			     httpService.getData(urlService.getUrl('GRAPH_DATA'), null, function (response) {
			         if (response && response.data)
			             successCallback(response.data);
			     }, function (error) {
			         errorCallback(error);
			     });
			};

		this.getBalance= function (successCallback,errorCallback){
			httpService.getData(urlService.getUrl('BALANCE'), null, function (response) {
				if (response && response.data)
					successCallback(response.data);
			}, function (error) {
				errorCallback(error);
			});
		};

		this.getMarketValue = function (req,successCallback, errorCallback) {
				httpService.postData(urlService.getUrl('MARKET_OVERVIEW'), req, function (response) {
								if (response && response.data)
										successCallback(response.data);
						}, function (error) {
								errorCallback(error);
						});
		};


		this.cancelOrder= function (req,successCallback,errorCallback){
			httpService.postData(urlService.getUrl('CANCEL_ORDER'), req, function (response) {
				if (response && response.data)
					successCallback(response.data);
			}, function (error) {
				errorCallback(error);
			});
		};

        this.getClosedOrders= function (successCallback,errorCallback){
            httpService.getData(urlService.getUrl('CLOSED_ORDERS'), null, function (response) {
                if (response && response.data)
                    successCallback(response.data);
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

        this.authenticateFirebaseDb = function(authToken){
            return firebase.auth().signInWithCustomToken(authToken);
        };
}
]);
