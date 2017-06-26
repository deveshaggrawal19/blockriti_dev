angular.module('taurus.homeModule')
    .factory('ShareEmailService', function () {

        var data = {
            email: ''
        };

        return {
            getEmail: function () {
                return data.email;
            },
            setEmail: function (newEmail) {
                data.email = newEmail;
            }
        };
    });