angular.module("taurus.escrowModule").service('escrowService', ['httpService', 'urlService', 'baseConfig', '$auth', '$state', '$rootScope', '$cookieStore', function (httpService, urlService, baseConfig, $auth, $state, $rootScope, $cookieStore) {
    "use strict";

    this.getCurrentSell = function (successCallback, errorCallback) {
        httpService.getData(urlService.getUrl('CURRENT_SELL'), null, function (response) {
            if (response && response.data)
                successCallback(response.data);
        }, function (error) {
            errorCallback(error);
        });
    };


    this.getRecentTrade = function (successCallback, errorCallback) {
        httpService.getData(urlService.getUrl('RECENT_TRADE'), null, function (response) {
            if (response && response.data)
                successCallback(response.data);
        }, function (error) {
            errorCallback(error);
        });
    };


    this.getCurrentBuy = function (successCallback, errorCallback) {
        httpService.getData(urlService.getUrl('CURRENT_BUY'), null, function (response) {
            if (response && response.data)
                successCallback(response.data);
        }, function (error) {
            errorCallback(error);
        });
    };

    this.getEngineBuy = function (req, successCallback, errorCallback) {
        httpService.postData(urlService.getUrl('ENGINE_BUY'), req, function (response) {
            if (response && response.data)
                successCallback(response.data);
        }, function (error) {
            errorCallback(error);
        });
    };

    this.getEngineBuyMarket = function (req, successCallback, errorCallback) {
        httpService.postData(urlService.getUrl('ENGINE_BUY_MARKET'), req, function (response) {
            if (response && response.data)
                successCallback(response.data);
        }, function (error) {
            errorCallback(error);
        });
    };

    this.getEngineSell = function (req, successCallback, errorCallback) {
        httpService.postData(urlService.getUrl('ENGINE_SELL'), req, function (response) {
            if (response && response.data)
                successCallback(response.data);
        }, function (error) {
            errorCallback(error);
        });
    };

    this.getEngineSellMarket = function (req, successCallback, errorCallback) {
        httpService.postData(urlService.getUrl('ENGINE_SELL_MARKET'), req, function (response) {
            if (response && response.data)
                successCallback(response.data);
        }, function (error) {
            errorCallback(error);
        });
    };

    this.getOrders = function (successCallback, errorCallback) {
        httpService.getData(urlService.getUrl('GET_ORDERS'), null, function (response) {
            if (response && response.data)
                successCallback(response.data);
        }, function (error) {
            errorCallback(error);
        });
    };

    this.getGraphData = function (successCallback, errorCallback) {
        httpService.getData(urlService.getUrl('GRAPH_DATA'), null, function (response) {
            if (response && response.data)
                successCallback(response.data);
        }, function (error) {
            errorCallback(error);
        });
    };

    this.getBalance = function (successCallback, errorCallback) {
        httpService.getData(urlService.getUrl('BALANCE'), null, function (response) {
            if (response && response.data)
                successCallback(response.data);
        }, function (error) {
            errorCallback(error);
        });
    };

    this.getMarketValue = function (req, successCallback, errorCallback) {
        httpService.postData(urlService.getUrl('MARKET_OVERVIEW'), req, function (response) {
            if (response && response.data)
                successCallback(response.data);
        }, function (error) {
            errorCallback(error);
        });
    };


    this.cancelOrder = function (req, successCallback, errorCallback) {
        httpService.postData(urlService.getUrl('CANCEL_ORDER'), req, function (response) {
            if (response && response.data)
                successCallback(response.data);
        }, function (error) {
            errorCallback(error);
        });
    };

    this.getClosedOrders = function (successCallback, errorCallback) {
        httpService.getData(urlService.getUrl('CLOSED_ORDERS'), null, function (response) {
            if (response && response.data)
                successCallback(response.data);
        }, function (error) {
            errorCallback(error);
        });
    };

    this.getInstantOrderApproximation = function (instantOrderApproxParams, successCallback, errorCallback) {
        httpService.postData(urlService.getUrl('GET_RATE'), instantOrderApproxParams, function (response) {
            if (response && response.data)
                successCallback(response.data);
        }, function (error) {
            errorCallback(error);
        });
    };

    this.authenticateFirebaseDb = function (authToken) {
        return firebase.auth().signInWithCustomToken(authToken);
    };

    /*** API calls related to escrow functionality started ****/

    this.generateEscrowLink = function (reqData, successCallback, errorCallback) {
        var that = this;
        httpService.postData(
            urlService.getPlainUrl('GENERATE_ESCROW_LINK'),
            reqData,
            function (response) {
                if (response && response.data) {
                    that.activateEscrowLink(response.data.link, successCallback, errorCallback);
                }
            },
            function (error) {
                errorCallback(error);
            }
        );
    };

    this.activateEscrowLink = function (link, successCallback, errorCallback) {
        var finalUrl = urlService.getPlainUrl('ACTIVATE_ESCROW_LINK');
        finalUrl = finalUrl.replace('{link}', link);

        httpService.getRequest(
            finalUrl,
            function (response) {
                successCallback(finalUrl);
            },
            function (response) {
                errorCallback(response);
            }
        );
    };
    
    this.getEscrowList = function (reqData, successCallback, errorCallback) {
        httpService.postData(
            urlService.getPlainUrl('GET_ALL_ESCROWS_LIST'),
            reqData,
            function (response) {
                if (response && response.data) {
                    successCallback(response.data);
                }
            },
            function (error) {
                errorCallback(error);
            }
        );
    };
    
    this.getEscrowSecretInfo = function (secretKey, successCallback, errorCallback) {
        var finalUrl = urlService.getPlainUrl('GET_SECRET_DETAILS');
        finalUrl = finalUrl.replace('{secret}', secretKey);

        httpService.getRequest(
            finalUrl,
            function (response) {
                successCallback(response.data);
            },
            function (response) {
                errorCallback(response);
            }
        );
    };
    
    this.releaseFunds = function (secretKey, successCallback, errorCallback) {
        var finalUrl = urlService.getPlainUrl('RELEASE_FUNDS');
        finalUrl = finalUrl.replace('{secret}', secretKey);

        httpService.getRequest(
            finalUrl,
            function (response) {
                successCallback(response);
            },
            function (response) {
                errorCallback(response);
            }
        );
    };


}]);
