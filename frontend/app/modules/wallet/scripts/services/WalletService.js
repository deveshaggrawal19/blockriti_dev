"use strict";

angular.module("taurus.walletModule").service('walletService', ['httpService',
		 'urlService',  'baseConfig', '$auth', '$state', '$rootScope', '$cookieStore',
    function (httpService, urlService, baseConfig, $auth, $state, $rootScope, $cookieStore) {


	  
	  this.wallet = function (successCallback, errorCallback){
          httpService.postData(urlService.getUrl('WALLET'), {"test": "true"}, function (response) {
              if (response && response.data)
                  successCallback(response.data);
          }, function (error) {
              errorCallback(error);
          });
     	};

}
]);
