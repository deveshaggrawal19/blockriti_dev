angular.module("taurus.tradeModule").factory('amountVisibleService', function () {

        // var showAmount = false;

        return {
            showAmount: false,
            setShowAmount: function(newValue) {
                this.showAmount = newValue;
            }
        };
          
});