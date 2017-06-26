'use strict';
angular.module('taurus.walletModule')
    .controller('WalletCtrl', ['$q', '$scope', '$timeout', '$filter', 'walletService', '$auth', '$cookies', '$state', 'urlService',
        function($q, $scope, $timeout, $filter, walletService, $auth, $cookies, $state, urlService) {
            var vm = this;
            $scope.transactions = {};
            vm.displayActionFullName = displayActionFullName;
            vm.downloadAsCsv;
            vm.url;
            $scope.wallet = function() {

                walletService.wallet(function successBlock(data) {
                    // console.log(data);
                    var transaction = JSON.parse(data);

                    // In order for the table's column sort to work, strings are parsed to numbers (where applicable)
                    $scope.transactions = transaction.entries.map(function(item, index) {
                        var numArr = ['cad', 'btc', 'rate', 'fee', 'net'];
                        console.log(item);
                        for (var i in item) {
                            if (item[i] && numArr.indexOf(i) > -1) {
                                item[i] = parseFloat(item[i]);
                            };
                        };

                        if (item.status === 'canceled') {
                            item.status = 'declined';
                        };
                        if (item.status === 'verify') {
                            item.status = 'pending';
                        };

                        // Change dates to be same format and Date type (they come in as 'dd/mm/yyyy hh:mm:ss' string or string in milliseconds)
                        if (item.date.indexOf(' ') == -1) {
                            item.date = new Date(parseInt(item.date));
                        } else {
                            var holdArr = item.date.split(' ');

                            //date
                            var day = holdArr[0].split('/')[0];
                            var month = holdArr[0].split('/')[1] - 1;
                            var year = holdArr[0].split('/')[2];

                            //time of day
                            var hours = holdArr[1].split(':')[0];
                            var minutes = holdArr[1].split(':')[1];
                            var seconds = holdArr[1].split(':')[2];

                            var newDate = new Date(year, month, day);
                            var newDate = new Date();
                            newDate.setDate(day);
                            newDate.setMonth(month);
                            newDate.setFullYear(year);
                            newDate.setHours(hours);
                            newDate.setMinutes(minutes);
                            newDate.setSeconds(seconds);

                            item.date = newDate;
                        };

                        return item;
                    });
                    console.log('success');
                    console.log($scope.transactions);

                    vm.downloadAsCsv = d3.csv.format($scope.transactions);
                    var blob = new Blob([vm.downloadAsCsv], {
                        type: 'text/plain'
                    });
                    vm.url = (window.URL || window.webkitURL).createObjectURL(blob);


                    vm.transactionsCopy = $scope.transactions;

                    vm.query = {
                        order: 'name',
                        limit: 10,
                        page: 1,
                    };

                }, function failureBlock(error) {
                    console.log(error);
                });
            };

            //watches filters/search
            $scope.$watchGroup(['type', 'status', 'action', 'search'], function(newVals) {
                $scope.transactions = $filter('filter')(vm.transactionsCopy, newVals[0]);
                $scope.transactions = $filter('filter')($scope.transactions, newVals[1]);
                $scope.transactions = $filter('filter')($scope.transactions, newVals[2]);
                $scope.transactions = $filter('filter')($scope.transactions, newVals[3]);
            });

            // $scope.$watch('$scope.transactions.length', function(val) {
            //     vm.query.count = val;
            // });


            vm.toggleLimitOptions = function() {
                vm.limitOptions = vm.limitOptions ? undefined : [10, 20, 30, 50];
            };



            $scope.init = function() {
                //  $scope.depositSummary();
                // $scope.withdrawalSummary();

                $scope.wallet();
            };
            $scope.init();




            vm.bitcoinOrCad = bitcoinOrCad;
            vm.actionColor = actionColor;
            vm.filterResults = filterResults;
            vm.statusIcon = statusIcon;
            vm.statusTooltip = statusTooltip;

            vm.filterAlias;

            function filterResults() {
                vm.query.page = 1;
            };

            vm.limitOptions = [10, 20, 30, 50];

            vm.options = {
                // rowSelection: false,
                // multiSelect: false,
                // autoSelect: false,
                // decapitate: false,
                // largeEditDialog: false,
                // boundaryLinks: false,
                limitSelect: true,
                pageSelect: true
            };

            vm.categories = ['Type', 'Status', 'Date', 'Action', 'Bitcoin', 'CAD', 'Spot Price', 'Fee', 'Net Received'];

            vm.types = [
                null,
                'deposit',
                'trade',
                'referral',
                'withdrawal'
            ];

            vm.statuses = [
                null,
                'complete',
                'declined',
                'pending'
            ];
            // converts status string to icon
            function statusIcon(status) {
                switch (status) {
                    case 'complete':
                        return 'mdi-check-circle';
                    case 'declined':
                        return 'mdi-alert-circle';
                    case 'pending':
                        return 'mdi-timer-sand';
                    default:
                        console.log(status);
                        return;
                };
            };

            function statusTooltip(status) {
                switch (status) {
                    case 'complete':
                        return 'Complete';
                    case 'declined':
                        return 'Declined/Canceled';
                    case 'pending':
                        return 'Pending';
                    default:
                        console.log(status);
                        return;
                };
            };

            vm.actions = [
                null,
                'Bitcoin',
                'Buy',
                'Cash in Person',
                'Cheque',
                'Referral Earnings',
                'INTERAC Online',
                'Sell',
                'Coupon',
                'Bank Wire'
            ];

            //Based on text in vm.transaction.action, determines if bitcoin or CAD currency formatting is needed
            //Actions that start with 'Buy' indicate bitcoin 'fee' and bitcoin 'net received'
            function bitcoinOrCad(action, bitcoin) {
                bitcoin = bitcoin || "";

                return (/buy|bitcoin/i.test(action) || (/referral/i.test(action) && bitcoin));
            };

            var actionColors = {
                bitcoin: '#F9A825',
                buy: 'rgb(76,175,80)', //green
                cash: '#283593',
                cheque: '#B71C1C',
                feeShare: '#616161',
                interac: '#00B8D4',
                sell: 'rgb(255,87,34)', //orange (warn)
                voucher: '#00796B',
                wireTransfer: '#AA00FF',
                cou: 'rgb(0,0,0)',
            };

            function actionColor(action) {
                switch (true) {
                    case /buy/.test(action):
                        //green (accent)
                        return actionColors.buy;
                    case /sell/.test(action):
                        //md-warn, orange/red
                        return actionColors.sell;
                    case /btc/.test(action):
                        //orange/yellow
                        return actionColors.bitcoin;
                    case /ip/.test(action):
                        //indigo
                        return actionColors.cash;
                    case /ee/.test(action):
                        //grey
                        return actionColors.feeShare;
                    case /io/i.test(action):
                        //blue
                        return actionColors.interac;
                    case /bw/.test(action):
                        //purple
                        return actionColors.wireTransfer;
                    case /Voucher/.test(action):
                        //teal
                        return actionColors.voucher;
                    case /ch/.test(action):
                        //dark red
                        return actionColors.cheque;
                    default:
                        return;
                }
            };

            function displayActionFullName(action) {
                switch (true) {
                    case /buy/i.test(action):
                        return 'Buy';
                    case /sell/i.test(action):
                        return 'Sell';
                    case /btc/i.test(action):
                        return 'Bitcoin';
                    case /ip/i.test(action):
                        return 'Cash in Person';
                    case /fee/i.test(action):
                        return 'Referral Earnings';
                    case /io/i.test(action):
                        return 'Interac Online';
                    case /bw/i.test(action):
                        return 'Bank Wire';
                    case /Coupon/i.test(action):
                        return 'Coupon';
                    case /ch/i.test(action):
                        return 'Cheque';
                    default:
                        return;
                };
            };



        }
    ]); //controller