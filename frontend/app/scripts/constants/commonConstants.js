"use strict";

angular.module("taurus")
    .constant('commonConstants', {
        'typeaheadMinLength': 3,
        'typeaheadMinLength0': 0,
        'typeaheadEditable': false,
        'defaultPageNo': 1,
        'itemPerPage': 10,
        'gridConfigurations': {
            "searching": false,
            "lengthChange": false,
            "info": false,
            "responsive": true,
            'setDeafultColSortingOrder': 'desc'
        }

    });