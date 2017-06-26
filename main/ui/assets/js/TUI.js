TUI = function ($, T) {
    var ui = {
        socket: null,
        initSocket: function(callback) {
            ui.socket = io.connect({
                path: io_path
            });

            ui.socket.on('connect', callback);
        },
        numberWithCommas: function(amount) {
            var parts = amount.toString().split(".");
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            return parts.join(".");
        },
        updatePageTitle: function(lastCAD) {
            if (location.pathname == '/' || location.pathname.indexOf('/trade') == 0 || location.pathname.indexOf('/market') == 0) {
                lastCAD = lastCAD.replace(/[^0-9\.]/g, '');
                if (document.title.charAt(0) != '(')
                    document.title = '(' + lastCAD + ') ' + document.title;
                else document.title = document.title.replace(/\([0-9\.]+\)/g, '(' + lastCAD + ')');
            }
        },
        tidy: function (currency, amount) {
            amount = this.numberWithCommas(amount);
            switch (currency) {
                case 'cad':
                case 'usd':
                    return '$' + amount + '<span class="e">' + currency.toUpperCase() + '</span>';

                default:
                    return amount + '<span class="e">' + currency.toUpperCase() + '</span>';
            }
        },
        precision: function (currency) {
            switch (currency) {
                case 'btc':
                    return 8;

                default:
                    return 2;
            }
        },
        round: function(value, precision, mode) {
            var m, f, isHalf, sgn; // helper variables
            precision |= 0; // making sure precision is integer
            m = Math.pow(10, precision);
            value *= m;
            sgn = (value > 0) | -(value < 0); // sign of the number
            isHalf = value % 1 === 0.5 * sgn;
            f = Math.floor(value);

            if (isHalf) {
                switch (mode) {
                    case 'PHP_ROUND_HALF_DOWN':
                        value = f + (sgn < 0); // rounds .5 toward zero
                        break;
                    case 'PHP_ROUND_HALF_EVEN':
                        value = f + (f % 2 * sgn); // rouds .5 towards the next even integer
                        break;
                    case 'PHP_ROUND_HALF_ODD':
                        value = f + !(f % 2); // rounds .5 towards the next odd integer
                        break;
                    default:
                        value = f + (sgn > 0); // rounds .5 away from zero
                }
            }

            return (isHalf ? value : Math.round(value)) / m;
        },
        dynamic: {
            processed: false,
            subscribe: function(){
                if (ui.dynamic.processed) return;

                ui.dynamic.processed = true;

                $('.orders tbody, .open-orders tbody').on('click', 'a.cancel', function(e) {
                    e.preventDefault();
                    T.message.cancelOrder($(this).data('orderid'));
                });

                ui.socket.on('book_update', function (data) {
                    var book       = data.book,
                        dynamic    = $('._dynamic'),
                        currencies = book.split('_'),
                        major      = currencies[0],
                        minor      = currencies[1];

                    if (dynamic.length) {
                        var types = ['bids', 'asks', 'trades'],
                            max   = 10;

                        if ($('._orderbook').length)
                            max = 100;

                        T.message.panels(book, max, function (data) {
                            for (var t in types) {
                                var type  = types[t],
                                    table = $('._dynamic[data-type="' + type + '"]');

                                if (table.length) {
                                    var body   = table.find('tbody'),
                                        format = table.data('format');

                                    body.empty();

                                    for (var i in data[type]) {
                                        var order = data[type][i],
                                            row   = $('<tr>');

                                        if (format == 'full') {
                                            row.data('amount', order.amount);
                                            row.data('price', order.rate);
                                        }

                                        row.append('<td class="price">' + ui.tidy(minor, order.rate) + '</td>' +
                                            '<td class="amount">' + ui.tidy(major, order.amount) + '</td>' +
                                            '<td>' + ui.tidy(minor, order.value) + '</td>' +
                                            '</tr>');

                                        body.append(row);

                                        if (max && i > max)
                                            break;
                                    }
                                }
                            }

                            // Get the last trade price for that currency
                            var lastCAD = data['trades'][0].rate;
                            $('.last-' + minor).html(ui.tidy(minor, lastCAD));
                            ui.updatePageTitle(lastCAD);
                        });
                    }

                    // Update the market stats from the homepage if needed
                    if ($('.overview').length)
                        $('.overview').load('/marketstats/' + book);
                });

                ui.socket.on('user_update', function (data) {
                    var book       = data.book,
                        currencies = book.split('_'),
                        major      = currencies[0],
                        minor      = currencies[1];

                        var types = ['orders', 'trades'],
                            max   = 10;

                    T.message.userpanels(max, function (data) {
                        for (var t in types) {
                            var type  = types[t],
                                table = $('._user_dynamic[data-type="' + type + '"]');

                            if (table.length) {
                                var body   = table.find('tbody'),
                                    format = table.data('format');

                                body.empty();

                                for (var i in data[type]) {
                                    var order = data[type][i],
                                        row   = $('<tr>');

                                    if (format == 'full') {
                                        row.data('amount', order.amount);
                                        row.data('price', order.rate);
                                    }

                                    if (type == 'orders') {
                                        if (table.hasClass('open-orders')) {
                                            var book = order.book.toUpperCase();
                                            row.append('<td class="' + (order.type == 'buy' ? 'green' : 'red') + '">' + order.type.toUpperCase() + '</td>' +
                                                '<td>' + ui.tidy(minor, order.rate) + '</td>' +
                                                '<td>' + ui.tidy(major, order.amount) + '</td>' +
                                                '<td>' + ui.tidy(minor, order.value) + '</td>' +
                                                '<td>' + order.datetime + '</td>' +
                                                '<td><a href="#" class="cancel" data-orderid="' + order.id + '"><i class="fa fa-times"></i></a></td>' +
                                                '</tr>');
                                        }
                                        else {
                                            row.append('<td class="' + (order.type == 'buy' ? 'green' : 'red') + '">' + order.type.toUpperCase() + '</td>' +
                                                '<td>' + ui.tidy(major, order.amount) + '</td>' +
                                                '<td>' + ui.tidy(minor, order.value) + '</td>' +
                                                '<td><a href="#" class="cancel" data-orderid="' + order.id + '"><i class="fa fa-times"></i></a></td>' +
                                                '</tr>');
                                        }
                                    }
                                    else {
                                        row.append('<td class="price">' + ui.tidy(minor, order.rate) + '</td>' +
                                            '<td class="amount">' + ui.tidy(major, order.amount) + '</td>' +
                                            '<td>' + ui.tidy(minor, order.value) + '</td>' +
                                            '</tr>');
                                    }

                                    body.append(row);

                                    if (max && i > max)
                                        break;
                                }
                            }
                        }

                        // Do the balance update here
                        for (currency in data.balances)
                            $('._user_dynamic._balance_' + currency).html(ui.tidy(currency, data.balances[currency]));
                    });
                });

                ui.socket.on('notification', function(data){
                    // Send the message
                    noty({
                        text: data.message,
                        layout: 'bottomRight',
                        timeout: data.user_id ? 10000 : false,
                        type: data.type
                    });
                });
            }
        }
    };

    ui.initSocket(function(){
        if (window.user_id != 'guest')
            ui.socket.emit('subscribe_user', { userId: window.user_id });

        ui.dynamic.subscribe();
    });

    return ui;
}(jQuery, T);

$(document).ready(function(){
    $('#country').on('change', function(){
        $.get('/main/state/' + $(this).val(), {
            dataType: 'json',
            cache: false
        },function(d) {
            $('#state').empty();
            if (!$.isEmptyObject(d)) {
                for (var e in d)
                    $('#state').append('<option value="' + e + '">' + d[e] + '</option>');
            }
            else $('#state').append('<option value="">not applicable</option>');
        })
    });
});