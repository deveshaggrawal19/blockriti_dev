"use strict";
angular
    .module("taurus.depositWithdrawModule")
    .controller("DepositWithdrawCtrl", ["$scope", "$mdDialog", "depositWithdrawService", "toastMessagesService", "$base64", function ($scope, $mdDialog, depositWithdrawService, toastMessagesService, $base64) {

        var vm = this;

        vm.depositBtcQrCode;

        vm.verificationLevel = 2;
        vm.depositBankSubmit = depositBankSubmit;
        vm.interacAccessCheck = interacAccessCheck;

        var withdrawToast = function (code) {
            switch (code) {
                case 79:
                    toastMessagesService.successToast('Success');
                    break;
                case 71:
                    toastMessagesService.failureToast('Invalid Security Pin');
                    break;
                case 72:
                    toastMessagesService.failureToast('Incorrect currency format');
                    break;
                case 73:
                    toastMessagesService.failureToast('Insufficient balance');
                    break;
                case 74:
                    toastMessagesService.failureToast('Invalid bitcoin address');
                    break;
                case 75:
                    toastMessagesService.failureToast('Amount is less than min limit');
                    break;
                case 76:
                    toastMessagesService.failureToast('Amount is greater than max limit');
                    break;
                case 77:
                    toastMessagesService.failureToast('Required fields should not be empty');
                    break;
                case 78:
                    toastMessagesService.failureToast('Unverified user');
                    break;
                case 81:
                    toastMessagesService.failureToast('Withdrawal disabled');
                    break;
                default:
                    console.log(code);
                    toastMessagesService.failureToast('Withdraw error');
            }
            ;
        };

        var depositInteracToast = function (code) {
            switch (code) {
                case 240:
                    toastMessagesService.successToast('Deposited with Interac');
                    break;
                case 231:
                    toastMessagesService.failureToast('User is not verified');
                    break;
                case 232:
                    toastMessagesService.failureToast('User banned from using Interac');
                    break;
                case 233:
                    toastMessagesService.failureToast('Invalid currency/currency not allowed');
                    break;
                case 234:
                    toastMessagesService.failureToast('Amount below allowed limit');
                    break;
                case 235:
                    toastMessagesService.failureToast('Amount exceeds allowed limit');
                    break;
                case 236:
                    toastMessagesService.failureToast('Unabled to fund as request empty');
                    break;                                        
                default:
                    console.log(code);
                    toastMessagesService.failureToast('Interac deposit error');
            };
        };

        var depositVoucherToast = function (code) {
            switch (code) {
                case 220:
                    toastMessagesService.successToast('Success');
                    break;
                case 211:
                    toastMessagesService.failureToast('User is not verified');
                    break;
                case 212:
                    toastMessagesService.failureToast('The given coupon does not exist');
                    break;
                case 213:
                    toastMessagesService.failureToast('The coupon has already been used');
                    break;
                case 214:
                    toastMessagesService.failureToast('Incorrect use of coupon');
                    break;
                default:
                    console.log(code);
                    toastMessagesService.failureToast('Voucher deposit error');
            };
        };

        var withdrawVoucherToast = function (code) {
            switch (code) {
                case 200:
                    toastMessagesService.successToast('Success');
                    break;
                case 191:
                    toastMessagesService.failureToast('User is not verified');
                    break;
                case 192:
                    toastMessagesService.failureToast('Amount must be in digits');
                    break;
                case 193:
                    toastMessagesService.failureToast('Amount exceeds available balance');
                    break;
                case 194:
                    toastMessagesService.failureToast('Amount is greater than allowed balance');
                    break;
                case 195:
                    toastMessagesService.failureToast('Amount should be greater than 5');
                    break;
                case 196:
                    toastMessagesService.failureToast('Please enter values');
                    break;
                case 71:
                    toastMessagesService.failureToast('Incorrect security PIN');
                    break;
                default:
                    console.log(code);
                    toastMessagesService.failureToast('Voucher withdraw error');
            }
            ;
        };


        function getDepositBTC() {
            depositWithdrawService.getDepositBTC(function successBlock(data) {
                console.log(data);
                vm.depositBtcQrCode = data;
            }, function failureBlock(error) {
                console.log(error);
            });
        };
        //Deposit Bitcoin
        // Print out address and qr code
        getDepositBTC();


        //  function getWithdrawBTC(withdrawParams) {
        //      depositWithdrawService.getWithdrawBTC(withdrawParams, function successBlock(data) {
        //         console.log("this is the getWithdrawBTC call" + data);
        //         withdrawToast(data.code);
        //      }, function failureBlock(error) {
        //         console.log(error);
        //         withdrawToast(error.data.code);
        //      });
        //  };

        //  function getWithdrawWire(withdrawParams) {
        //      depositWithdrawService.getWithdrawWire(withdrawParams, function successBlock(data) {
        //          console.log("this is the getWithdrawWire call" + data);
        //      }, function failureBlock(error) {
        //      });
        //  };

        //  function getWithdrawCheque(withdrawParams) {
        //      depositWithdrawService.getWithdrawCheque(withdrawParams, function successBlock(data) {
        //          console.log("this is the getWithdrawCheque call" + data);
        //      }, function failureBlock(error) {
        //      });
        //  };


        vm.tooltipVisible;

        vm.depositInteracOnlineSubmit = depositInteracOnlineSubmit;
        vm.depositVoucherSubmit = depositVoucherSubmit;
        vm.withdrawVoucherSubmit = withdrawVoucherSubmit;
        vm.withdrawChequeSubmit = withdrawChequeSubmit;
        vm.withdrawWireTransferSubmit = withdrawWireTransferSubmit;
        vm.withdrawBitcoinSubmit = withdrawBitcoinSubmit;
        vm.getUserInfo = getUserInfo;
        vm.showTabDialog = showTabDialog;

        function showTabDialog(ev, name) {
            var name = name;
            $mdDialog.show({
                controller: 'DepositWithdrawCtrl',
                templateUrl: 'modules/deposit-withdraw/views/' + name + '.html',
                parent: angular.element(document.body),
                targetEvent: ev,
                clickOutsideToClose: true,
                fullscreen: true,
            });
        };
        function getUserInfo() {
            var data = {"test": "true"};
            depositWithdrawService.getUserInfo(data, function successBlock(data) {
                    vm.userinfo = data;
                    console.log("vm.userinfo: "+vm.userinfo);
                }, function failureBlock(error) {
                    console.log(error);
                }
            );
        };

        // DEPOSIT - INTERAC online
        vm.depositInteracOnline = {
            amount: "",
            currency: "cad"

        };
        vm.depositInteracOnlineDisplayInfo = {
            fee: "",
            net: ""
        };


        $scope.$watch(function () {
            return vm.depositInteracOnline.amount;
        }, function (value) {
            vm.depositInteracOnlineDisplayInfo.fee = (vm.depositInteracOnline.amount > 250 ? vm.depositInteracOnline.amount * 0.02 : 5);
            vm.depositInteracOnlineDisplayInfo.net = vm.depositInteracOnline.amount - vm.depositInteracOnlineDisplayInfo.fee;
        });

        function depositInteracOnlineSubmit(data) {
            var depositParams = {currency: vm.depositInteracOnline.currency, amount: data.amount};
            depositWithdrawService.getDepositInterac(depositParams, function successBlock(data) {
                console.log(data);
                depositInteracToast(data.code);
            }, function failureBlock(error) {
                console.log(error);
                depositInteracToast(error.data.code);
            });
        };

        vm.depositVoucher = {
            code: ""
        };

        function depositVoucherSubmit(depositParams) {
            depositWithdrawService.getDepositCoupon(depositParams, function successBlock(data) {
                console.log(data);
                depositVoucherToast(data.code);
            }, function failureBlock(error) {
                console.log(error);
                depositVoucherToast(error.data.code);
            });
        };


        vm.bankDetails = {
            "domestic": {
                "beneficiary": "",
                "beneficiary_address": "",
                "bank": "",
                "bank_address": "",
                "bank_swift": "",
                "route_transit_number": "",
                "account_number": "",
                "details": ""
            },

            "international": {
                "beneficiary": "",
                "beneficiary_address": "",
                "bank": "",
                "bank_address": "",
                "bank_swift": "",
                "bank_info": "",
                "account_number": "",
                "details": ""
            },
            "code": ""
        }

        function depositBankSubmit() {
            var depositParams = {test: "true"};
            depositWithdrawService.getDepositBank(depositParams, function successBlock(data) {
                console.log(data);
                vm.bankDetails = {
                    domestic: {
                        beneficiary: data.domestic.beneficiary,
                        beneficiary_address: data.domestic.beneficiary_address,
                        bank: data.domestic.bank,
                        bank_address: data.domestic.bank_address,
                        bank_swift: data.domestic.bank_swift,
                        route_transit_number: data.domestic.route_transit_number,
                        account_number: data.domestic.account_number,
                        details: data.domestic.details
                    }, international: {
                        beneficiary: data.domestic.beneficiary,
                        beneficiary_address: data.international.beneficiary_address,
                        bank: data.international.bank,
                        bank_address: data.international.bank_address,
                        bank_swift: data.international.bank_swift,
                        bank_info: data.international.bank_info,
                        account_number: data.international.account_number,
                        details: data.international.details
                    }
                }

                //depositVoucherToast(data.code);
            }, function failureBlock(error) {
                console.log(error);
                // depositVoucherToast(error.data.code);
            });
        };


        // WITHDRAW - bitcoin
        vm.withdrawBitcoin = {
            amount: "",
            address: "",
            code: ""
        };
        function withdrawBitcoinSubmit(withdrawParams) {
            var crypt = new JSEncrypt();
            crypt.setKey($base64.decode(localStorage.getItem('pkey')));

            var inputParams = {
             amount: withdrawParams.amount,
             address: withdrawParams.address,
            data: crypt.encrypt(JSON.stringify({code: withdrawParams.code}))
         }
            console.log(inputParams)
            depositWithdrawService.getWithdrawBTC(inputParams, function successBlock(data) {
                console.log("this is the getWithdrawBTC call" + data);
                withdrawToast(data.code);
            }, function failureBlock(error) {
                console.log(error);
                withdrawToast(error.data.code);
            });

        };

        // WITHDRAW - wire transfer
        vm.withdrawWireTransfer = {
            amount: "",
            address: "",
            bank_name: "",
            bank_address: "",
            account: "",
            swift: "",
            instructions: "",
            code: ""
        };

        vm.withdrawWireTransferDisplayInfo = {
            fee: "",
            net: ""
        }

        $scope.$watch(function () {
            return vm.withdrawWireTransfer.amount;
        }, function (value) {
            vm.withdrawWireTransferDisplayInfo.fee = ((vm.withdrawWireTransfer.amount > 5000 ? vm.withdrawWireTransfer.amount * 0.01 : 50));
            vm.withdrawWireTransferDisplayInfo.net = vm.withdrawWireTransfer.amount - vm.withdrawWireTransferDisplayInfo.fee;
        });


        function withdrawWireTransferSubmit(withdrawParams) {
            var crypt = new JSEncrypt();
            crypt.setKey($base64.decode(localStorage.getItem('pkey')));
            var inputParams = {
                amount: withdrawParams.amount,
                address: withdrawParams.address,
                bank_name: withdrawParams.bank_name,
                bank_address: withdrawParams.bank_address,
                account: withdrawParams.account,
                swift: withdrawParams.swift,
                instructions: withdrawParams.instructions,
                data: crypt.encrypt(JSON.stringify({code: withdrawParams.code}))
            };

            depositWithdrawService.getWithdrawWire(inputParams, function successBlock(data) {
                console.log("this is the getWithdrawWire call" + data);
                withdrawToast(data.code);
            }, function failureBlock(error) {
                console.log(error);
                withdrawToast(error.data.code);
            });
        };

        // WITHDRAW - cheque
        vm.withdrawCheque = {
            amount: "",
            address: "",
            code: "",
        };
        vm.withdrawChequeDisplayInfo = {
            fee: "",
            net: "",
        }

        $scope.$watch(function () {
            return vm.withdrawCheque.amount;
        }, function (value) {
            vm.withdrawChequeDisplayInfo.fee = (vm.withdrawCheque.amount > 250 ? vm.withdrawCheque.amount * 0.02 : 5);
            vm.withdrawChequeDisplayInfo.net = vm.withdrawCheque.amount - vm.withdrawChequeDisplayInfo.fee;
        });

        function withdrawChequeSubmit(withdrawParams) {
            var crypt = new JSEncrypt();
            crypt.setKey($base64.decode(localStorage.getItem('pkey')));

            var inputParams = {
                amount: withdrawParams.amount,
                address: withdrawParams.address,
                data: crypt.encrypt(JSON.stringify({code: withdrawParams.code}))
            };

            depositWithdrawService.getWithdrawCheque(inputParams, function successBlock(data) {
                console.log("this is the getWithdrawCheque call" + data);
                withdrawToast(data.code);
            }, function failureBlock(error) {
                console.log(error);
                withdrawToast(error.data.code);
            });
        };

        function interacAccessCheck() {
            var inputParams = {test: "true"};
            depositWithdrawService.getInteracAllowed(inputParams, function successBlock(data) {
                vm.interacAccess = data.status;
            }, function failureBlock(error) {
                console.log(error);
            });
        }

        // WITHDRAW - voucher
        vm.expiryDateOptions = ['Never', 'One Day', 'Three Days', 'Seven Days', 'Thirty Days', 'Sixty Days'];
        vm.widthdrawVoucher = {
            amount: "",
            expiry: "",
            code: ""
        };
        function withdrawVoucherSubmit(withdrawParams) {
            console.log(withdrawParams);
            var crypt = new JSEncrypt();
            crypt.setKey($base64.decode(localStorage.getItem('pkey')));
            var inputParams = {
                amount: withdrawParams.amount,
                expiry: withdrawParams.expiry,
                data: crypt.encrypt(JSON.stringify({code: withdrawParams.code}))
            };
            //crypt.encrypt(JSON.stringify({code:  withdrawParams.code})
            //}
            depositWithdrawService.getWithdrawCoupon(inputParams, function successBlock(data) {
                console.log(data);
                withdrawVoucherToast(data.code);
            }, function failureBlock(error) {
                console.log(error);
                withdrawVoucherToast(error.data.code);
            });
        };

        $scope.cancel = function () {
            $mdDialog.cancel();
        };
        // getWithdrawBTC();
        // getWithdrawWire();
        // getWithdrawCheque();

    }]); // controller
