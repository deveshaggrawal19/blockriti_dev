angular.module("taurus.buysellModule").factory('amountVisibleService', function () {

        // var showAmount = false;

        return {
            showAmount: false,
            setShowAmount: function(newValue) {
                this.showAmount = newValue;
            }
        };
          
});