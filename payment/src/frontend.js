var apg = function(options) {
    // CONFIG! Modify this!
    var apiBase = 'http://127.0.0.1:9909';
    var brokerageBase = 'http://1-dot-blockriti-163505.appspot.com';

    var sendJson = function(url, data, cb) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', apiBase + url);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.responseType = 'json';
        xhr.send(JSON.stringify(data));
        xhr.onload = function() {
            cb(xhr.response);
        }
    }

    // check required parameters
    if((![
        'elt',
        'apiPublic',
        'info',
        'jsCallback',
        'confirmedCallback',
    ].every(function (c) {
        return ~Object.keys(options).indexOf(c);
    })) || (!(options.amountSatoshis || options.amountInr))) {
        throw new Error('You must provide elt, apiPublic, info, jsCallback, confirmedCallback, and either amountSatoshis or amountInr as parameters to `apg`');
    }

    console.log(options.amountInr);
    if(options.amountInr) {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', brokerageBase + '/api/brokerage');
        xhr.responseType = 'json';
        xhr.send();
        xhr.onload = function() {
            options.amountSatoshis = options.amountInr / xhr.response.sell * 100000000;
            startMain();
        }
    } else {
        startMain();
    }

    function startMain() {
        sendJson('/startTransaction', {
            apiPublic: options.apiPublic,
            amount: options.amountSatoshis,
            info: options.info,
            confirmedCallback: options.confirmedCallback,
        }, function(startRes) {
            function poll() {
                sendJson('/getTransactionInfo', { txId: startRes.txId }, function(response) {
                    document.getElementById('apg-received').textContent = (response.paidAmount / 100000000).toFixed(8);
                    if(response.state === 'unconfirmed' || response.state === 'complete') {
                        options.jsCallback();
                    }
                });
            }
            // ahh this is terrible
            options.elt.innerHTML += '<img src="' + startRes.qr + '" style="float: left">';
            options.elt.innerHTML += '<br>';
            options.elt.innerHTML += 'Send <strong>' + (options.amountSatoshis / 100000000).toFixed(8) + ' BTC</strong> to <strong>' + startRes.address + '</strong>';
            options.elt.innerHTML += '<br>';
            options.elt.innerHTML += 'Received: <strong><span id="apg-received">0.00000000</span> BTC</strong>';
            options.elt.innerHTML += '<br>'
            options.elt.innerHTML += 'Or pay with prepaid card:'
            options.elt.innerHTML += '<br>';
            options.elt.innerHTML += '<input style="margin:10px;font-size:14pt" id="apg-card-num" placeholder="card number"><input style="margin:10px;font-size:14pt" id="apg-pin" type="number" placeholder="pin"><button style="font-size:14pt" id="apg-prepaid">Pay</button>';
            options.elt.style.cssText = 'height:200px;line-height:1.5rem;margin:10px;width:40vw;border:10px solid #bbb;';
            document.getElementById('apg-prepaid').addEventListener('click', function() {
                if(!confirm('Pay ' + options.amountSatoshis / 100000000 + ' BTC with prepaid card?')) {
                    return;
                }
                sendJson('/payWithPrepaid', {
                    txId: startRes.txId,
                    cardNum: document.getElementById('apg-card-num').value,
                    pin: document.getElementById('apg-pin').value,
                }, function(response) {
                    if(response.status === 'error') {
                        switch(response.message) {
                            case 'Invalid card/pin':
                                alert('Incorrect card number/pin');
                                break;
                            case 'Insufficient funds':
                                alert('Insufficient funds to pay with prepaid');
                                break;
                            default:
                                alert('Error paying with prepaid card: ' + response.message);
                        }
                    } else {
                        alert('Payment completed successfully');
                        poll();
                    }
                });
            });

            setInterval(poll, 5000);
        });
    };
}
