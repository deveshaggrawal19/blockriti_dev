'use strict';
angular.module('taurus.loggedInNavModule')
        .controller('LoggedInNavCtrl', ['$scope', '$state','loggedInNavService', '$mdToast', 'toastMessagesService', 'amountVisibleService', '$stateParams', '$firebaseObject', '$mdSidenav','$mdDialog', function($scope, $state, loggedInNavService, $mdToast, toastMessagesService, amountVisibleService, $stateParams, $firebaseObject, $mdSidenav, $mdDialog) {

        var vm            = this;
        vm.balance;
        vm.getBalance     = getBalance;
        vm.logout         = logout;
        vm.navigationBar  = navigationBar;

/*         vm.menuItems = [
            {
                name:   'About',
                url:    'about',
                icon:   'mdi mdi-information'
            },
            {
                name:   'FAQ',
                url:    'faq',
                icon:   'mdi mdi-comment-question-outline'
            },
            {
                name:   'Help',
                url:    'help',
                icon:   'mdi mdi-help'
            },
            {
                name:   'Fee Schedule',
                url:    'fee-schedule',
                icon:   'mdi mdi-currency-usd'
            },
            {
                name:   'Terms of Service',
                url:    'terms-of-service',
                icon:   'mdi mdi-book-open'
            },
            {
                name:   'Privacy Policy',
                url:    'privacy-policy',
                icon:   'mdi mdi-lock'
            },
            {
                name:   'API',
                url:    'api',
                icon:   'mdi mdi-code-braces'
            }
        ]; */

	vm.menuItems = [

            {
                name:   'Help',
                url:    'help',
                icon:   'mdi mdi-help'
            },
        ];	
		
        vm.openSideNavBar = function() {
            $mdSidenav('logged-in-nav-sidebar').open();
        };

        vm.showHomeDialog=function (ev, name) {
            var name = name;
            $mdDialog.show({
                controller: 'HomeCtrl as home',
                templateUrl: 'modules/home/views/' + name + '.html',
                parent: angular.element(document.body),
                targetEvent: ev,
                clickOutsideToClose: true,
                fullscreen: true
            });
        };
		

        function navigationBar(stateName) {
           $state.go(stateName);
        }


            function getBalance() {
                var accessToken = localStorage.getItem("access_token");
                loggedInNavService.authenticateFirebaseDb(accessToken).then(function(result){
                    var userId = "user:"+localStorage.getItem('client');
                    var balancesRef = firebase.database().ref().child("profileData/"+userId+"/balances");
                    var data = $firebaseObject(balancesRef);
                    console.log(data);
                    vm.balance = data;
                }).catch(function(error){
                    console.error(error);
                });
            };

            getBalance();



        // $scope.showAmount = amountVisibleService.showAmount;
        // $scope.$watch(function () { 
        //     return amountVisibleService.showAmount; 
        // }, function (newValue, oldValue) {
        //     if (newValue !== oldValue) {
        //         $scope.showAmount = newValue;
        //         if ($scope.showAmount === true) {
        //             $('.stats-card').show()
        //         } else {
        //             $('.stats-card').hide()
        //         }
        //     };
        // });
        if ($state.current.url !== '/trade') {
            $('.stats-card').show();
        };

        // $scope.$watch('showAmount', function(val) {
        //     console.log(val);
        //     console.log($scope.showAmount);
        // });

        //Logout function
            // connect to button
            // build responses

            ///"1. On success
            // {""code"":160}
            //
            // 2. On failure
            // 500 Internal Server Error
            // {""code"":151}
            // OR
            // 401 Unauthorised
            // {""code"":23, ""body"":null}"

        function logout() {
            console.log('logout!');
            localStorage.clear();

            loggedInNavService.logout(function successBlock(data) {
				$state.go('home');
                toastMessagesService.successToast('Logged out');
                console.log(data);
            }, function failureBlock(error){
                console.log(error);
                toastMessagesService.successToast('Failure on logout');
                $state.go('home');
            });
          };

  }]); //controller
