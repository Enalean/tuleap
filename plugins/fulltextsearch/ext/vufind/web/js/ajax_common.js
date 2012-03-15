/* This file contains AJAX routines that are shared by multiple VuFind modules.
 */

/* Extract the first value found within the specified tag in the AJAX transaction.
 */
function getAJAXResponseValue(transaction, tag)
{
    if (transaction.responseXML && transaction.responseXML.documentElement) {
        var response = transaction.responseXML.documentElement;
        var tmp = response.getElementsByTagName(tag);
        if (tmp && tmp.item(0)) {
            return tmp.item(0).firstChild.nodeValue;
        }
    }
    return false;
}

/* Create a new list for storing favorites:
 */
function addList(form, failMsg)
{
    for (var i = 0; i < form.public.length; i++) {
        if (form.public[i].checked) {
            var isPublic = form.public[i].value;
        }
    }

    var url = path + "/MyResearch/AJAX";
    var params = "method=AddList&" +
                 "title=" + encodeURIComponent(form.title.value) + "&" +
                 "public=" + isPublic + "&" +
                 "desc=" + encodeURIComponent(form.desc.value) + "&" +
                 "followupModule=" + form.followupModule.value + "&" +
                 "followupAction=" + form.followupAction.value + "&" +
                 "followupId=" + form.followupId.value;

    var callback =
    {
        success: function(transaction) {
            var value = getAJAXResponseValue(transaction, 'result');
            if (value) {
                if (value == "Done") {
                    getLightbox(form.followupModule.value, form.followupAction.value, form.followupId.value, null, form.followupText.value);
                } else {
                    alert(value.length > 0 ? value : failMsg);
                }
            } else {
                document.getElementById('popupbox').innerHTML = failMsg;
                setTimeout("hideLightbox();", 3000);
            }
        },
        failure: function(transaction) {
            document.getElementById('popupbox').innerHTML = failMsg;
            setTimeout("hideLightbox();", 3000);
        }
    };
    var transaction = YAHOO.util.Connect.asyncRequest('GET', url+'?'+params, callback, null);
}

/* Given a base URL and a set of parameters, use AJAX to send an email; this assumes
 * that a lightbox is already open.
 */
function sendAJAXEmail(url, params, strings)
{
    document.getElementById('popupbox').innerHTML = '<h3>' + strings.sending + '</h3>';

    var callback =
    {
        success: function(transaction) {
            var value = getAJAXResponseValue(transaction, 'result');
            if (value) {
                if (value == "Done") {
                    document.getElementById('popupbox').innerHTML = '<h3>' + strings.success + '</h3>';
                    setTimeout("hideLightbox();", 3000);
                } else {
                    var errorDetails = getAJAXResponseValue(transaction, 'details');
                    document.getElementById('popupbox').innerHTML = '<h3>' + strings.failure + '</h3>' +
                        (errorDetails ? '<h3>' + errorDetails + '</h3>' : '');
                }
            } else {
                document.getElementById('popupbox').innerHTML = '<h3>' + strings.failure + '</h3>';
            }
        },
        failure: function(transaction) {
            document.getElementById('popupbox').innerHTML = strings.failure;
        }
    };
    var transaction = YAHOO.util.Connect.asyncRequest('GET', url+'?'+params, callback, null);
}

/* Send the current URL in an email to a specific address, from a specific address,
 * and including some message text.
 */
function SendURLEmail(to, from, message, strings)
{
    var url = path + "/Search/AJAX";
    var params = "method=SendEmail&" +
                 "url=" + URLEncode(window.location.href) + "&" +
                 "from=" + encodeURIComponent(from) + "&" +
                 "to=" + encodeURIComponent(to) + "&" +
                 "message=" + encodeURIComponent(message);
    sendAJAXEmail(url, params, strings);
}

function URLEncode(clearString) {
    var output = '';
    var x = 0;
    clearString = clearString.toString();
    var regex = /(^[a-zA-Z0-9_.]*)/;
    while (x < clearString.length) {
        var match = regex.exec(clearString.substr(x));
        if (match != null && match.length > 1 && match[1] != '') {
            output += match[1];
            x += match[1].length;
        } else {
            if (clearString[x] == ' ')
                output += '+';
            else {
                var charCode = clearString.charCodeAt(x);
                var hexVal = charCode.toString(16);
                output += '%' + ( hexVal.length < 2 ? '0' : '' ) + hexVal.toUpperCase();
            }
            x++;
        }
    }
    return output;
}

function sendAJAXSMS(url, params, strings)
{
    document.getElementById('popupbox').innerHTML = '<h3>' + strings.sending + '</h3>';

    var callback =
    {
        success: function(transaction) {
            var value = getAJAXResponseValue(transaction, 'result');
            if (value) {
                if (value == "Done") {
                    document.getElementById('popupbox').innerHTML = '<h3>' + strings.success + '</h3>';
                    setTimeout("hideLightbox();", 3000);
                } else {
                    document.getElementById('popupbox').innerHTML = strings.failure;
                }
            } else {
                document.getElementById('popupbox').innerHTML = strings.failure;
            }
        },
        failure: function(transaction) {
            document.getElementById('popupbox').innerHTML = strings.failure;
        }
    };
    var transaction = YAHOO.util.Connect.asyncRequest('GET', url+'?'+params, callback, null);
}

function moreFacets(name)
{
    document.getElementById("more" + name).style.display="none";
    document.getElementById("narrowGroupHidden_" + name).style.display="block";
}
                
function lessFacets(name)
{
    document.getElementById("more" + name).style.display="block";
    document.getElementById("narrowGroupHidden_" + name).style.display="none";
}

function performSaveRecord(id, formElem, strings, service, successCallback)
{
    var tags = formElem.elements['mytags'].value;
    var notes = formElem.elements['notes'].value;
    var list = formElem.elements['list'].options[formElem.elements['list'].selectedIndex].value;

    var url = path + "/Record/" + id + "/AJAX";
    var params = "method=SaveRecord&" +
                 "service=" + encodeURIComponent(service) + "&" +
                 "mytags=" + encodeURIComponent(tags) + "&" +
                 "list=" + list + "&" +
                 "notes=" + encodeURIComponent(notes);
    var callback =
    {
        success: function(transaction) {
            var response = transaction.responseXML.documentElement;
            if (response.getElementsByTagName('result')) {
                var value = response.getElementsByTagName('result').item(0).firstChild.nodeValue;
                if (value == "Done") {
                    successCallback();
                    hideLightbox();
                } else {
                    getLightbox('Record', 'Save', id, null, strings.add);
                }
            } else {
                document.getElementById('popupbox').innerHTML = strings.error;
                setTimeout("hideLightbox();", 3000);
            }
        },
        failure: function(transaction) {
            document.getElementById('popupbox').innerHTML = strings.error;
            setTimeout("hideLightbox();", 3000);
        }
    };
    var transaction = YAHOO.util.Connect.asyncRequest('GET', url+'?'+params, callback, null);
}