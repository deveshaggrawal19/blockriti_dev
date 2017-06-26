angular.module('taurus').factory('tSocket', function (socketFactory) {

     var tIoSocket = io.connect('https://104.155.54.175:80', {
            //var tIoSocket = io.connect('https://node.tek.moe:80/', {
                query: {
                    "AUTH_USER": localStorage.getItem('client'),
                    "AUTH_TOKEN": localStorage.getItem('taurus_token')
                }
                });

                tSocket = socketFactory({
                            ioSocket: tIoSocket
                            });

                return tSocket;
});
        //     subscribe: function(){
        //         console.log("subFunction");
        //         if (ui.dynamic.processed) return;
        //
        //         ui.dynamic.processed = true;
        //
        //
        //         ui.socket.on('book_update', function (data) {
        //             console.log("thisisthe" + data);
        //             scope.init();
        //         });
        //
        //
        //
        //         ui.socket.on('user_update', function (data) {
        //             console.log("user" + data);
        //             scope.init();
        //
        //         });
        //
        //         ui.socket.on('notification', function (data) {
        //             console.log('notifica' + data);
        //             scope.init();
        //         });
        //
        //     }
        // }
        //
        //
        //
        //

