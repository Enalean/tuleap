var GetStatusList = new Array();
var GetSaveStatusList = new Array();

function getStatuses(id)
{
    GetStatusList[GetStatusList.length] = id;
}

function doGetStatuses(strings)
{
    // Do nothing if no statuses were requested:
    if (GetStatusList.length < 1) {
        return;
    }

    var now = new Date();
    var ts = Date.UTC(now.getFullYear(),now.getMonth(),now.getDay(),now.getHours(),now.getMinutes(),now.getSeconds(),now.getMilliseconds());

    var url = path + "/Search/AJAX?method=GetItemStatuses";
    for (var i=0; i<GetStatusList.length; i++) {
       url += "&id[]=" + encodeURIComponent(GetStatusList[i]);
    }
    url += "&time="+ts;

    var callback =
    {
        success: function(http) {
            var response = http.responseXML.documentElement;
            var items = response.getElementsByTagName('item');
            var elemId;
            var statusDiv;
            var status;
            var reserves;

            for (i=0; i<items.length; i++) {
                elemId = items[i].getAttribute('id');
                statusDiv = getElem('status' + elemId);
               
                if (statusDiv) {
                    if (items[i].getElementsByTagName('reserve')) {
                        reserves = items[i].getElementsByTagName('reserve').item(0).firstChild.data;
                    }

                    if (reserves == 'Y') {
                        statusDiv.innerHTML = '';
                    } else if (items[i].getElementsByTagName('availability')) {
                        if (items[i].getElementsByTagName('availability').item(0).firstChild) {
                            status = items[i].getElementsByTagName('availability').item(0).firstChild.data;
                            // write out response
                            if (status == "true") {
                                statusDiv.innerHTML = strings.available;
                            } else {
                                statusDiv.innerHTML = strings.unavailable;
                            }
                        } else {
                            statusDiv.innerHTML = strings.unknown;
                        }
                    } else {
                        statusDiv.innerHTML = strings.unknown;
                    }
                }

                if (items[i].getElementsByTagName('location')) {
                    var callnumber
                    var location = items[i].getElementsByTagName('location').item(0).firstChild.data;
                    var reserves = items[i].getElementsByTagName('reserve').item(0).firstChild.data;

                    var locationDiv = getElem('location' + elemId);
                    if (locationDiv) {
                        if (reserves == 'Y') {
                            locationDiv.innerHTML = strings.reserve;
                        } else {
                            locationDiv.innerHTML = location;
                        }
                    }

                    var callnumberDiv = getElem('callnumber' + elemId);
                    if (callnumberDiv) {
                        if (items[i].getElementsByTagName('callnumber').item(0).firstChild) {
                            callnumber = items[i].getElementsByTagName('callnumber').item(0).firstChild.data
                            callnumberDiv.innerHTML = callnumber;
                        } else {
                            callnumberDiv.innerHTML = '';
                        }
                    }
                }
            }
        }
    };
    YAHOO.util.Connect.asyncRequest('GET', url, callback, null);
}

function saveRecord(id, formElem, strings)
{
    successCallback = function() {
        // Redraw the statuses to reflect the change:
        doGetSaveStatuses();
    };
    performSaveRecord(id, formElem, strings, 'VuFind', successCallback);
}

function getSaveStatuses(id)
{
    GetSaveStatusList[GetSaveStatusList.length] = id;
}

function doGetSaveStatuses()
{
    if (GetSaveStatusList.length < 1) return;

    var now = new Date();
    var ts = Date.UTC(now.getFullYear(),now.getMonth(),now.getDay(),now.getHours(),now.getMinutes(),now.getSeconds(),now.getMilliseconds());

    var url = path + "/Search/AJAX?method=GetSaveStatuses";
    for (var i=0; i<GetSaveStatusList.length; i++) {
        url += "&id" + i + "=" + encodeURIComponent(GetSaveStatusList[i]);
    }
    url += "&time="+ts;

    var callback =
    {
        success: function(http) {
            var response = http.responseXML.documentElement;
            var items = response.getElementsByTagName('item');

            for (var i=0; i<items.length; i++) {
                var elemId = items[i].getAttribute('id');

                var result = items[i].getElementsByTagName('result').item(0).firstChild.data;
                if (result != 'False') {
                    YAHOO.util.Dom.addClass(document.getElementById('saveLink' + elemId), 'savedFavorite');
                    var lists = eval('(' + result + ')');
                    var listNames = '';
                    for (var j=0; j<lists.length;j++) {
                        if (j > 0) {
                            listNames += '<br/>';
                        }
                        listNames += jsEntityEncode(lists[j].title);
                    }
                    getElem('lists' + elemId).innerHTML = '<li>' + listNames + '</li>';
                }
            }
        }
    };
    YAHOO.util.Connect.asyncRequest('GET', url, callback, null);
}
