var serverList = [];

function updateDashboardStreams() {
    $.ajax('/plugins/plexstreams/ajax.php').done(function(streams){
        $('#plexstreams_count').html(streams.length);
        $('#retrieving_streams').remove();
        if (streams.length > 0) {
            $('.no_streams').remove();
            var currentDate = new Date();
            var lastUpdate = currentDate.getTime();
            streams.forEach(function(stream) {
                $container = $('#' + stream.id);
                if ($container.length === 0) {
                    $container = $('<tr style="display:table-row;" id="' + stream.id + '">' +
                        '<td width="40%" style="padding: 0px;"><p class="plexstream-title" title="' + stream.title + '">' + stream.title +  '</p></td>' +
                        '<td align="center" style="padding: 0px;text-align:center;"><i class="fa fa-' + stream.stateIcon + '" title="' + stream.state + '"></i></td>' +
                        '<td align="center" style="padding: 0px;"><p class="plexstream-user" title="' + stream.user + '">' + stream.user + '</td>' +
                        '<td align="center" style="padding: 0px;text-align:right;"><p class="plexstream-time"><span class="currentPositionHours">' + stream.currentPositionHours.toString().padStart(2, 0) + '</span>:<span class="currentPositionMinutes">' + stream.currentPositionMinutes.toString().padStart(2, 0) + '</span>:<span class="currentPositionSeconds">' + stream.currentPositionSeconds.toString().padStart(2, 0) +  '</span> / ' + stream.lengthDisplay + '</div></td>' +
                    '</tr>').appendTo('#plexstreams_streams');
                    var node = $container[0];
                    updateDuration(node, stream);
                } else {
                    var node = $container[0];
                    $cells = $container.find('td');
                    $($cells[1]).find('i').attr('class', 'fa fa-' + stream.stateIcon).attr('title', uCWord(stream.state));
                    updateDuration(node, stream);
                }
                $container.attr('updatedat', lastUpdate);
                node.prevState = stream.state;
            });
            $('#plexstreams_streams tr[updatedat]').each(function() {
                if ($(this).is('[updatedat]')) {
                    if ($(this).attr('updatedat') !== lastUpdate.toString()) {
                        if (this.timer) {
                            clearInterval(this.timer)
                        };
                        $(this).remove();
                    }
                }
            });
        } else {
            $('#plexstreams_streams').html('<tr class="no_streams"><td colspan="4" align="center" style="padding: 0 0 0 0;"><p style="text-align:center;font-style:italic;">' + _('There are currently no active streams') + '</p></td></tr>');
        }
    }).fail(function(jqXHR) {
        if (jqXHR.status == '500') {
            $('#plexstreams_streams').html('<tr><td colspan="4" align="center"><p style="text-align:center;font-style:italic;">' + _('Please make sure you have') + ' <a href="/Settings/PlexStreams">' + _('setup') + '</a> ' + _('the plugin first') + '</p></td></tr>');
        }
    });
}

function uCWord(str) {
    return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
}

function updateFullStreamInfo() {
    $.ajax('/plugins/plexstreams/ajax.php').done(function(streams){
        if (streams.length > 0) {
            var currentDate = new Date();
            var lastUpdate = currentDate.getTime();
            $streamHolder = $('#streams-container');
            if ($streamHolder.length === 0) {
                $('#no-streams').replaceWith('<div id="streams-container"><ul></ul>');
                $streamHolder = $('#streams-container ul');
            }
            streams.forEach(function(stream) {
                var node = $('#' + stream.id + '.stream-container')[0];
                console.log('Updating Stream ID:' + stream.id);
                $container = $(node);
                if ($container.length > 0) {
                    $status = $container.find('.status i');
                    $progressBar = $container.find('.progressBar');
                    $progressBar.css({
                        width: stream.percentPlayed + '%'
                    });
                    
                    $status.attr('class', 'fa fa-' + stream.stateIcon);
                    $status.attr('title', uCWord(stream.state));
                    var $details = $container.find('.details');
                    $details.find('.stream.value').html(uCWord(stream.streamDecision));
                    $details.find('.bandwidth.value').html(stream.bandwidth);
                    $details.find('.audio.value').html(uCWord(stream.streamInfo.audio['@attributes'].decision));
                    if (stream.streamInfo.video) {
                        $details.find('.video.value').html(uCWord(stream.streamInfo.video['@attributes'].decision));
                    }
                } else {
                    $container = $('<li class="stream-container" id="' + stream.id + '"><div class="stream-subcontainer"><div class="stream" style="background-image:url(' + stream.artUrl  + ');"><div class="blur"><div class="details"><ul class="detail-list"><li><div class="label">' + _('Length') + '</div><div class="value">' + stream.duration + '</div></li><li><div class="label">' + _('Stream') + '</div><div class="stream value">' + stream.streamDecision + '</div></li><li><div class="label">' + _('Location') + '</div><div class="value" title="' + stream.locationDisplay + '" style="pointer:default;">' + stream.locationDisplay + '</div></li><li><div class="label">' + _('Bandwidth') + '</div><div class="bandwidth value">' + stream.bandwidth + '</div></li><li><div class="label">' + _('Audio') + '</div><div class="audio value">' + stream.streamInfo.audio['@attributes'].decision + '</div></li><li>' +  (stream.streamInfo.video ? '<div class="label">' + _('Video') + '</div><div class="video value">' + stream.streamInfo.video['@attributes'].decision + '</div></li>' : '') + '</ul></div><div class="poster" style="background-image:url(' + stream.thumbUrl + ');"></div><div class="userIcon" title="' + stream.user + '" style="background-image:url(' + stream.userAvatar + ')"></div></div></div><div class="bottom-box"><div class="progressBar" duration="' + stream.duration + '" style="' + stream.percentPlayed + '%;"><div class="position"><span class="currentPositionHours">' + stream.currentPositionHours.toString().padStart(2, 0) + '</span>:<span class="currentPositionMinutes">' + stream.currentPositionMinutes.toString().padStart(2, 0) + '</span>:<span class="currentPositionSeconds">' + stream.currentPositionSeconds.toString().padStart(2, 0) + '</span>  / ' + stream.lengthDisplay + '</div></div><div class="title"><a href="#" onclick="openBox(\'/plugins/plexstreams/movieDetails.php?details=' + encodeURIComponent(stream.key) + '&host=' + encodeURIComponent(stream['@host'])  + '\',\'Details\',600,900); return false;">' + stream.title +'</a><div class="status"><i class="fa fa-' + stream.stateIcon + '" title="' + stream.status + '"></i></div></div></div></div></li>').appendTo($streamHolder);
                    node = $container[0];
                }
                updateDuration(node, stream);
                $container.attr('updatedat', lastUpdate);
                node.prevState = stream.state;
            });

            $('.stream-container[updatedat]').each(function() {
                if ($(this).is('[updatedat]')) {
                    if ($(this).attr('updatedat') !== lastUpdate.toString()) {
                        if (this.timer) {
                            clearInterval(this.timer)
                        };
                        $(this).remove();
                    }
                }
            });
        } else {
            if ($('#streams-container').length > 0) {
                $('#streams-container').replaceWith('<p style="text-align:center;font-style:italic;">' + _('There are currently no active streams') + '</p>');
            }
        }
    }).fail(function(jqXHR) {
        if (jqXHR.status == '500') {
            $('#plexstreams_streams').html('<tr><td colspan="4" align="center"><p style="text-align:center;font-style:italicl">' + _('Please make sure you have') + ' <a href="/Settings/PlexStreams">' + _('setup') + '</a> ' + _('the plugin first') + '</p></td></tr>');
        }
    });
}

function updateDuration(node, stream) {
    var $container = $(node);

    if (stream.duration) {
        $hours = $container.find('.currentPositionHours');
        $minutes = $container.find('.currentPositionMinutes');
        $seconds = $container.find('.currentPositionSeconds');
    }
    if (node.prevState && node.prevState !== stream.state) {
        if (stream.duration) {
            $hours.html(stream.currentPositionHours.toString().padStart(2, 0));
            $minutes.html(stream.currentPositionMinutes.toString().padStart(2, 0));
            $seconds.html(stream.currentPositionSeconds.toString().padStart(2, 0));
            if (stream.state === 'playing') {
                incrementTimer($hours, $minutes, $seconds);
            }
        }
    }
    if (stream.duration  && stream.state === 'playing' && !node.timer) {
        node.timer = setInterval(incrementTimer, 1000, $hours, $minutes, $seconds);
    } else if(stream.state !== 'playing') {
        if (node.timer) {
            clearInterval(node.timer);
            node.timer = undefined;
        }
        $hours.html(stream.currentPositionHours.toString().padStart(2, 0));
        $minutes.html(stream.currentPositionMinutes.toString().padStart(2, 0));
        $seconds.html(stream.currentPositionSeconds.toString().padStart(2, 0));
    }
}

function incrementTimer($hours, $minutes, $seconds) {
    var seconds = parseInt($seconds.html(), 10);
    var minutes = parseInt($minutes.html(), 10);
    var hours = parseInt($hours.html());
    seconds += 1;
    if (seconds > 59) {
        seconds = 0;
        minutes += 1;
    }
    if (minutes > 59) {
        minutes = 0;
        hours += 1;
    }
    $seconds.html(seconds.toString().padStart(2, 0));
    $minutes.html(minutes.toString().padStart(2, 0));
    $hours.html(hours.toString().padStart(2, 0));
}

function updateServerList(dest) {
    var list = [];
    $.each($("input[name='hostbox']:checked"), function(){
        list.push($(this).val());
    });
    $('#' + dest).val(list.join(','));
}

function getServers(containerSelector, selected) {
    var url = '/plugins/plexstreams/getServers.php?useSsl=' + $('input[name="FORCE_PLEX_HTTPS"]:checked').val();
    var $host = $(containerSelector);
    $host.hide();
    $('.lds-dual-ring').show();
    selected = selected.split(',');
    $host.html('');
    $.get(url).done(function(data) {
        serverList = data.serverList;
        if (Object.keys(serverList).length > 0) {
            for (var id in serverList) {
                if (serverList.hasOwnProperty(id)) {
                    var server = serverList[id];
                    serverList[id].Connections.forEach(function(connection) {
                        if (connection !== null) {
                            $host.append('<input type="checkbox" onchange="updateServerList(\'HOST\')" name="hostbox" id="' + connection.uri + '" data-id="' + id + '"' + (selected.indexOf(connection.uri) > -1 ? ' checked="checked"' : '' ) + ' value="' + connection.uri + '"/> <label for="' + connection.uri + '"> ' + server.Name + ' (' +  connection.address + ':' + connection.port + ')' + (connection.local === '0' ? ' - Remote' : '') + '</label><br/>');
                        }
                    });
                }
            }
        } else {
            $host.html('<p>No Servers found, please enter server in Custom Servers Field');
        }
        $host.show();
        $('.lds-dual-ring').hide();
    });
}

function setLocalStorage(key, value, path) {
    if (path !== false) {
        key = key + '_' + window.location.pathname;
    }
    localStorage.setItem(key, value);
}
function getLocalStorage(key, default_value, path) {
    if (path !== false) {
        key = key + '_' + window.location.pathname;
    }
    var value = localStorage.getItem(key);
    if (value !== null) {
        return value
    } else if (default_value !== undefined) {
        setLocalStorage(key, default_value, path);
        return default_value
    }
}

function PopupCenter(url, title, w, h) {
    // Fixes dual-screen position                         Most browsers      Firefox
    var dualScreenLeft = window.screenLeft != undefined ? window.screenLeft : window.screenX;
    var dualScreenTop = window.screenTop != undefined ? window.screenTop : window.screenY;

    var width = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
    var height = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;

    var left = ((width / 2) - (w / 2)) + dualScreenLeft;
    var top = ((height / 2) - (h / 2)) + dualScreenTop;
    var newWindow = window.open(url, title, 'scrollbars=yes, width=' + w + ', height=' + h + ', top=' + top + ', left=' + left);

    // Puts focus on the newWindow
    if (window.focus) {
        newWindow.focus();
    }

    return newWindow;
}
var plex_oauth_loader = '<style>' +
        '.login-loader-container {' +
            'font-family: "Open Sans", Arial, sans-serif;' +
            'position: absolute;' +
            'top: 0;' +
            'right: 0;' +
            'bottom: 0;' +
            'left: 0;' +
        '}' +
        '.login-loader-message {' +
            'color: #282A2D;' +
            'text-align: center;' +
            'position: absolute;' +
            'left: 50%;' +
            'top: 25%;' +
            'transform: translate(-50%, -50%);' +
        '}' +
        '.login-loader {' +
            'border: 5px solid #ccc;' +
            '-webkit-animation: spin 1s linear infinite;' +
            'animation: spin 1s linear infinite;' +
            'border-top: 5px solid #282A2D;' +
            'border-radius: 50%;' +
            'width: 50px;' +
            'height: 50px;' +
            'position: relative;' +
            'left: calc(50% - 25px);' +
        '}' +
        '@keyframes spin {' +
            '0% { transform: rotate(0deg); }' +
            '100% { transform: rotate(360deg); }' +
        '}' +
    '</style>' +
    '<div class="login-loader-container">' +
        '<div class="login-loader-message">' +
            '<div class="login-loader"></div>' +
            '<br>' +
            'Redirecting to the Plex login page...' +
        '</div>' +
    '</div>';
var plex_oauth_window = null;
function closePlexOAuthWindow() {
    if (plex_oauth_window) {
        plex_oauth_window.close();
    }
}

function uuidv4() {
    return ([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g, function(c) {
        var cryptoObj = window.crypto || window.msCrypto; // for IE 11
        return (c ^ cryptoObj.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)
    });
}

function getPlexHeaders() {
    return {
        'Accept': 'application/json',
        'X-Plex-Product': 'Unraid Plex Streams Plugin',
        'X-Plex-Version': PLUGIN_VERSION,
        'X-Plex-Client-Identifier': getLocalStorage('UnraidPlexStreams_ClientID', uuidv4(), false),
        'X-Plex-Platform': 'unraid',
        'X-Plex-Platform-Version': OS_VERSION,
        'X-Plex-Model': 'Plex OAuth',
        'X-Plex-Device': OS_VERSION,
        'X-Plex-Device-Name': 'Unraid Plex Streams Plugin',
        'X-Plex-Device-Screen-Resolution': window.screen.width + 'x' + window.screen.height,
        'X-Plex-Language': 'en'
    };
}

getPlexOAuthPin = function () {
    var x_plex_headers = getPlexHeaders();
    var deferred = $.Deferred();

    $.ajax({
        url: 'https://plex.tv/api/v2/pins?strong=true',
        type: 'POST',
        headers: x_plex_headers,
        success: function(data) {
            deferred.resolve({pin: data.id, code: data.code});
        },
        error: function() {
            closePlexOAuthWindow();
            deferred.reject();
        }
    });
    return deferred;
};

var polling = null;

function encodeData(data) {
    return Object.keys(data).map(function(key) {
        return [key, data[key]].map(encodeURIComponent).join("=");
    }).join("&");
}

function PlexOAuth(success, error, pre) {
    if (typeof pre === "function") {
        pre()
    }
    closePlexOAuthWindow();
    plex_oauth_window = PopupCenter('', 'Plex-OAuth', 600, 700);
    $(plex_oauth_window.document.body).html(plex_oauth_loader);

    getPlexOAuthPin().then(function (data) {
        var x_plex_headers = getPlexHeaders();
        const pin = data.pin;
        const code = data.code;

        var oauth_params = {
            'clientID': x_plex_headers['X-Plex-Client-Identifier'],
            'context[device][product]': x_plex_headers['X-Plex-Product'],
            'context[device][version]': x_plex_headers['X-Plex-Version'],
            'context[device][platform]': x_plex_headers['X-Plex-Platform'],
            'context[device][platformVersion]': x_plex_headers['X-Plex-Platform-Version'],
            'context[device][device]': x_plex_headers['X-Plex-Device'],
            'context[device][deviceName]': x_plex_headers['X-Plex-Device-Name'],
            'context[device][model]': x_plex_headers['X-Plex-Model'],
            'context[device][screenResolution]': x_plex_headers['X-Plex-Device-Screen-Resolution'],
            'context[device][layout]': 'desktop',
            'code': code
        }

        plex_oauth_window.location = 'https://app.plex.tv/auth/#!?' + encodeData(oauth_params);
        polling = pin;

        (function poll() {
            $.ajax({
                url: 'https://plex.tv/api/v2/pins/' + pin,
                type: 'GET',
                headers: x_plex_headers,
                success: function (data) {
                    if (data.authToken){
                        closePlexOAuthWindow();
                        getServers('#hostcontainer', $('#HOST').val());
                        if (typeof success === "function") {
                            success(data.authToken)
                        }
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    if (textStatus !== "timeout") {
                        closePlexOAuthWindow();
                        if (typeof error === "function") {
                            error()
                        }
                    }
                },
                complete: function () {
                    if (!plex_oauth_window.closed && polling === pin){
                        setTimeout(function() {poll()}, 1000);
                    }
                },
                timeout: 10000
            });
        })();
    }, function () {
        closePlexOAuthWindow();
        if (typeof error === "function") {
            error()
        }
    });
}