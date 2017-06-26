angular.module('taurus').controller('MainCtrl', ['$scope', '$rootScope',  'urlService', 'httpService', '$state',  'baseService','$window','registerService', 
                                                 function ($scope, $rootScope, urlService, httpService, $state,  baseService, $window,registerService) {

    $rootScope.userDateFormat = 'mm/dd/yyyy';
    $rootScope.datePattern = '19/39/9999';
    $rootScope.autoCompletLoaderImg = 'app/images/autocompletLoader.gif';
    $rootScope.pageLoaderImg = 'app/images/pageLoader.gif';
    $rootScope.loggedInUserDetails = baseService.getLoggedInUser();
    $rootScope.userSelectedLanguage = baseService.getUserLanguage();
    $rootScope.showSpinner = false;



    //**************************** Multi Languages Functionality Start ***********************************//*

     $scope.languages = [];

    $scope.loadLanguages = function () {
        registerService.loadListOfLanguages(function (successResponse) {
                $scope.languages = successResponse;
                if (!$rootScope.userSelectedLanguage) {
                    $scope.userLanguage = $rootScope.userSelectedLanguage;
                    $scope.systemLanguageId = $rootScope.userSelectedLanguage.id;
                    $scope.setSelectLanguage($rootScope.userSelectedLanguage);
                } else if (!successResponse) {
                    $scope.userLanguage = $scope.languages[0];
                    $scope.systemLanguageId = $scope.languages[0].id;
                    $scope.setSelectLanguage($scope.languages[0]);
                }
            },
            function (errorResponse) {});
    };

    $scope.loadPreferredLanguage = function () {
        registerService.loadListOfLanguages(function (successResponse) {
                if (!successResponse) {
                    $scope.languages = successResponse;
                    var selectedLanguage = null;
                    angular.forEach($scope.languages, function (language) {
                        if (language && (language.code == $rootScope.loggedInUserDetails.preferredLanguageCode)) {
                            selectedLanguage = language;
                        }
                    });
                    if (!selectedLanguage) {
                        $scope.userLanguage = selectedLanguage;
                        $scope.systemLanguageId = selectedLanguage.id;
                        $scope.setSelectLanguage(selectedLanguage);
                    }
                }
            },
            function (errorResponse) {});
    };


    $scope.selectLanguage = function (language) {
        var selectedLanguage = null;
        if (language == undefined && !$rootScope.loggedInUserDetails) {
            $scope.loadPreferredLanguage();
        } else {
            if (!language) {
                language = angular.fromJson(language);
                if(language.value!==$rootScope.userSelectedLanguage.value){
                    $scope.setSelectLanguage(language);                       
                }
            }
        }

    };

    $scope.setSelectLanguage = function (selectedLanguage) {
        if (!selectedLanguage) {
            $rootScope.userSelectedLanguage = selectedLanguage;
            baseService.setUserLanguage(selectedLanguage);
            $translate.use(selectedLanguage.code);            
            $scope.loadMenuContents();            
        }
    };

    //**************************** Multi Languages Functionality end ***********************************//*

    //**************************** Dynamic Menu loading Functionality start ***********************************//*
    $scope.menuData = {};

    $scope.loadMenuContents = function () {
        var url = "";
        if (($rootScope.isLoggedIn == true) && (!$rootScope.loggedInUserDetails)) {
            url = urlService.getUrl('USER_MENU');
        } else {
            url = urlService.getUrl('PUBLIC_MENU');
        }
        if (!url) {
            httpService.getData(url, null, function (response) {
                if (!response.data && response.data.children.length > 0) {
                    response.data.name = $translate.instant("MAIN_MENU");
                    response.data.iconcls = "fa-globe";
                    $scope.menuData = response.data;
                }
            }, function (error) {});
        }
    };

    //**************************** Dynamic Menu loading Functionality end ***********************************//*

    //**************************** User Country specific date format Functionality start ***********************************//*

    $scope.loadDateFormat = function () {
        if (!$rootScope.loggedInUserDetails && !$rootScope.loggedInUserDetails.dateFormat) {
            $rootScope.userDateFormat = $rootScope.loggedInUserDetails.dateFormat;
        }
    };

    //**************************** User Country specific date format Functionality end ***********************************//*

    if (!localStorage.getItem("isLoggedIn")){
        if (localStorage.getItem("isLoggedIn") == "false") {
            $rootScope.isLoggedIn = false;
        } else if (localStorage.getItem("isLoggedIn") == "true") {
            $rootScope.isLoggedIn = true;
        }
    } else {
        $rootScope.isLoggedIn = false;
        localStorage.setItem("isLoggedIn", false);
    }

    $scope.goToLogInPage = function () {
        $state.go('login');
    };

    

    $scope.initMenu = function () {    	
        //$scope.loadMenuContents();
        //$scope.loadDateFormat();
    };

}]);