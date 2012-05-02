/*
* Copyright 2005 ThoughtWorks, Inc
*
*  Licensed under the Apache License, Version 2.0 (the "License");
*  you may not use this file except in compliance with the License.
*  You may obtain a copy of the License at
*
*      http://www.apache.org/licenses/LICENSE-2.0
*
*  Unless required by applicable law or agreed to in writing, software
*  distributed under the License is distributed on an "AS IS" BASIS,
*  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
*  See the License for the specific language governing permissions and
*  limitations under the License.
*
*/

passColor = "#cfffcf";
failColor = "#ffcfcf";
errorColor = "#ffffff";
workingColor = "#DEE7EC";
doneColor = "#FFFFCC";

var injectedSessionId;
var cmd1 = document.createElement("div");
var cmd2 = document.createElement("div");
var cmd3 = document.createElement("div");
var cmd4 = document.createElement("div");

var postResult = "START";
var debugMode = false;
var relayToRC = null;
var proxyInjectionMode = false;
var uniqueId = 'sel_' + Math.round(100000 * Math.random());

var RemoteRunnerOptions = classCreate();
objectExtend(RemoteRunnerOptions.prototype, URLConfiguration.prototype);
objectExtend(RemoteRunnerOptions.prototype, {
    initialize: function() {
        this._acquireQueryString();
    },
    isDebugMode: function() {
        return this._isQueryParameterTrue("debugMode");
    },

    getContinue: function() {
        return this._getQueryParameter("continue");
    },

    getDriverUrl: function() {
        return this._getQueryParameter("driverUrl");
    },

    getSessionId: function() {
        return this._getQueryParameter("sessionId");
    },

    _acquireQueryString: function () {
        if (this.queryString) return;
        if (browserVersion.isHTA) {
            var args = this._extractArgs();
            if (args.length < 2) return null;
            this.queryString = args[1];
        } else if (proxyInjectionMode) {
            this.queryString = selenium.browserbot.getCurrentWindow().location.search.substr(1);
        } else {
            this.queryString = top.location.search.substr(1);
        }
    }

});
var runOptions;

function runSeleniumTest() {
    runOptions = new RemoteRunnerOptions();
    var testAppWindow;

    if (runOptions.isMultiWindowMode()) {
        testAppWindow = openSeparateApplicationWindow('Blank.html', true);
    } else if ($('myiframe') != null) {
        var myiframe = $('myiframe');
        if (myiframe) {
            testAppWindow = myiframe.contentWindow;
        }
    }
    else {
        proxyInjectionMode = true;
        testAppWindow = window;
    }
    selenium = Selenium.createForWindow(testAppWindow, proxyInjectionMode);
    if (runOptions.getBaseUrl()) {
        selenium.browserbot.baseUrl = runOptions.getBaseUrl();
    }
    if (!debugMode) {
        debugMode = runOptions.isDebugMode();
    }
    if (proxyInjectionMode) {
        LOG.log = logToRc;
        selenium.browserbot._modifyWindow(testAppWindow);
    }
    else if (debugMode) {
        LOG.logHook = logToRc;
    }
    window.selenium = selenium;

    commandFactory = new CommandHandlerFactory();
    commandFactory.registerAll(selenium);

    currentTest = new RemoteRunner(commandFactory);

    if (document.getElementById("commandList") != null) {
        document.getElementById("commandList").appendChild(cmd4);
        document.getElementById("commandList").appendChild(cmd3);
        document.getElementById("commandList").appendChild(cmd2);
        document.getElementById("commandList").appendChild(cmd1);
    }

    var doContinue = runOptions.getContinue();
    if (doContinue != null) postResult = "OK";

    currentTest.start();
}

function buildDriverUrl() {
    var driverUrl = runOptions.getDriverUrl();
    if (driverUrl != null) {
        return driverUrl;
    }
    var s = window.location.href
    var slashPairOffset = s.indexOf("//") + "//".length
    var pathSlashOffset = s.substring(slashPairOffset).indexOf("/")
    return s.substring(0, slashPairOffset + pathSlashOffset) + "/selenium-server/driver/";
}

function logToRc(logLevel, message) {
    if (logLevel == null) {
        logLevel = "debug";
    }
    if (debugMode) {
        sendToRC("logLevel=" + logLevel + ":" + message.replace(/[\n\r\015]/g, " ") + "\n", "logging=true");
    }
}

function isArray(x) {
    return ((typeof x) == "object") && (x["length"] != null);
}

function serializeString(name, s) {
    return name + "=unescape(\"" + escape(s) + "\");";
}

function serializeObject(name, x)
{
    var s = '';

    if (isArray(x))
    {
        s = name + "=new Array(); ";
        var len = x["length"];
        for (var j = 0; j < len; j++)
        {
            s += serializeString(name + "[" + j + "]", x[j]);
        }
    }
    else if (typeof x == "string")
    {
        s = serializeString(name, x);
    }
    else
    {
        throw "unrecognized object not encoded: " + name + "(" + x + ")";
    }
    return s;
}

function relayBotToRC(s) {
}

// seems like no one uses this, but in fact it is called using eval from server-side PI mode code; however, 
// because multiple names can map to the same popup, assigning a single name confuses matters sometimes;
// thus, I'm disabling this for now.  -Nelson 10/21/06
function setSeleniumWindowName(seleniumWindowName) {
//selenium.browserbot.getCurrentWindow()['seleniumWindowName'] = seleniumWindowName;
}

RemoteRunner = classCreate();
objectExtend(RemoteRunner.prototype, new TestLoop());
objectExtend(RemoteRunner.prototype, {
    initialize : function(commandFactory) {
        this.commandFactory = commandFactory;
        this.requiresCallBack = true;
        this.commandNode = null;
        this.xmlHttpForCommandsAndResults = null;
    },

    nextCommand : function() {
        var urlParms = "";
        if (postResult == "START") {
            urlParms += "seleniumStart=true";
        }
        this.xmlHttpForCommandsAndResults = XmlHttp.create();
        sendToRC(postResult, urlParms, fnBind(this._HandleHttpResponse, this), this.xmlHttpForCommandsAndResults);
    },

    commandStarted : function(command) {
        this.commandNode = document.createElement("div");
        var innerHTML = command.command + '(';
        if (command.target != null && command.target != "") {
            innerHTML += command.target;
            if (command.value != null && command.value != "") {
                innerHTML += ', ' + command.value;
            }
        }
        innerHTML += ")";
        if (innerHTML.length >40) {
            innerHTML = innerHTML.substring(0,40);
            innerHTML += "...";
        }
        this.commandNode.innerHTML = innerHTML;
        this.commandNode.style.backgroundColor = workingColor;
        if (document.getElementById("commandList") != null) {
            document.getElementById("commandList").removeChild(cmd1);
            document.getElementById("commandList").removeChild(cmd2);
            document.getElementById("commandList").removeChild(cmd3);
            document.getElementById("commandList").removeChild(cmd4);
            cmd4 = cmd3;
            cmd3 = cmd2;
            cmd2 = cmd1;
            cmd1 = this.commandNode;
            document.getElementById("commandList").appendChild(cmd4);
            document.getElementById("commandList").appendChild(cmd3);
            document.getElementById("commandList").appendChild(cmd2);
            document.getElementById("commandList").appendChild(cmd1);
        }
    },

    commandComplete : function(result) {

        if (result.failed) {
            if (postResult == "CONTINUATION") {
                currentTest.aborted = true;
            }
            postResult = result.failureMessage;
            this.commandNode.title = result.failureMessage;
            this.commandNode.style.backgroundColor = failColor;
        } else if (result.passed) {
            postResult = "OK";
            this.commandNode.style.backgroundColor = passColor;
        } else {
            if (result.result == null) {
                postResult = "OK";
            } else {
                postResult = "OK," + result.result;
            }
            this.commandNode.style.backgroundColor = doneColor;
        }
    },

    commandError : function(message) {
        postResult = "ERROR: " + message;
        this.commandNode.style.backgroundColor = errorColor;
        this.commandNode.title = message;
    },

    testComplete : function() {
        window.status = "Selenium Tests Complete, for this Test"
        // Continue checking for new results
        this.continueTest();
        postResult = "START";
    },

    _HandleHttpResponse : function() {
        if (this.xmlHttpForCommandsAndResults.readyState == 4) {
            if (this.xmlHttpForCommandsAndResults.status == 200) {
            	if (this.xmlHttpForCommandsAndResults.responseText=="") {
                    LOG.error("saw blank string xmlHttpForCommandsAndResults.responseText");
                    return;
                }
                var command = this._extractCommand(this.xmlHttpForCommandsAndResults);
                this.currentCommand = command;
                this.continueTestAtCurrentCommand();
            } else {
                var s = 'xmlHttp returned: ' + this.xmlHttpForCommandsAndResults.status + ": " + this.xmlHttpForCommandsAndResults.statusText;
                LOG.error(s);
                this.currentCommand = null;
                setTimeout(fnBind(this.continueTestAtCurrentCommand, this), 2000);
            }

        }
    },

    _extractCommand : function(xmlHttp) {
        var command;
        try {
            var re = new RegExp("^(.*?)\n((.|[\r\n])*)");
            if (re.exec(xmlHttp.responseText)) {
                command = RegExp.$1;
                var rest = RegExp.$2;
                rest = rest.trim();
                if (rest) {
                    eval(rest);
                }
            }
            else {
                command = xmlHttp.responseText;
            }
        } catch (e) {
            alert('could not get responseText: ' + e.message);
        }
        if (command.substr(0, '|testComplete'.length) == '|testComplete') {
            return null;
        }

        return this._createCommandFromRequest(command);
    },


    _delay : function(millis) {
        var startMillis = new Date();
        while (true) {
            milli = new Date();
            if (milli - startMillis > millis) {
                break;
            }
        }
    },

// Parses a URI query string into a SeleniumCommand object
    _createCommandFromRequest : function(commandRequest) {
        //decodeURIComponent doesn't strip plus signs
        var processed = commandRequest.replace(/\+/g, "%20");
        // strip trailing spaces
        var processed = processed.replace(/\s+$/, "");
        var vars = processed.split("&");
        var cmdArgs = new Object();
        for (var i = 0; i < vars.length; i++) {
            var pair = vars[i].split("=");
            cmdArgs[pair[0]] = pair[1];
        }
        var cmd = cmdArgs['cmd'];
        var arg1 = cmdArgs['1'];
        if (null == arg1) arg1 = "";
        arg1 = decodeURIComponent(arg1);
        var arg2 = cmdArgs['2'];
        if (null == arg2) arg2 = "";
        arg2 = decodeURIComponent(arg2);
        if (cmd == null) {
            throw new Error("Bad command request: " + commandRequest);
        }
        return new SeleniumCommand(cmd, arg1, arg2);
    }

})


function sendToRC(dataToBePosted, urlParms, callback, xmlHttpObject, async) {
    if (async == null) {
        async = true;
    }
    if (xmlHttpObject == null) {
        xmlHttpObject = XmlHttp.create();
    }
    var url = buildDriverUrl() + "?"
    if (urlParms) {
        url += urlParms;
    }
    url += "&localFrameAddress=" + (proxyInjectionMode ? makeAddressToAUTFrame() : "top");
    url += getSeleniumWindowNameURLparameters();
    url += "&uniqueId=" + uniqueId;

    if (callback == null) {
        callback = function() {
        };
    }
    url += buildDriverParams() + preventBrowserCaching();
    xmlHttpObject.open("POST", url, async);
    xmlHttpObject.onreadystatechange = callback;
    xmlHttpObject.send(dataToBePosted);
    return null;
}

function buildDriverParams() {
    var params = "";

    var sessionId = runOptions.getSessionId();
    if (sessionId == undefined) {
        sessionId = injectedSessionId;
    }
    if (sessionId != undefined) {
        params = params + "&sessionId=" + sessionId;
    }
    return params;
}

function preventBrowserCaching() {
    var t = (new Date()).getTime();
    return "&counterToMakeURsUniqueAndSoStopPageCachingInTheBrowser=" + t;
}

//
// Return URL parameters pertaining to the name(s?) of the current window
//
// In selenium, the main (i.e., first) window's name is a blank string.
//
// Additional pop-ups are associated with either 1.) the name given by the 2nd parameter to window.open, or 2.) the name of a 
// property on the opening window which points at the window.
//
// An example of #2: if window X contains JavaScript as follows:
//
// 	var windowABC = window.open(...)
//
// Note that the example JavaScript above is equivalent to
//
// 	window["windowABC"] = window.open(...)
//
function getSeleniumWindowNameURLparameters() {
    var w = (proxyInjectionMode ? selenium.browserbot.getCurrentWindow() : window).top;
    var s = "&seleniumWindowName=";
    if (w.opener == null) {
        return s;
    }
    if (w["seleniumWindowName"] == null) {
    	s +=  'generatedSeleniumWindowName_' + Math.round(100000 * Math.random());
    }
    else {
    	s += w["seleniumWindowName"];
    }
    var windowOpener = w.opener;
    for (key in windowOpener) {
        var val = null;
        try {
    	    val = windowOpener[key];
        }
        catch(e) {
        }        
        if (val==w) {
	    s += "&jsWindowNameVar=" + key;			// found a js variable in the opener referring to this window
        }
    }
    return s;
}

// construct a JavaScript expression which leads to my frame (i.e., the frame containing the window
// in which this code is operating)
function makeAddressToAUTFrame(w, frameNavigationalJSexpression)
{
    if (w == null)
    {
        w = top;
        frameNavigationalJSexpression = "top";
    }

    if (w == selenium.browserbot.getCurrentWindow())
    {
        return frameNavigationalJSexpression;
    }
    for (var j = 0; j < w.frames.length; j++)
    {
        var t = makeAddressToAUTFrame(w.frames[j], frameNavigationalJSexpression + ".frames[" + j + "]");
        if (t != null)
        {
            return t;
        }
    }
    return null;
}
