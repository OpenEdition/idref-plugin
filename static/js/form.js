var proxy;
var idAutorite = "";
var remoteClientExist = false;
var oFrame;
var idrefinit = false;

var serializer = {

    stringify: function(data) {
        var message = "";
        for (var key in data) {
            if (data.hasOwnProperty(key)) {
                message += key + "=" + escape(data[key]) + "&";
            }
        }
        return message.substring(0, message.length - 1);
    },

    parse: function(message) {
        var data = {};
        var d = message.split("&");
        var pair, key, value;
        for (var i = 0, len = d.length; i < len; i++) {
            pair = d[i];
            key = pair.substring(0, pair.indexOf("="));
            value = pair.substring(key.length + 1);
            data[key] = unescape(value);
        }
        return data;
    }
};

function envoiClient(index1, index1Value, person_id) {

    index1Value = index1Value.replace(/'/g, "\\\'");
    if (initClient() == 0) {};
    oFrame = document.getElementById("popupFrame");
    if (!idrefinit) {
        oFrame.contentWindow.postMessage(serializer.stringify({
            Init: "true"
        }), "*");
        idrefinit = false;
    }
    try {
        eval('oFrame.contentWindow.postMessage(serializer.stringify({Index1:\'' + index1 + '\',Index1Value:\'' + index1Value + '\',fromApp:\'Lodel\',AutoClick:\'true\',End:\'true\'}), "*"); ');
        window.from_person_id = person_id;
    } catch (e) {
        alert("oFrame.contentWindow Failed? " + e);
    }
}

function initClient() {
    if (remoteClientExist) {
        showPopWin("", screen.width * 0.89, screen.height * 0.74, null);
        return 0;
    }

    showPopWin("", screen.width * 0.89, screen.height * 0.74, null);
    remoteClientExist = true;
    if (document.addEventListener) {
        window.addEventListener("message", function(e) {
            traiteResultat(e);
        });
    } else {
        window.attachEvent('onmessage', function(e) {
            traiteResultat(e);
        });
    }
    return 0;
}

function traiteResultat(e) {
    var data = serializer.parse(e.data);
    e.preventDefault();
    if (data["g"] != null) {
        var field_id = "idref-" + window.from_person_id;
        var idref_status = "#idref-status-" + window.from_person_id;
        $(idref_status).html(translations['idref_not_saved']);
        $(idref_status).addClass("idref-not-saved");
        document.getElementById(field_id).value = data['b'];
        hidePopWin(null);
    }
}
