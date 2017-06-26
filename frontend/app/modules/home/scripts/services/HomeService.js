angular.module("taurus.homeModule").service('HomeService', ['httpService',
		 'urlService',  'baseConfig', '$auth', '$state', '$rootScope', '$cookieStore',
    function (httpService, urlService, baseConfig, $auth, $state, $rootScope, $cookieStore) {
        "use strict";

      
        this.getMarketValue = function (req,successCallback, errorCallback) {
				    httpService.postData(urlService.getUrl('MARKET_OVERVIEW'), req, function (response) {
		                if (response && response.data)
		                    successCallback(response.data);
		            }, function (error) {
		                errorCallback(error);
		            });
        };

		this.getBTCPrices = function (successCallback, errorCallback) {
			httpService.getRequest(urlService.getOrbeonUrl('GET_BTC_PRICES'), function (response) {
				if(response && response.data) {
					successCallback(response.data);
				}
			}, function (error) {
				errorCallback(error);
			});
		};

		}]);
