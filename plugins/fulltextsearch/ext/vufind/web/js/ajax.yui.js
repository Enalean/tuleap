/* AJAX Functions using YUI Connection Manager Functionality
 *
 * @todo: Please rewrite me as a class!!!
 */

function getLightbox(module, action, id, lookfor, message, followupModule, followupAction, followupId)
{
    // Optional parameters
    if (followupModule === undefined) {followupModule = '';}
    if (followupAction === undefined) {followupAction = '';}
    if (followupId     === undefined) {followupId     = '';}

    if ((module == '') || (action == '')) {
        hideLightbox();
        return 0;
    }

    // Popup Lightbox
    lightbox();

    // Load Popup Box Content from AJAX Server
    var url = path + "/AJAX/Home";
    var params = 'method=GetLightbox' +
                 '&lightbox=true'+
                 '&submodule=' + encodeURIComponent(module) +
                 '&subaction=' + encodeURIComponent(action) +
                 '&id=' + encodeURIComponent(id) +
                 '&lookfor=' +encodeURIComponent(lookfor) +
                 '&message=' + encodeURIComponent(message) +
                 '&followupModule=' + encodeURIComponent(followupModule) +
                 '&followupAction=' + encodeURIComponent(followupAction) +
                 '&followupId=' + encodeURIComponent(followupId);
    var callback = 
    {
        success: function(transaction) {
            var response = transaction.responseXML.documentElement;
            if (response && response.getElementsByTagName('result')) {
                document.getElementById('popupbox').innerHTML =
                    response.getElementsByTagName('result').item(0).firstChild.nodeValue;
            } else {
                document.getElementById('popupbox').innerHTML =
                    document.getElementById('lightboxError').innerHTML;
            }

            // Check to see if an element within the lightbox needs to be given focus.
            // Note that we need to introduce a slight delay before taking focus due
            // to IE sensitivity.
            var focusIt = function() {
                var o = document.getElementById('mainFocus');
                if (o) {
                    o.focus();
                }
            }
            setTimeout(focusIt, 250);
        },
        failure: function(transaction) {
            document.getElementById('popupbox').innerHTML =
                document.getElementById('lightboxError').innerHTML;
        }
    };
    var transaction = YAHOO.util.Connect.asyncRequest('GET', url+'?'+params, callback, null);

    // Make Popup Box Draggable
    var dd = new YAHOO.util.DD("popupbox");
    dd.setHandleElId("popupboxHeader");
}

function SaltedLogin(elems, module, action, id, lookfor, message)
{
    // Load Popup Box Content from AJAX Server
    var url = path + "/AJAX/Home";
    var params = 'method=GetSalt';
    var callback =
    {
        success: function(transaction) {
            var response = transaction.responseXML.documentElement;
            if (response.getElementsByTagName('result')) {
                Login(elems,
                      response.getElementsByTagName('result').item(0).firstChild.nodeValue,
                      module, action, id, lookfor, message);
                
            }
        }
    };
    var transaction = YAHOO.util.Connect.asyncRequest('GET', url+'?'+params, callback, null);
}

function Login(elems, salt, module, action, id, lookfor, message)
{
    var username = elems['username'].value;
    var password = elems['password'].value;

    // Encrypt Password
    //var cipher = new Blowfish(salt);
    //password = cipher.encrypt(password);
    //password = TEAencrypt(password, salt);
    password = rc4Encrypt(salt, password);

    // Process Login via AJAX
    var url = path + "/AJAX/Home";
    var params = 'method=Login' +
                 '&username=' + username +
                 '&password=' + hexEncode(password);
    var callback =
    {
        success: function(transaction) {
            var response = transaction.responseXML.documentElement;
            var result = response.getElementsByTagName('result');
            if (result) {
                // Hide "log in" options and show "log out" options:
                if (result[0].childNodes[0].nodeValue != "Error") {
                    var login = document.getElementById('loginOptions');
                    var logout = document.getElementById('logoutOptions');
                    if (login) {
                        login.style.display = 'none';
                    }
                    if (logout) {
                        logout.style.display = 'block';
                    }
                }
                // Update user save statuses if the current context calls for it:
                if (typeof(doGetSaveStatuses) == 'function') {
                    doGetSaveStatuses();
                } else if (typeof(redrawSaveStatus) == 'function') {
                    redrawSaveStatus();
                }

                // Load the post-login action:
                getLightbox(module, action, id, lookfor, message);
            }
        }
    };
    var transaction = YAHOO.util.Connect.asyncRequest('GET', url+'?'+params, callback, null);
}