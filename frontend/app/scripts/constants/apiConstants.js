"use strict";
/**
 * Creating api constants in taurus application
 *
 */
angular.module("taurus").factory('urlService', function (baseConfig) {

    var factory = {};

    factory.BASE_URL                = baseConfig.baseUrl; //'http://localhost:8080/taurus-web/';
    factory.READ_JSONFILE_URL       = baseConfig.readJsonFileUrl;
    factory.LOGIN                   = '/rest/user/login/';
    factory.REGISTER                = '/rest/user/register';
    factory.LOGOUT                  = '/rest/user/logout';
    factory.AUTHENTICATE            = '/rest/user/authenticate';
    factory.GET_TRADES              = '/rest/trade/getTrades';
    factory.GET_ORDERS              = '/rest/trade/getOrders';
    factory.CURRENT_SELL            = '/rest/trade/getCurrentSell/';
    factory.RECENT_TRADE            = '/rest/trade/getMostRecentTrades/';
    factory.CURRENT_BUY             = '/rest/trade/getCurrentBuy/';
    factory.ENGINE_BUY              = '/rest/engine/buy/';
    factory.ENGINE_BUY_MARKET       = '/rest/engine/buymarket/';
    factory.ENGINE_SELL             = '/rest/engine/sell/';
    factory.ENGINE_SELL_MARKET      = '/rest/engine/sellmarket/';
    factory.GRAPH_DATA              = '/rest/trade/getGraphData/';
    // factory.DEPOSIT_SUMMARY         = '/rest/fund/depositSummary/';
    // factory.WITHDRAWAL_SUMMARY      = '/rest/withdrawal/withdrawalSummary/';
    factory.FETCH_KEY               = '/rest/keystore/fetchKey/webUIn-g';
	factory.WALLET                  = '/rest/history/getUserHistory';
    factory.BALANCE                 = '/rest/trade/balance';
    factory.DEPOSIT_BTC             = '/rest/fund/depositBtc';
    factory.DEPOSIT_COUPON          = '/rest/fund/depositCoupon';
    factory.WITHDRAW_BTC            = '/rest/withdrawal/bitcoin';
    factory.WITHDRAW_WIRE           = '/rest/withdrawal/bankwire';
    factory.WITHDRAW_CHEQUE         = '/rest/withdrawal/cheque';
    factory.WITHDRAW_COUPON         = '/rest/withdrawal/coupon';
    factory.TWOFA_DEP               = '/rest/user/two_factor_authentication_dependancies/2fauth';
    factory.MARKET_OVERVIEW         = '/rest/trade/marketOverview';
	factory.CHANGE_PASSWORD         = '/rest/user/changePassword';
    factory.CHANGE_2FAUTH           = "/rest/user/two_factor_authentication/2fauth";
    factory.GET_SECURITY_SETTINGS   = '/rest/user/two_factor_authentication_dependancies/2fauth';
    factory.USER_INFO               = '/rest/user/userInfo';
    factory.VERIFY_USER             = '/rest/user/verifyUser';
	factory.GET_VERIFY              = '/rest/user/getUserVerificationDetails';
    factory.VERIFY_EMAIL            = '/rest/user/verifyEmail';
    factory.CANCEL_ORDER            = '/rest/engine/cancelOrders';
    factory.CLOSED_ORDERS           = '/rest/trade/getClosedOrders';
    factory.GET_RATE                = '/rest/trade/getRate';
    factory.FORGOT_PASSWORD         = '/rest/user/forgotPassword';
    factory.FORGOT_CONFIRM          = '/rest/user/forgotConfirm';
    factory.CHANGE_PIN              = '/rest/user/changePin';
    factory.CHANGE_PMAIL            = '/rest/user/two_factor_authentication/pmail';
    factory.CHANGE_SMS              = '/rest/user/two_factor_authentication/psms';
    factory.CHANGE_PGP              = '/rest/user/submitPGPKey';
    factory.API_CONF                = '/rest/user/addAPI';
    factory.GET_APIS                = '/rest/user/getUserApis';
    factory.DELETE_API              = '/rest/user/removeApi';
    factory.REFERRAL                = '/rest/user/getReferralLink';
    factory.DEPOSIT_INTERAC         = '/rest/fund/depositInterac';
    factory.SET_PIN                 = '/rest/user/setPin';
    factory.DEPOSIT_BANK            = '/rest/fund/depositBankWire';
    factory.UPLOADED_DOCUMENTS      = '/rest/user/getUploadedDocuments';
    factory.INTERAC_ALLOWED         = '/rest/user/getUserInteracStatus';
    factory.GET_LEVEL_ONE_DETAILS   = '/rest/user/getL1Details';
    factory.GET_BTC_PRICES = "https://104.155.230.23:8000/list_btc_prices";

    factory.BROKERAGE_BUY_ORDER = "https://shrikantbhalerao.com/brokerage/buy";
    factory.BROKERAGE_SELL_ORDER = "https://shrikantbhalerao.com/brokerage/sell";
    
    factory.GENERATE_ESCROW_LINK = "https://shrikantbhalerao.com/escrow/create-link";
    factory.ACTIVATE_ESCROW_LINK = "https://shrikantbhalerao.com/escrow/start/{link}";
    factory.GET_ALL_ESCROWS_LIST = "https://shrikantbhalerao.com/escrow/list";
    factory.GET_SECRET_DETAILS = "https://shrikantbhalerao.com/escrow/get-info?secretkey={secret}";
    factory.RELEASE_FUNDS = "https://shrikantbhalerao.com/escrow/release-funds?secretkey={secret}";
    factory.GET_SELLER_BALANCE = "https://shrikantbhalerao.com/gateway/get_seller_balance/{seller}";
    factory.PROCESS_PAYOUT = "https://shrikantbhalerao.com/gateway/payout/{seller}/{amount}/{currency}/{address}"
    factory.PROCESS_REQ_PAYOUT = "https://shrikantbhalerao.com/gateway/request_payment/{amount}/{currency}/{message}/{seller}/{customer}/{callback_url}";
    
    
    factory.getUrl = function (key) {
        return factory.BASE_URL + factory[key];
    };

    factory.getOrbeonUrl = function (key) {
        return factory[key];
    };

    factory.getPlainUrl = function (key) {
        return factory[key];
    };

    factory.getJsonFileUrl = function (key) {
        return factory.READ_JSONFILE_URL + factory[key];
    };

    
    factory.appendParamsToUrl = function (key, paramObject) {
        var url = factory[key];
        if (!!paramObject && typeof paramObject == "object") {
            url += "?";
            for (var key in paramObject) {
                if (paramObject.hasOwnProperty(key)) {
                    url = url + key + "=" + paramObject[key] + "&";
                }
            }
            var lastChar = url.slice(-1);
            if (lastChar == '&') {
                url = url.slice(0, -1);
            }

        }
        return url;
    };
    
    factory.getParameterizedUrl = function (key, paramObject) {
        var url = factory.BASE_URL + factory[key];
        if (paramObject !== null) {
            for (var key in paramObject) {
                if (paramObject.hasOwnProperty(key)) {
                    url = url.replace("{" + key + "}", paramObject[key]);
                }
            }
        }
        return url;
    };

    factory.getQueryParameterizedUrl = function (key, paramObject) {
        var url = factory.BASE_URL + factory[key];
        if (paramObject !== null) {
            url += "?";
            for (var key in paramObject) {
                if (paramObject.hasOwnProperty(key)) {
                    url = url + key + "=" + paramObject[key] + "&";
                }
            }
            var lastChar = url.slice(-1);
            if (lastChar == '&') {
                url = url.slice(0, -1);
            }

        }
        return url;
    };

    return factory;
});
