function getSaveStatus(id, elemId)
{
    var url = path + "/Record/" + encodeURIComponent(id) + "/AJAX";
    var params = "method=GetSaveStatus";
    var callback =
    {
        success: function(transaction) {
            var response = transaction.responseXML.documentElement;
            if (response.getElementsByTagName('result')) {
                var value = response.getElementsByTagName('result').item(0).firstChild.nodeValue;
                if (value == 'Saved') {
                    YAHOO.util.Dom.addClass(document.getElementById(elemId), 'savedFavorite');
                }
            }
        }
    };
    var transaction = YAHOO.util.Connect.asyncRequest('GET', url+'?'+params, callback, null);
}

function saveRecord(id, formElem, strings)
{
    successCallback = function() {
        // Highlight the save link to indicate that the content is saved:
        YAHOO.util.Dom.addClass(document.getElementById('saveLink'), 'savedFavorite');
    };
    performSaveRecord(id, formElem, strings, 'VuFind', successCallback);
}

function SendEmail(id, to, from, message, strings)
{
    var url = path + "/Record/" + encodeURIComponent(id) + "/AJAX";
    var params = "method=SendEmail&" +
                 "from=" + encodeURIComponent(from) + "&" +
                 "to=" + encodeURIComponent(to) + "&" +
                 "message=" + encodeURIComponent(message);
    sendAJAXEmail(url, params, strings);
}

function SendSMS(id, to, provider, strings)
{
    var url = path + "/Record/" + encodeURIComponent(id) + "/AJAX";
    var params = "method=SendSMS&" +
                 "to=" + encodeURIComponent(to) + "&" +
                 "provider=" + encodeURIComponent(provider);
    sendAJAXSMS(url, params, strings);
}

function SaveTag(id, formElem, strings)
{
    var tags = formElem.elements['tag'].value;

    var url = path + "/Record/" + encodeURIComponent(id) + "/AJAX";
    var params = "method=SaveTag&tag=" + encodeURIComponent(tags);
    var callback =
    {
        success: function(transaction) {
            var response = transaction.responseXML ? transaction.responseXML.documentElement : false;
            var result = response ? response.getElementsByTagName('result') : false;
            if (result && result.length > 0) {
                if (result.item(0).firstChild.nodeValue == "Unauthorized") {
                    document.forms['loginForm'].elements['followup'].value='SaveRecord';
                    popupMenu('loginBox');
                } else {
                    GetTags(id, 'tagList', strings);
                    document.getElementById('popupbox').innerHTML = '<h3>' + strings.success +'</h3>';
                    setTimeout("hideLightbox();", 3000);
                }
            } else {
                document.getElementById('popupbox').innerHTML = strings.save_error;
            }
        },
        failure: function(transaction) {
            document.getElementById('popupbox').innerHTML = strings.save_error;
        }
    };
    var transaction = YAHOO.util.Connect.asyncRequest('GET', url+'?'+params, callback, null);
}

function GetTags(id, elemId, strings)
{
    var url = path + "/Record/" + encodeURIComponent(id) + "/AJAX";
    var params = "method=GetTags";
    var callback =
    {
        success: function(transaction) {
            var response = transaction.responseXML ? transaction.responseXML.documentElement : false;
            if (response && response.getElementsByTagName('result')) {
                var tags = response.getElementsByTagName("Tag");
                var output = "";
                if(tags && tags.length > 0) {
                    for(i = 0; i < tags.length; i++) {
                        if (i > 0) {
                            output = output + ", ";
                        }
                        output = output + '<a href="' + path + '/Search/Results?tag=' +
                                 encodeURIComponent(tags.item(i).childNodes[0].nodeValue) + '">' +
                                 jsEntityEncode(tags.item(i).childNodes[0].nodeValue) + '</a> (' +
                                 tags.item(i).getAttribute('count') + ")";
                    }
                }
                document.getElementById(elemId).innerHTML = output;
            } else {
                document.getElementById(elemId).innerHTML = strings.load_error;
            }
        },
        failure: function(transaction) {
            document.getElementById(elemId).innerHTML = strings.load_error;
        }
    };
    var transaction = YAHOO.util.Connect.asyncRequest('GET', url+'?'+params, callback, null);
}

function SaveComment(id, strings)
{
    comment = document.forms['commentForm'].elements['comment'].value;

    var url = path + "/Record/" + encodeURIComponent(id) + "/AJAX";
    var params = "method=SaveComment&comment=" + encodeURIComponent(comment);
    var callback =
    {
        success: function(transaction) {
            var response = transaction.responseXML ? transaction.responseXML.documentElement : false;
            var result = false;
            if (response) {
                result = response.getElementsByTagName('result')
            }
            if (result && result.length > 0) {
                result = result.item(0).firstChild.nodeValue;
                if (result == "Done") {
                    document.forms['commentForm'].elements['comment'].value = '';
                    LoadComments(id, strings);
                } else {
                    getLightbox('AJAX', 'Login', id, null, strings.save_title);
                }
            } else {
                alert(strings.save_error);
            }
        },
        failure: function(transaction) {
            alert(strings.save_error);
        }
    };
    var transaction = YAHOO.util.Connect.asyncRequest('GET', url+'?'+params, callback, null);
}

function LoadComments(id, strings)
{
    var output = '';
    
    var url = path + "/Record/" + encodeURIComponent(id) + "/AJAX";
    var params = "method=GetComments";
    var callback =
    {
        success: function(transaction) {
            var result = false;
            var response = transaction.responseXML ? transaction.responseXML.documentElement : false;
            if (response) {
                result = response.getElementsByTagName('result');
            }
            if (result && result.length > 0) {
                document.getElementById('commentList').innerHTML = result.item(0).firstChild.data;
            } else {
                document.getElementById('commentList').innerHTML = strings.load_error;
            }
        },
        failure: function(transaction) {
            document.getElementById('commentList').innerHTML = strings.load_error;
        }
    };
    var transaction = YAHOO.util.Connect.asyncRequest('GET', url+'?'+params, callback, null);
}
