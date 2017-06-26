"use strict";

angular.module("taurus").service('toastMessagesService', ['$mdToast',
    function($mdToast) {


      var toastTemplate = function(message, toastIcon, iconColor) {
        return (
          '<md-toast>' +
              '<div class="md-toast-content">' +
                '<i class="mdi ' + toastIcon + '" style="color: ' + iconColor + '; font-size: 20px;"></i>' +
                message + 
                '&nbsp; &nbsp;' +
              '</div>' +
            '</md-toast>'
        );
      }


      this.failureToast = function(message) {
        $mdToast.show({
          hideDelay: 3000,
          position: 'bottom left',
          template: toastTemplate(message, '', '')
        });
      };
      
      this.successToast = function(message) {
        $mdToast.show({
          hideDelay: 3000,
          position: 'bottom left',
          template: toastTemplate(message, 'mdi-check', 'green')
        });
      };

      this.warnToast = function(message) {
        $mdToast.show({
          hideDelay: 3000,
          position: 'bottom left',
          template: toastTemplate(message, '', '')
        });
      };



      // this.successToast = function(message) {
      //   $mdToast.show(
      //     $mdToast.simple()
      //       // .theme('success-toast')
      //       .textContent(message + '<i class="mdi mdi-check-cirlce"></i>')
      //       .position('bottom')
      //       .hideDelay(3000)
      //   );
      // };

      // this.warnToast = function(message) {
      //   $mdToast.show(
      //     $mdToast.simple()
      //       // .theme('warn-toast')
      //       .textContent(message)
      //       .position('bottom')
      //       .hideDelay(3000)
      //   );
      // };



    }]);
