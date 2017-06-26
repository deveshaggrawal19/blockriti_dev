'use strict';

angular.module('taurus.verificationModule')
    .controller('VerificationCtrl', ["$state", "$mdExpansionPanel", "verificationService", "$scope", "toastMessagesService", "Upload", "$timeout", "$http",
      function($state, $mdExpansionPanel, verificationService, $scope, toastMessagesService, Upload, $timeout, $http) {
      
      'use strict';

      var vm                    = this;

      vm.checkVerifiedStatus    = checkVerifiedStatus;
      // vm.clearOtherInput        = clearOtherInput;
      vm.getVerification        = getVerification;
      vm.goToTrade              = goToTrade;
      vm.showUploadSuccess      = showUploadSuccess;
      vm.submitVerificationOne  = submitVerificationOne;
      vm.submitVerificationTwo  = submitVerificationTwo;

      vm.minDob;
      vm.verificationOneForm;
      vm.photoForm;
      vm.billUtilityForm;
      vm.verificationTwoForm;
      vm.getVerificationData;
      vm.verificationLevelTwo = false;
      vm.verificationOneProgressBar = true;
      vm.verificationTwoProgressBar = true;
      vm.triggerFileBrowser = triggerFileBrowser;

      // Max birth date (min age) to register for taurus
      var today = new Date();
      vm.maxDob = new Date(today.getFullYear() - 18, today.getMonth(), today.getDate());
 
     vm.verificationOne = {
        firstName:    "",
        lastName:     "",
        dob:          "",
        address:      "",
        city:         "",
        province:     "",
        country:      "",
        postalCode:   "",
        photoId:      "",
        utilityBill:  "",
        phone:        "",
        occupation:   "",
     };

     vm.allowedCountries = [ 
      'Afghanistan',
      'Åland Islands',
      'Albania',
      'Algeria',
      'Andorra',
      'Angola',
      'Anguilla',
      'Antarctica',
      'Antigua and Barbuda',
      'Argentina',
      'Armenia',
      'Aruba',
      'Australia',
      'Austria',
      'Azerbaijan',
      'Bahamas (the)',
      'Bahrain',
      'Bangladesh',
      'Barbados',
      'Belarus',
      'Belgium',
      'Belize',
      'Benin',
      'Bermuda',
      'Bhutan',
      'Bolivia (Plurinational State of)',
      'Bonaire, Sint Eustatius and Saba',
      'Bosnia and Herzegovina',
      'Botswana',
      'Bouvet Island',
      'Brazil',
      'British Indian Ocean Territory (the)',
      'Brunei Darussalam',
      'Bulgaria',
      'Burkina Faso',
      'Burundi',
      'Cabo Verde',
      'Cambodia',
      'Cameroon',
      'Canada',
      'Cayman Islands (the)',
      'Central African Republic (the)',
      'Chad',
      'Chile',
      'China',
      'Christmas Island',
      'Cocos (Keeling) Islands (the)',
      'Colombia',
      'Comoros (the)',
      'Congo (the)',
      'Congo (the Democratic Republic of the)',
      'Cook Islands (the)',
      'Costa Rica',
      'Côte d\'Ivoire',
      'Croatia',
      'Cuba',
      'Curaçao',
      'Cyprus',
      'Czech Republic (the)',
      'Denmark',
      'Djibouti',
      'Dominica',
      'Dominican Republic (the)',
      'Ecuador',
      'Egypt',
      'El Salvador',
      'Equatorial Guinea',
      'Eritrea',
      'Estonia',
      'Ethiopia',
      'Falkland Islands (the) [Malvinas]',
      'Faroe Islands (the)',
      'Fiji',
      'Finland',
      'France',
      'French Guiana',
      'French Polynesia',
      'French Southern Territories (the)',
      'Gabon',
      'Gambia (the)',
      'Georgia',
      'Ghana',
      'Gibraltar',
      'Greece',
      'Greenland',
      'Grenada',
      'Guadeloupe',
      'Guatemala',
      'Guernsey',
      'Guinea',
      'Guinea-Bissau',
      'Guyana',
      'Haiti',
      'Heard Island and McDonald Islands',
      'Holy See (the)',
      'Honduras',
      'Hong Kong',
      'Hungary',
      'Iceland',
      'India',
      'Indonesia',
      'Ireland',
      'Isle of Man',
      'Israel',
      'Italy',
      'Jamaica',
      'Japan',
      'Jersey',
      'Jordan',
      'Kazakhstan',
      'Kenya',
      'Kiribati',
      'Korea (the Democratic People\'s Republic of)',
      'Korea (the Republic of)',
      'Kuwait',
      'Kyrgyzstan',
      'Lao People\'s Democratic Republic (the)',
      'Latvia',
      'Lebanon',
      'Lesotho',
      'Liberia',
      'Libya',
      'Liechtenstein',
      'Lithuania',
      'Luxembourg',
      'Macao',
      'Macedonia (the former Yugoslav Republic of)',
      'Madagascar',
      'Malawi',
      'Malaysia',
      'Maldives',
      'Mali',
      'Malta',
      'Marshall Islands (the)',
      'Martinique',
      'Mauritania',
      'Mauritius',
      'Mayotte',
      'Mexico',
      'Micronesia (Federated States of)',
      'Moldova (the Republic of)',
      'Monaco',
      'Mongolia',
      'Montenegro',
      'Montserrat',
      'Morocco',
      'Mozambique',
      'Myanmar',
      'Namibia',
      'Nauru',
      'Nepal',
      'Netherlands (the)',
      'New Caledonia',
      'New Zealand',
      'Nicaragua',
      'Niger (the)',
      'Nigeria',
      'Niue',
      'Norfolk Island',
      'Norway',
      'Oman',
      'Pakistan',
      'Palau',
      'Palestine, State of',
      'Panama',
      'Papua New Guinea',
      'Paraguay',
      'Peru',
      'Philippines (the)',
      'Pitcairn',
      'Poland',
      'Portugal',
      'Qatar',
      'Réunion',
      'Romania',
      'Russian Federation (the)',
      'Rwanda',
      'Saint Barthélemy',
      'Saint Helena, Ascension and Tristan da Cunha',
      'Saint Kitts and Nevis',
      'Saint Lucia',
      'Saint Martin (French part)',
      'Saint Pierre and Miquelon',
      'Saint Vincent and the Grenadines',
      'Samoa',
      'San Marino',
      'Sao Tome and Principe',
      'Saudi Arabia',
      'Senegal',
      'Serbia',
      'Seychelles',
      'Sierra Leone',
      'Singapore',
      'Sint Maarten (Dutch part)',
      'Slovakia',
      'Slovenia',
      'Solomon Islands',
      'Somalia',
      'South Africa',
      'South Georgia and the South Sandwich Islands',
      'South Sudan ',
      'Spain',
      'Sri Lanka',
      'Sudan (the)',
      'Suriname',
      'Svalbard and Jan Mayen',
      'Swaziland',
      'Sweden',
      'Switzerland',
      'Taiwan (Province of China)',
      'Tajikistan',
      'Tanzania, United Republic of',
      'Thailand',
      'Timor-Leste',
      'Togo',
      'Tokelau',
      'Tonga',
      'Trinidad and Tobago',
      'Tunisia',
      'Turkey',
      'Turkmenistan',
      'Turks and Caicos Islands (the)',
      'Tuvalu',
      'Uganda',
      'Ukraine',
      'United Arab Emirates (the)',
      'United Kingdom of Great Britain and Northern Ireland (the)',
      'Uruguay',
      'Uzbekistan',
      'Vanuatu',
      'Venezuela (Bolivarian Republic of)',
      'Vietnam',
      'Virgin Islands (British)',
      'Wallis and Futuna',
      'Western Sahara',
      'Yemen',
      'Zambia',
      'Zimbabwe' 
      ];

      function triggerFileBrowser(inputId) {
        $('#' + inputId).trigger('click');
      }

     vm.verificationTwo = {
        verificationTwoDocument: ""
     };

     function goToTrade() {
        $state.go('trade');
     }

     vm.validateVerificationForm = validateVerificationForm;
     function validateVerificationForm(data) {
        console.log(data);
        console.log(data.$valid);
        return data.$valid;
     }


     // If the form input error object is empty (file input is valid)
     // it will show a green checkmark. Otherwise hides the checkmark
     function showUploadSuccess(formInputErrorObject, elementIdToAdjust) {
      if (jQuery.isEmptyObject(formInputErrorObject)) {
        $('#' + elementIdToAdjust).parent().children('md-icon').css('opacity', '1');
        // $('#utility-bill-input').parent().children().first().css('opacity', '1');
      } else {
        $('#' + elementIdToAdjust).parent().children('md-icon').css('opacity', '0');
      }
     }

     // VerificationTwo has optional input: Void Cheque or Bank Letter/Statement.
     // When either upload button is clicked, this function clears the model and hides the checkmark, if visible
     // So that both uploads are not used
     // function clearOtherInput() {
     //  vm.verificationTwo = {
     //    bankLetter: "",
     //    voidCheque: ""
     //  };
     //  $('#bank-letter-btn').parent().children('md-icon').css('opacity', '0');
     //  $('#void-cheque-btn').parent().children('md-icon').css('opacity', '0');
     // }


		// function trade(data) {
		// 	  $state.go(data);
		// };

    var verificationFailureToast = function(code) {
      switch (code) {
       case 1161:
         toastMessagesService.failureToast('First name missing');
         break;
       case 1162:
         toastMessagesService.failureToast('Last name missing');
         break;
       case 1163:
         toastMessagesService.failureToast('Date of birth missing');
         break;       
        case 1164:
         toastMessagesService.failureToast('Address Missing');
         break;
        case 1165:
         toastMessagesService.failureToast('City Missing');
         break;
        case 1166:
         toastMessagesService.failureToast('Province/state Missing');
         break;
        case 1167:
         toastMessagesService.failureToast('Country Missing');
         break;
        case 1168:
         toastMessagesService.failureToast('Postal code/ZIP Missing');
         break;
        case 1169:
         toastMessagesService.failureToast('Occupation Missing');
         break;
        case 1171:
         toastMessagesService.failureToast('Phone Missing');
         break;
       default:
         console.log(code);
         toastMessagesService.failureToast('Verification error');
      };
    }


		/*******************Verification Level 1***********************/
		function getVerification() {
        var data = {'test': 'true'};
        verificationService.getVerification(data,function successBlock(data) {
            vm.getVerificationData = data;
            // console.log(data.verification_level)
            if(data.verification_level == 1) {
            };
        }, function failureBlock(error) {
                console.log(error);
		        }
        );
		}


    vm.getLevelOneDetails = getLevelOneDetails;

    function getLevelOneDetails() {
      verificationService.getLevelOneDetails(function successBlock(data) {
        console.log(data);
        vm.verificationOne = {
        firstName:    data.firstName,
        lastName:     data.lastName,
        dob:          new Date(data.birthDate),
        address:      data.address,
        city:         data.city,
        province:     data.state,
        country:      data.country,
        postalCode:   data.zip,
        phone:        data.phone,
        occupation:   data.occupation,
     };
      }, function failureBlock(error) {
          console.log(error);
      });
    }


    $(document).ready(function () {
      getVerification(); 
    });
      getLevelOneDetails();

    function checkVerifiedStatus() {
      if(vm.getVerificationData.verification_level == 1) {
        vm.verificationLevelTwo = true;
      } else {
        vm.verificationLevelTwo = true;
        //toastMessagesService.failureToast('You must be an approved, verified level one user before you can access the verification level two form.');
      }
    }


/************************************* Upload + Submit ********************************/
  var verificationOneUploadSuccessCount = 0;
  var verificationTwoUploadSuccessCount = 0;
  var verificationUploadSuccess = function () {
    if (verificationOneUploadSuccessCount === 2) {
      toastMessagesService.successToast('Verification Level One data and files submitted successfully');
      vm.verificationOneProgressBar = true;
    } else if (verificationTwoUploadSuccessCount === 1) {
      toastMessagesService.successToast('Verification Level Two submitted successfully');
      vm.verificationTwoProgressBar = true;
    };
  };

  var uploadSingleFile = function (formId, inputId, verificationTwo) {

    $('#' + inputId).attr('name', 'uploaded_files');
    var formData = new FormData($('form#'+formId)[0]);
    var action = $('form#'+formId).attr("action");

    $.ajax({
        url: action,
        type: 'POST',
        data: formData,
        // async: false,
        success: function (data) {
          console.log(data);
          verificationOneUploadSuccessCount++;
          verificationTwo ? verificationTwoUploadSuccessCount++ : "";
          verificationUploadSuccess();
        },
        error: function (error) {
          console.log(error);
          toastMessagesService.failureToast('Verification upload failure');
          vm.verificationOneProgressBar = true;
          vm.verificationTwoProgressBar = true;
        },
        cache: false,
        contentType: false,
        processData: false
    });
  }

   function submitVerificationOne(verificationOneData) {
      console.log(verificationOneData);
      verificationOneUploadSuccessCount = 0;
      verificationTwoUploadSuccessCount = 0;

      vm.verificationOneForm.$setSubmitted();
      vm.photoForm.photoId.$dirty = true;
      vm.billUtilityForm.utilityBill.$dirty = true;
      if (vm.verificationOneForm.$valid && vm.photoForm.$valid && vm.billUtilityForm.$valid) {
        vm.verificationOneProgressBar = false;
      	console.log(verificationOneData);
        var verificationOneSubmitData = {
          first_name: verificationOneData.firstName,
          last_name: verificationOneData.lastName,
          dob: verificationOneData.dob,
          address: verificationOneData.address,
          city: verificationOneData.city,
          state: verificationOneData.province,
          country: verificationOneData.country,
          zip: verificationOneData.postalCode,
          occupation: verificationOneData.occupation,
          phone: verificationOneData.phone,
        };
        verificationService.verification_first(verificationOneSubmitData, function successBlock(data) {
          console.log('success');
          // toastMessagesService.successToast('Verification information submitted.');
          if (data.code == 1180) {
            console.log(data);

            $("form#photoForm").attr("action", data.photo_upload_url)
            uploadSingleFile("photoForm", "photo-id-input");

            $("form#billUtilityForm").attr("action", data.utility_bill_url);
            uploadSingleFile("billUtilityForm", "utility-bill-input");

          };
          // vm.verificationOneProgressBar = true;
        }, function failureBlock(error) {
        	 console.log(error);
           verificationFailureToast(error.data.code);
           vm.verificationOneProgressBar = true;
    	  });
      };
    }

  function submitVerificationTwo(verificationTwoData) {
    verificationOneUploadSuccessCount = 0;
    verificationTwoUploadSuccessCount = 0;
    console.log(verificationTwoData);
    vm.verificationTwoForm.verificationTwoDocument.$dirty = true;
    vm.verificationTwoProgressBar = false;
    var dataToSend = {'test': 'true'};
    verificationService.getVerification(dataToSend, function successBlock(data) {
      // vm.getVerificationData = data;
      console.log(data);
      // console.log("level: " + data.verification_level);
      // console.log("bank_letter_upload:" + data.bank_letter_upload);
      // if(data.verification_level == 1) {
        $("form#verificationTwoForm").attr("action", data.bank_letter_upload);
        uploadSingleFile("verificationTwoForm", "verification-two-document-input", true);
      // };
      // vm.verificationTwoProgressBar = true;
    }, function failureBlock(error) {
      console.log(error);
      vm.verificationTwoProgressBar = true;
    })
/*
    verificationService.getVerification
      if (verificationTwoData.bankLetter) {

        $("form#verificationTwoForm").attr("action",data.bank_letter_upload);

        console.log("Bank letter");
      dataToSend = verificationTwoData.bankLetter 
    } else if (verificationTwoData.voidCheque) {
        console.log("Cheque");
      dataToSend = verificationTwoData.voidCheque;
    };
    if (vm.getVerificationData.verification_level == 0 || vm.getVerificationData.verification_level == 1) {

        uploadSingleFile(dataToSend, vm.getVerificationData.bank_letter_upload);
    };
*/

  };


     //************************************************************* 







     $scope.init = function() {
          $scope.user_id = localStorage.getItem('client');
          var verification_level = localStorage.getItem('verification_level');
    		  if(verification_level == 0) {
    			    $mdExpansionPanel().waitFor('verificationFirst').then(function (instance) {
              instance.expand();
              });
    		  } else {
        			if(verification_level==1) {
            			$mdExpansionPanel().waitFor('verificationSecond').then(function (instance) {
                      instance.expand();
                  });
              vm.getVerification();
    		      } else {
    				         if(verification_level>2){
                				$('.trade1').show();
                				$('.trade2').show();
    	                }else{
							$('.rightIcon1').show();
							$('.rightIcon2').show();
						}
               }
         };

      };

      $scope.init();


//bank account

 $scope.choices = [{id: 'choice1'}];
  
  $scope.addNewChoice = function() {
    var newItemNo = $scope.choices.length+1;
    $scope.choices.push({'id':'choice'+newItemNo});
  };
    
  $scope.removeChoice = function() {
    var lastItem = $scope.choices.length-1;
    $scope.choices.splice(lastItem);
  };


















  }]); //controller
