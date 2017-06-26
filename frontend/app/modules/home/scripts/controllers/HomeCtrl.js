'use strict';
angular.module('taurus.homeModule')
				.controller('HomeCtrl', ['$scope', '$mdSidenav', '$mdDialog', '$mdMedia','HomeService', '$state', '$firebaseObject', '$timeout', 'ShareEmailService', 'toastMessagesService',
          function ($scope, $mdSidenav, $mdDialog, $mdMedia, HomeService, $state, $firebaseObject, $timeout, ShareEmailService, toastMessagesService) {

                    var ref = firebase.database().ref();

                    $scope.marketValues = $firebaseObject(ref.child('getMarketOverview'));
                    console.log($scope.marketValues);
        var vm = this;
        $scope.test="test";
        $scope.sidenavOpen;
        $scope.menuItems;
        $scope.loginVisible 	= false;
        $scope.authBoxVisible = false;
        $scope.btc_inr_price = 0;

	//carouselElement start	

		
//carouselElement end		
		
		
        $(document).on("keyup","#btc-count", function (event) {
            var keyCode = event.keyCode || event.which;
            var btcCount = parseFloat($("#btc-count").val());

            if(keyCode >= 48 && keyCode <= 57){
                vm.getBTCINRPrice(btcCount);
            }
        });

        vm.getBTCINRPrice = function(btcCount) {
            if($scope.btc_inr_price == 0){
                vm.getBTCPricesFromServer(btcCount);
            }else{
                $scope.btc_final_inr_price = $scope.btc_inr_price * btcCount;
                $timeout(function () {
                    $scope.$digest();
                },500);
            }
        };

        vm.toggleSlideAnswer  = toggleSlideAnswer;

        /*		vm.marketValues 			= {};
				$scope.getMarketValue;*/

        vm.displayLinkToDemo = displayLinkToDemo;
        vm.goToTrade = goToTrade;

        function goToTrade() {
          $state.go('trade');
        };
        
        function displayLinkToDemo() {
          return localStorage.getItem('taurus_token') ? false : true;
        };
        vm.demoRegisterEmail = "";
        vm.shareDemoRegisterEmail = shareDemoRegisterEmail;
        function shareDemoRegisterEmail() {
          ShareEmailService.setEmail(vm.shareDemoRegisterEmail);
        };

        $scope.$watch(function() {
          return vm.demoRegisterEmail;
        }, function() {
          ShareEmailService.setEmail(vm.demoRegisterEmail);
        });

        $scope.firstName = '';

        $scope.$watch('firstName', function (newValue, oldValue) {
            if (newValue !== oldValue) Data.setFirstName(newValue);
        });


        $timeout(function () {
          if ($state.current.name === 'home.tos') {
              $mdDialog.show({
                // controller: 'HomeCtrl',
                templateUrl: 'modules/home/views/terms-of-service.html',
                parent: angular.element(document.body),
                clickOutsideToClose: true,
                fullscreen: true,
            });
          };
        }, 0);

				vm.negativeValue;
        $scope.openSidenav = function() {
          $mdSidenav('home-menu-sidenav').open();
        };
        vm.goDemoRegister = goDemoRegister;
        function goDemoRegister(ev) {
          $state.go('demo');
        };
        /***Get Market Overview****/
		vm.negativeValue = function(myValue){
		    var num = parseInt(myValue);
		    //alert(myValue);
		    if(myValue < 0) {
			    var css = { 'color':'red' };
			    return css;
				  } else {
					  var css = { 'color':'green' };
				    return css;
				  }
        };
		var param={"test":"true"};
        vm.getBTCPricesFromServer = function(btcCount) {
            HomeService.getBTCPrices(function successBlock(data) {
                console.log("***");
                $scope.btc_inr_price = parseFloat(data.latest.currencies.INR.last);
                $scope.btc_final_inr_price = $scope.btc_inr_price * btcCount;
            }, function failureBlock() {
                console.log("Failed to get btc prices.");
            });
        };

        /*vm.getMarketValue=function() {
				     HomeService.getMarketValue(param,function successBlock(data){
	    		 $scope.marketValues = data;
				 		console.log(data.lastPrice);
	         },function failureBlock(){
	         });
		};*/


      //Populates sidenavMenu (each as list element)
      $scope.menuItems = [
            {
              name:   'About',
              url:    'about',
              icon:   'mdi mdi-information',
            },
            {
              name:   'FAQ',
              url:    'faq',
              icon:   'mdi mdi-comment-question-outline',
            },
            {
              name:   'Help',
              url:    'help',
              icon:   'mdi mdi-help',
            },
            {
              name:   'Fee Schedule',
              url:    'fee-schedule',
              icon:   'mdi mdi-currency-usd',
            },
            {
              name:   'Terms of Service',
              url:    'terms-of-service',
              icon:   'mdi mdi-book-open',
            },
            {
              name:   'Privacy Policy',
              url:    'privacy-policy',
              icon:   'mdi mdi-lock',
            },
            {
              name:   'API',
              url:    'api',
              icon:   'mdi mdi-code-braces',
            },
        ];

        //Login & Register - Goes to AuthenticationCtrl
        $scope.showLoginRegisterDialog=function (ev, name) {
            var name = name;
            $mdDialog.show({
                // controller: 'AuthenticationCtrl as authentication',
                templateUrl: 'modules/authentication/views/' + name + '.html',
                parent: angular.element(document.body),
                targetEvent: ev,
                clickOutsideToClose: true,
								fullscreen: true,
            });
        };

        //md-Dialog off each list item in sidenavMenu
        $scope.showHomeDialog=function (ev, name) {
            var name = name;
            $mdDialog.show({
                controller: 'HomeCtrl as home',
                templateUrl: 'modules/home/views/' + name + '.html',
                parent: angular.element(document.body),
                targetEvent: ev,
                clickOutsideToClose: true,
								fullscreen: true,
            });
        };

        $scope.cancel = function () {
            $mdDialog.cancel();
        };


        // Attaches FAB to bottom of background-image div. FAB points down
				// When not at top, rotates FAB 180 degrees and moves it closer to bottom
        var myWindow = $(window);
        myWindow.scroll(function() {
						if(myWindow.scrollTop() === 0) {
								$("#home-fab").removeClass("rotate-fab").addClass("resting-position");
						} else {
								$("#home-fab").removeClass("resting-position").addClass("rotate-fab");
						}
				});
				//jQuery smooth scroll on FAB
				$scope.scrollTo=function() {
						if(myWindow.scrollTop() === 0) {
								$('html, body').animate({
										scrollTop: $("#what-is-bitcoin").offset().top
								}, 800);
						} else {
								$('html, body').animate({
										scrollTop: $("#home-main-background").offset().top
								}, 500);
						}
				};


      $scope.init = function() {
          // $scope.depositSummary();
          // $scope.withdrawalSummary();
          //  vm.getMarketValue();
          vm.getBTCINRPrice(1);
          $timeout(function () {
              $("#btc-count").val("1");
          },300);

          var expiredSession = localStorage.getItem('sessionExpired');
          if(expiredSession){
              localStorage.removeItem('sessionExpired');
              toastMessagesService.failureToast('Session has timed out');
          }

          var isLoggedIn = localStorage.getItem('isLoggedIn');
          if(isLoggedIn){
              $state.go("trade");
          }
      };

      $scope.init();

      $scope.testCode = function() {
        // $('iframe').contents();
        console.log($('iframe').contents());
      };

      function toggleSlideAnswer(answerId) {
        $('#answer-' + answerId).css('max-width', $('#question-' + answerId).css('width'));
        $('#answer-' + answerId).slideToggle();
        $('#question-' + answerId + ' i').toggleClass("mdi-menu-down mdi-menu-up");
        $('#question-' + answerId + ' button').toggleClass("md-accent md-warn");

      };




      }]); //controller

	  
	      angular.module('taurus.homeModule').directive('ngCarousel', function() {
      return function(scope, element, attrs) {
        var el = element[0];
        var containerEl = el.querySelector("ul");
        var slidesEl = containerEl.querySelectorAll("li");
        scope.numSlides = slidesEl.length;
        scope.curSilde = 1;   
        scope.$watch('curSlide', function(num) {
          containerEl.style.left = (-1*100*(num-1)) + '%';
        });
        
        el.style.position = 'absolute';
        el.style.overflow = 'hidden';

        containerEl.style.position = 'absolute';
        containerEl.style.width = (scope.numSlides*100)+'%';
        containerEl.style.listStyleType = 'none';
        containerEl.style.margin =0;
        containerEl.style.padding=0;
        containerEl.style.transition = '1s';
  
        for(var i=0; i<slidesEl.length; i++) {
          var slideEl = slidesEl[i];
          slideEl.style.display = 'inline-block';
          slideEl.style.width = (100/scope.numSlides) + '%';

        }
      };
    });