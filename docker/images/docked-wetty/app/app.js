'use strict';

var express = require('express');
var http = require('http');
var https = require('https');
var path = require('path');
var server = require('socket.io');
var pty = require('pty.js');
var fs = require('fs');
var atob = require('atob');

var opts = require('optimist')
    .options({
        sslkey: {
            demand: false,
            description: 'path to SSL key'
        },
        sslcert: {
            demand: false,
            description: 'path to SSL certificate'
        },
        sshhost: {
            demand: false,
            description: 'ssh server host'
        },
        sshport: {
            demand: false,
            description: 'ssh server port'
        },
        sshuser: {
            demand: false,
            description: 'ssh user'
        },
        sshauth: {
            demand: false,
            description: 'defaults to "password", you can use "publickey,password" instead'
        },
        port: {
            demand: true,
            alias: 'p',
            description: 'wetty listen port'
        },
        whitelist: {
            demand: false,
            description: 'whitelist of usernames/hosts you can connect to. Given as comma separated list of the form "^.*@localhost$,^user@hostname$".'
        }
    }).boolean('allow_discovery').argv;

var sshhost = 'localhost';
var sshport = 22;
var sshuser = '';
var sshpass = '';
var sshauth = 'password';

if (opts.sshport) {
    sshport = opts.sshport;
}

if (opts.sshhost) {
    sshhost = opts.sshhost;
}

if (opts.sshauth) {
    sshauth = opts.sshauth;
}

if (opts.sshuser) {
    sshuser = opts.sshuser;
}

if (opts.sslkey && opts.sslcert) {
    opts.ssl = {};
    opts.ssl.key = fs.readFileSync(path.resolve(opts.sslkey));
    opts.ssl.cert = fs.readFileSync(path.resolve(opts.sslcert));
}


process.on('uncaughtException', function(e) {
    console.error('Error: ' + e);
});

var httpserv;

var app = express();
app.get('/ssh/:code', function(req, res) {
    res.sendfile(__dirname + '/public/wetty/index.html');
});
// required to intercept /wetty/socket.io
app.use('/', express.static(path.join(__dirname, 'public')));
    httpserv = http.createServer(app).listen(opts.port, function() {
        console.log('http on port ' + opts.port);
    });

var io = server(httpserv,{path: '/wetty/socket.io'});

io.on('connection', function(socket){
    var request = socket.request;
    console.log((new Date()) + ' Connection accepted.');
    
    // check URL consistency and extract path
    var match = request.headers.referer.match('/ssh/.+$');
    if (match) {
        var code = match[0].split('/')[2];
        // decode base64 encoded data
        code = atob(code, 'ascii');
        // split user and server parts
        var parts = code.split('@');
        // check consistency
        if(parts.length < 2) return;
        // split user and password
        var usr_info = parts[0].split(':');
        // split server and port
        var srv_info = parts[1].split(':');
        // check consistency
        if(usr_info.length < 2) return;
        if(srv_info.length < 2) return;
        sshuser = usr_info[0];
        sshpass = usr_info[1];
        sshhost = srv_info[0];
        sshport = srv_info[1];


        var command = ['/usr/bin/ssh', sshuser + '@' + sshhost, '-p', sshport, '-o', 'PreferredAuthentications=' + sshauth, '-o', 'StrictHostKeyChecking=no'];
        command = ['/usr/bin/sshpass', '-p', sshpass].concat(command);

        var term = pty.spawn('/usr/bin/env', command, {
            name: 'xterm-256color',
            cols: 80,
            rows: 30
        });

        //console.log(command);
        console.log((new Date()) + " PID=" + term.pid + " STARTED on behalf of user " + sshuser + '@' + sshhost);

        term.on('data', function(data) {
            socket.emit('output', data);
        });
        term.on('exit', function(code) {
            console.log((new Date()) + " PID=" + term.pid + " ENDED with code "+code);
        });
        socket.on('resize', function(data) {
            if (!term.readable || !term.writable || term.destroyed) return;
            term.resize(data.col, data.row);
        });
        socket.on('input', function(data) {
            term.write(data);
        });
        socket.on('disconnect', function() {
            term.end();
        });        
    }
});

