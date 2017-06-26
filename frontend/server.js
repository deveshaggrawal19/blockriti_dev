var express = require('express');
var cluster = require('cluster');
var compression = require('compression')

if (cluster.isMaster) {
    var numWorkers = require('os').cpus().length;

    console.log('Master cluster setting up ' + numWorkers + ' workers...');

    for (var i = 0; i < numWorkers; i++) {
        cluster.fork();
    }

    cluster.on('online', function(worker) {
        console.log('Worker ' + worker.process.pid + ' is online');
    });

    cluster.on('exit', function(worker, code, signal) {
        console.log('Worker ' + worker.process.pid + ' died with code: ' + code + ', and signal: ' + signal);
        console.log('Starting a new worker');
        cluster.fork();
    });
} else {
     var app = express();

    app.use(compression());
    app.use(express.static('app'));

    app.use('/libs', express.static('libs'));
   
    var server = app.listen(8000, function() {
        console.log("app listening on " + server.address().port);
    });
}