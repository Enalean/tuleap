/**
 * @fileoverview All stuff related to HTTP Polling
 * @author Stefan Strigler steve@zeank.in-berlin.de
 * @version $Revision: 452 $
 */

/**
 * Instantiates an HTTP Polling session
 * @class Implementation of {@link
 * http://www.xmpp.org/extensions/xep-0025.html HTTP Polling}
 * @extends JSJaCConnection
 * @constructor
 */
function JSJaCHttpPollingConnection(oArg) {
  /**
   * @ignore
   */
  this.base = JSJaCConnection;
  this.base(oArg);

  // give hint to JSJaCPacket that we're using HTTP Polling ...
  JSJACPACKET_USE_XMLNS = false;
}
JSJaCHttpPollingConnection.prototype = new JSJaCConnection();

/**
 * Tells whether this implementation of JSJaCConnection is polling
 * Useful if it needs to be decided
 * whether it makes sense to allow for adjusting or adjust the
 * polling interval {@link JSJaCConnection#setPollInterval}
 * @return <code>true</code> if this is a polling connection,
 * <code>false</code> otherwise.
 * @type boolean
 */
JSJaCHttpPollingConnection.prototype.isPolling = function() { return true; };

/**
 * @private
 */
JSJaCHttpPollingConnection.prototype._getFreeSlot = function() {
  if (typeof(this._req[0]) == 'undefined' ||
      typeof(this._req[0].r) == 'undefined' ||
      this._req[0].r.readyState == 4)
    return 0;
  else
    return -1;
};

/**
 * @private
 */
JSJaCHttpPollingConnection.prototype._getInitialRequestString = function() {
  var reqstr = "0";
  if (JSJAC_HAVEKEYS) {
    this._keys = new JSJaCKeys(b64_sha1,this.oDbg); // generate first set of keys
    key = this._keys.getKey();
    reqstr += ";"+key;
  }
  var streamto = this.domain;
  if (this.authhost)
    streamto = this.authhost;

  reqstr += ",<stream:stream to='"+streamto+"' xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams'";
  if (this.authtype == 'sasl' || this.authtype == 'saslanon')
    reqstr += " version='1.0'";
  reqstr += ">";
  return reqstr;
};

/**
 * @private
 */
JSJaCHttpPollingConnection.prototype._getRequestString = function(raw, last) {
  var reqstr = this._sid;
  if (JSJAC_HAVEKEYS) {
    reqstr += ";"+this._keys.getKey();
    if (this._keys.lastKey()) {
      this._keys = new JSJaCKeys(b64_sha1,this.oDbg);
      reqstr += ';'+this._keys.getKey();
    }
  }
  reqstr += ',';
  if (raw)
    reqstr += raw;
  while (this._pQueue.length) {
    reqstr += this._pQueue[0];
    this._pQueue = this._pQueue.slice(1,this._pQueue.length);
  }
  if (last)
    reqstr += '</stream:stream>';
  return reqstr;
};

/**
 * @private
 */
JSJaCHttpPollingConnection.prototype._getStreamID = function() {
  if (this._req[0].r.responseText == '') {
    this.oDbg.log("waiting for stream id",2);
    this._timeout = setTimeout(JSJaC.bind(this._sendEmpty, this),1000);
    return;
  }

  this.oDbg.log(this._req[0].r.responseText,4);

  // extract stream id used for non-SASL authentication
  if (this._req[0].r.responseText.match(/id=[\'\"]([^\'\"]+)[\'\"]/))
    this.streamid = RegExp.$1;
  this.oDbg.log("got streamid: "+this.streamid,2);

  var doc;

  try {
    var response = this._req[0].r.responseText;
    if (!response.match(/<\/stream:stream>\s*$/))
      response += '</stream:stream>';

    doc = XmlDocument.create("doc");
    doc.loadXML(response);
    if (!this._parseStreamFeatures(doc))
      return;
  } catch(e) {
    this.oDbg.log("loadXML: "+e.toString(),1);
  }

  this._connected = true;

  if (this.register)
    this._doInBandReg();
  else
    this._doAuth();

  this._process(this._timerval); // start polling
};

/**
 * @private
 */
JSJaCHttpPollingConnection.prototype._getSuspendVars = function() {
  return new Array();
};

/**
 * @private
 */
JSJaCHttpPollingConnection.prototype._handleInitialResponse = function() {
  // extract session ID
  this.oDbg.log(this._req[0].r.getAllResponseHeaders(),4);
  var aPList = this._req[0].r.getResponseHeader('Set-Cookie');
  aPList = aPList.split(";");
  for (var i=0;i<aPList.length;i++) {
    aArg = aPList[i].split("=");
    if (aArg[0] == 'ID')
      this._sid = aArg[1];
  }
  this.oDbg.log("got sid: "+this._sid,2);

  /* start sending from queue for not polling connections */
  this._connected = true;

  this._interval= setInterval(JSJaC.bind(this._checkQueue, this),
                              JSJAC_CHECKQUEUEINTERVAL);
  this._inQto = setInterval(JSJaC.bind(this._checkInQ, this),
                            JSJAC_CHECKINQUEUEINTERVAL);

  /* wait for initial stream response to extract streamid needed
   * for digest auth
   */
  this._getStreamID();
};

/**
 * @private
 */
JSJaCHttpPollingConnection.prototype._parseResponse = function(r) {
  var req = r.r;
  if (!this.connected())
    return null;

  /* handle error */
  // proxy error (!)
  if (req.status != 200) {
    this.oDbg.log("invalid response ("+req.status+"):" + req.responseText+"\n"+req.getAllResponseHeaders(),1);

    this._setStatus('internal_server_error');

    clearTimeout(this._timeout); // remove timer
    clearInterval(this._interval);
    clearInterval(this._inQto);
    this._connected = false;
    this.oDbg.log("Disconnected.",1);
    this._handleEvent('ondisconnect');
    this._handleEvent('onerror',JSJaCError('503','cancel','service-unavailable'));
    return null;
  }

  this.oDbg.log(req.getAllResponseHeaders(),4);
  var sid, aPList = req.getResponseHeader('Set-Cookie');

  if (aPList == null)
    sid = "-1:0"; // Generate internal server error
  else {
    aPList = aPList.split(";");
    var sid;
    for (var i=0;i<aPList.length;i++) {
      var aArg = aPList[i].split("=");
      if (aArg[0] == 'ID')
        sid = aArg[1];
    }
  }

  // http polling component error
  if (typeof(sid) != 'undefined' && sid.indexOf(':0') != -1) {
    switch (sid.substring(0,sid.indexOf(':0'))) {
    case '0':
      this.oDbg.log("invalid response:" + req.responseText,1);
      break;
    case '-1':
      this.oDbg.log("Internal Server Error",1);
      break;
    case '-2':
      this.oDbg.log("Bad Request",1);
      break;
    case '-3':
      this.oDbg.log("Key Sequence Error",1);
      break;
    }

    this._setStatus('internal_server_error');

    clearTimeout(this._timeout); // remove timer
    clearInterval(this._interval);
    clearInterval(this._inQto);
    this._handleEvent('onerror',JSJaCError('500','wait','internal-server-error'));
    this._connected = false;
    this.oDbg.log("Disconnected.",1);
    this._handleEvent('ondisconnect');
    return null;
  }

  if (!req.responseText || req.responseText == '')
    return null;

  try {
    var response = req.responseText.replace(/\<\?xml.+\?\>/,"");
    if (response.match(/<stream:stream/))
        response += "</stream:stream>";
    var doc = JSJaCHttpPollingConnection._parseTree("<body>"+response+"</body>");

    if (!doc || doc.tagName == 'parsererror') {
      this.oDbg.log("parsererror",1);

      doc = JSJaCHttpPollingConnection._parseTree("<stream:stream xmlns:stream='http://etherx.jabber.org/streams'>"+req.responseText);
      if (doc && doc.tagName != 'parsererror') {
        this.oDbg.log("stream closed",1);

        if (doc.getElementsByTagName('conflict').length > 0)
          this._setStatus("session-terminate-conflict");
			
        clearTimeout(this._timeout); // remove timer
        clearInterval(this._interval);
        clearInterval(this._inQto);
        this._handleEvent('onerror',JSJaCError('503','cancel','session-terminate'));
        this._connected = false;
        this.oDbg.log("Disconnected.",1);
        this._handleEvent('ondisconnect');
      } else
        this.oDbg.log("parsererror:"+doc,1);
		
      return doc;
    }

    return doc;
  } catch (e) {
    this.oDbg.log("parse error:"+e.message,1);
  }
  return null;;
};

/**
 * @private
 */
JSJaCHttpPollingConnection.prototype._reInitStream = function(to,cb,arg) {
  this._sendRaw("<stream:stream xmlns:stream='http://etherx.jabber.org/streams' xmlns='jabber:client' to='"+to+"' version='1.0'>",cb,arg);
};

/**
 * @private
 */
JSJaCHttpPollingConnection.prototype._resume = function() {
  this._process(this._timerval);
};

/**
 * @private
 */
JSJaCHttpPollingConnection.prototype._setupRequest = function(async) {
  var r = XmlHttp.create();
  try {
    r.open("POST",this._httpbase,async);
    if (r.overrideMimeType)
      r.overrideMimeType('text/plain; charset=utf-8');
    r.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
  } catch(e) { this.oDbg.log(e,1); }

  var req = new Object();
  req.r = r;
  return req;
};

/**
 * @private
 */
JSJaCHttpPollingConnection.prototype._suspend = function() {};

/*** [static] ***/

/**
 * @private
 */
JSJaCHttpPollingConnection._parseTree = function(s) {
  try {
    var r = XmlDocument.create("body","foo");
    if (typeof(r.loadXML) != 'undefined') {
      r.loadXML(s);
      return r.documentElement;
    } else if (window.DOMParser)
      return (new DOMParser()).parseFromString(s, "text/xml").documentElement;
  } catch (e) { }
  return null;
};
