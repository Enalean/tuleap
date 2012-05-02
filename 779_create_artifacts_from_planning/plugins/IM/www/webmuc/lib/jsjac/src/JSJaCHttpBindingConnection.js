/**
 * @fileoverview All stuff related to HTTP Binding
 * @author Stefan Strigler steve@zeank.in-berlin.de
 * @version $Revision: 483 $
 */

/**
 * Instantiates an HTTP Binding session
 * @class Implementation of {@link
 * http://www.xmpp.org/extensions/xep-0206.html XMPP Over BOSH}
 * formerly known as HTTP Binding.
 * @extends JSJaCConnection
 * @constructor
 */
function JSJaCHttpBindingConnection(oArg) {
  /**
   * @ignore
   */
  this.base = JSJaCConnection;
  this.base(oArg);

  // member vars
  /**
   * @private
   */
  this._hold = JSJACHBC_MAX_HOLD;
  /**
   * @private
   */
  this._inactivity = 0;
  /**
   * @private
   */
  this._last_requests = new Object(); // 'hash' storing hold+1 last requests
  /**
   * @private
   */
  this._last_rid = 0;                 // I know what you did last summer
  /**
   * @private
   */
  this._min_polling = 0;

  /**
   * @private
   */
  this._pause = 0;
  /**
   * @private
   */
  this._wait = JSJACHBC_MAX_WAIT;
}
JSJaCHttpBindingConnection.prototype = new JSJaCConnection();

/**
 * Inherit an instantiated HTTP Binding session
 */
JSJaCHttpBindingConnection.prototype.inherit = function(oArg) {
  this.domain = oArg.domain || 'localhost';
  this.username = oArg.username;
  this.resource = oArg.resource;
  this._sid = oArg.sid;
  this._rid = oArg.rid;
  this._min_polling = oArg.polling;
  this._inactivity = oArg.inactivity;
  this._setHold(oArg.requests-1);
  this.setPollInterval(this._timerval);
  if (oArg.wait)
    this._wait = oArg.wait; // for whatever reason

  this._connected = true;

  this._handleEvent('onconnect');

  this._interval= setInterval(JSJaC.bind(this._checkQueue, this),
                              JSJAC_CHECKQUEUEINTERVAL);
  this._inQto = setInterval(JSJaC.bind(this._checkInQ, this),
                            JSJAC_CHECKINQUEUEINTERVAL);
  this._timeout = setTimeout(JSJaC.bind(this._process, this),
                             this.getPollInterval());
};

/**
 * Sets poll interval
 * @param {int} timerval the interval in seconds
 */
JSJaCHttpBindingConnection.prototype.setPollInterval = function(timerval) {
  if (timerval && !isNaN(timerval)) {
    if (!this.isPolling())
      this._timerval = 100;
    else if (this._min_polling && timerval < this._min_polling*1000)
      this._timerval = this._min_polling*1000;
    else if (this._inactivity && timerval > this._inactivity*1000)
      this._timerval = this._inactivity*1000;
    else
      this._timerval = timerval;
  }
  return this._timerval;
};

/**
 * whether this session is in polling mode
 * @type boolean
 */
JSJaCHttpBindingConnection.prototype.isPolling = function() { return (this._hold == 0) };

/**
 * @private
 */
JSJaCHttpBindingConnection.prototype._getFreeSlot = function() {
  for (var i=0; i<this._hold+1; i++)
    if (typeof(this._req[i]) == 'undefined' || typeof(this._req[i].r) == 'undefined' || this._req[i].r.readyState == 4)
      return i;
  return -1; // nothing found
};

/**
 * @private
 */
JSJaCHttpBindingConnection.prototype._getHold = function() { return this._hold; };

/**
 * @private
 */
JSJaCHttpBindingConnection.prototype._getRequestString = function(raw, last) {
  raw = raw || '';
  var reqstr = '';

  // check if we're repeating a request

  if (this._rid <= this._last_rid && typeof(this._last_requests[this._rid]) != 'undefined') // repeat!
    reqstr = this._last_requests[this._rid].xml;
  else { // grab from queue
    var xml = '';
    while (this._pQueue.length) {
      var curNode = this._pQueue[0];
      xml += curNode;
      this._pQueue = this._pQueue.slice(1,this._pQueue.length);
    }

    reqstr = "<body rid='"+this._rid+"' sid='"+this._sid+"' xmlns='http://jabber.org/protocol/httpbind' ";
    if (JSJAC_HAVEKEYS) {
      reqstr += "key='"+this._keys.getKey()+"' ";
      if (this._keys.lastKey()) {
        this._keys = new JSJaCKeys(hex_sha1,this.oDbg);
        reqstr += "newkey='"+this._keys.getKey()+"' ";
      }
    }
    if (last)
      reqstr += "type='terminate'";
    else if (this._reinit) {
      if (JSJACHBC_USE_BOSH_VER) 
        reqstr += "xmpp:restart='true' xmlns:xmpp='urn:xmpp:xbosh'";
      this._reinit = false;
    }

    if (xml != '' || raw != '') {
      reqstr += ">" + raw + xml + "</body>";
    } else {
      reqstr += "/>";
    }

    this._last_requests[this._rid] = new Object();
    this._last_requests[this._rid].xml = reqstr;
    this._last_rid = this._rid;

    for (var i in this._last_requests)
      if (this._last_requests.hasOwnProperty(i) &&
          i < this._rid-this._hold)
        delete(this._last_requests[i]); // truncate
  }
	
  return reqstr;
};

/**
 * @private
 */
JSJaCHttpBindingConnection.prototype._getInitialRequestString = function() {
  var reqstr = "<body content='text/xml; charset=utf-8' hold='"+this._hold+"' xmlns='http://jabber.org/protocol/httpbind' to='"+this.authhost+"' wait='"+this._wait+"' rid='"+this._rid+"'";
  if (this.host || this.port)
    reqstr += " route='xmpp:"+this.host+":"+this.port+"'";
  if (this.secure)
    reqstr += " secure='"+this.secure+"'";
  if (JSJAC_HAVEKEYS) {
    this._keys = new JSJaCKeys(hex_sha1,this.oDbg); // generate first set of keys
    key = this._keys.getKey();
    reqstr += " newkey='"+key+"'";
  }
  if (this._xmllang)
    reqstr += " xml:lang='"+this._xmllang + "'";

  if (JSJACHBC_USE_BOSH_VER) {
    reqstr += " ver='" + JSJACHBC_BOSH_VERSION + "'";
    reqstr += " xmlns:xmpp='urn:xmpp:xbosh'";
    if (this.authtype == 'sasl' || this.authtype == 'saslanon')
      reqstr += " xmpp:version='1.0'";
  }
  reqstr += "/>";
  return reqstr;
};

/**
 * @private
 */
JSJaCHttpBindingConnection.prototype._getStreamID = function(slot) {

  this.oDbg.log(this._req[slot].r.responseText,4);

  if (!this._req[slot].r.responseXML || !this._req[slot].r.responseXML.documentElement) {
    this._handleEvent('onerror',JSJaCError('503','cancel','service-unavailable'));
    return;
  }
  var body = this._req[slot].r.responseXML.documentElement;

  // extract stream id used for non-SASL authentication
  if (body.getAttribute('authid')) {
    this.streamid = body.getAttribute('authid');
    this.oDbg.log("got streamid: "+this.streamid,2);
  } else {
    this._timeout = setTimeout(JSJaC.bind(this._sendEmpty, this),
                               this.getPollInterval());
    return;
  }

  this._timeout = setTimeout(JSJaC.bind(this._process, this),
                             this.getPollInterval());

  if (!this._parseStreamFeatures(body))
    return;

  if (this.register)
    this._doInBandReg();
  else
    this._doAuth();
};

/**
 * @private
 */
JSJaCHttpBindingConnection.prototype._getSuspendVars = function() {
  return ('host,port,secure,_rid,_last_rid,_wait,_min_polling,_inactivity,_hold,_last_requests,_pause').split(',');
};

/**
 * @private
 */
JSJaCHttpBindingConnection.prototype._handleInitialResponse = function(slot) {
  try {
    // This will throw an error on Mozilla when the connection was refused
    this.oDbg.log(this._req[slot].r.getAllResponseHeaders(),4);
    this.oDbg.log(this._req[slot].r.responseText,4);
  } catch(ex) {
    this.oDbg.log("No response",4);
  }

  if (this._req[slot].r.status != 200 || !this._req[slot].r.responseXML) {
    this.oDbg.log("initial response broken (status: "+this._req[slot].r.status+")",1);
    this._handleEvent('onerror',JSJaCError('503','cancel','service-unavailable'));
    return;
  }
  var body = this._req[slot].r.responseXML.documentElement;

  if (!body || body.tagName != 'body' || body.namespaceURI != 'http://jabber.org/protocol/httpbind') {
    this.oDbg.log("no body element or incorrect body in initial response",1);
    this._handleEvent("onerror",JSJaCError("500","wait","internal-service-error"));
    return;
  }

  // Check for errors from the server
  if (body.getAttribute("type") == "terminate") {
    this.oDbg.log("invalid response:\n" + this._req[slot].r.responseText,1);
    clearTimeout(this._timeout); // remove timer
    this._connected = false;
    this.oDbg.log("Disconnected.",1);
    this._handleEvent('ondisconnect');
    this._handleEvent('onerror',JSJaCError('503','cancel','service-unavailable'));
    return;
  }

  // get session ID
  this._sid = body.getAttribute('sid');
  this.oDbg.log("got sid: "+this._sid,2);

  // get attributes from response body
  if (body.getAttribute('polling'))
    this._min_polling = body.getAttribute('polling');

  if (body.getAttribute('inactivity'))
    this._inactivity = body.getAttribute('inactivity');

  if (body.getAttribute('requests'))
    this._setHold(body.getAttribute('requests')-1);
  this.oDbg.log("set hold to " + this._getHold(),2);

  if (body.getAttribute('ver'))
    this._bosh_version = body.getAttribute('ver');

  if (body.getAttribute('maxpause'))
    this._pause = Number.max(body.getAttribute('maxpause'), JSJACHBC_MAXPAUSE);

  // must be done after response attributes have been collected
  this.setPollInterval(this._timerval);

  /* start sending from queue for not polling connections */
  this._connected = true;

  this._inQto = setInterval(JSJaC.bind(this._checkInQ, this),
                            JSJAC_CHECKINQUEUEINTERVAL);
  this._interval= setInterval(JSJaC.bind(this._checkQueue, this),
                              JSJAC_CHECKQUEUEINTERVAL);

  /* wait for initial stream response to extract streamid needed
   * for digest auth
   */
  this._getStreamID(slot);
};

/**
 * @private
 */
JSJaCHttpBindingConnection.prototype._parseResponse = function(req) {
  if (!this.connected() || !req)
    return null;

  var r = req.r; // the XmlHttpRequest

  try {
    if (r.status == 404 || r.status == 403) {
      // connection manager killed session
      this._abort();
      return null;
    }

    if (r.status != 200 || !r.responseXML) {
      this._errcnt++;
      var errmsg = "invalid response ("+r.status+"):\n" + r.getAllResponseHeaders()+"\n"+r.responseText;
      if (!r.responseXML)
        errmsg += "\nResponse failed to parse!";
      this.oDbg.log(errmsg,1);
      if (this._errcnt > JSJAC_ERR_COUNT) {
        // abort
        this._abort();
        return null;
      }
      this.oDbg.log("repeating ("+this._errcnt+")",1);
     
      this._setStatus('proto_error_fallback');
     
      // schedule next tick
      setTimeout(JSJaC.bind(this._resume, this),
                 this.getPollInterval());
     
      return null;
    }
  } catch (e) {
    this.oDbg.log("XMLHttpRequest error: status not available", 1);
	this._errcnt++;
	if (this._errcnt > JSJAC_ERR_COUNT) {
	  // abort
	  this._abort();
	} else {
	  this.oDbg.log("repeating ("+this._errcnt+")",1);
     
	  this._setStatus('proto_error_fallback');
     
	  // schedule next tick
	  setTimeout(JSJaC.bind(this._resume, this),
                     this.getPollInterval()); 
    }
    return null;
  }

  var body = r.responseXML.documentElement;
  if (!body || body.tagName != 'body' ||
	  body.namespaceURI != 'http://jabber.org/protocol/httpbind') {
    this.oDbg.log("invalid response:\n" + r.responseText,1);

    clearTimeout(this._timeout); // remove timer
    clearInterval(this._interval);
    clearInterval(this._inQto);

    this._connected = false;
    this.oDbg.log("Disconnected.",1);
    this._handleEvent('ondisconnect');

    this._setStatus('internal_server_error');
    this._handleEvent('onerror',
					  JSJaCError('500','wait','internal-server-error'));

    return null;
  }

  if (typeof(req.rid) != 'undefined' && this._last_requests[req.rid]) {
    if (this._last_requests[req.rid].handled) {
      this.oDbg.log("already handled "+req.rid,2);
      return null;
    } else
      this._last_requests[req.rid].handled = true;
  }


  // Check for errors from the server
  if (body.getAttribute("type") == "terminate") {
    this.oDbg.log("session terminated:\n" + r.responseText,1);

    clearTimeout(this._timeout); // remove timer
    clearInterval(this._interval);
    clearInterval(this._inQto);

    if (body.getAttribute("condition") == "remote-stream-error")
      if (body.getElementsByTagName("conflict").length > 0)
        this._setStatus("session-terminate-conflict");
    this._handleEvent('onerror',JSJaCError('503','cancel',body.getAttribute('condition')));
    this._connected = false;
    this.oDbg.log("Disconnected.",1);
    this._handleEvent('ondisconnect');
    return null;
  }

  // no error
  this._errcnt = 0;
  return r.responseXML.documentElement;
};

/**
 * @private
 */
JSJaCHttpBindingConnection.prototype._reInitStream = function(to,cb,arg) {
  /* [TODO] we can't handle 'to' here as this is not (yet) supported
   * by the protocol
   */

  // tell http binding to reinit stream with/before next request
  this._reinit = true;
  cb.call(this,arg); // proceed with next callback

  /* [TODO] make sure that we're checking for new stream features when
   * 'cb' finishes
   */
};

/**
 * @private
 */
JSJaCHttpBindingConnection.prototype._resume = function() {
  /* make sure to repeat last request as we can be sure that
   * it had failed (only if we're not using the 'pause' attribute
   */
  if (this._pause == 0 && this._rid >= this._last_rid)
    this._rid = this._last_rid-1;

  this._process();
};

/**
 * @private
 */
JSJaCHttpBindingConnection.prototype._setHold = function(hold)  {
  if (!hold || isNaN(hold) || hold < 0)
    hold = 0;
  else if (hold > JSJACHBC_MAX_HOLD)
    hold = JSJACHBC_MAX_HOLD;
  this._hold = hold;
  return this._hold;
};

/**
 * @private
 */
JSJaCHttpBindingConnection.prototype._setupRequest = function(async) {
  var req = new Object();
  var r = XmlHttp.create();
  try {
    r.open("POST",this._httpbase,async);
    r.setRequestHeader('Content-Type','text/xml; charset=utf-8');
  } catch(e) { this.oDbg.log(e,1); }
  req.r = r;
  this._rid++;
  req.rid = this._rid;
  return req;
};

/**
 * @private
 */
JSJaCHttpBindingConnection.prototype._suspend = function() {
  if (this._pause == 0)
    return; // got nothing to do

  var slot = this._getFreeSlot();
  // Intentionally synchronous
  this._req[slot] = this._setupRequest(false);

  var reqstr = "<body pause='"+this._pause+"' xmlns='http://jabber.org/protocol/httpbind' sid='"+this._sid+"' rid='"+this._rid+"'";
  if (JSJAC_HAVEKEYS) {
    reqstr += " key='"+this._keys.getKey()+"'";
    if (this._keys.lastKey()) {
      this._keys = new JSJaCKeys(hex_sha1,this.oDbg);
      reqstr += " newkey='"+this._keys.getKey()+"'";
    }

  }
  reqstr += ">";

  while (this._pQueue.length) {
    var curNode = this._pQueue[0];
    reqstr += curNode;
    this._pQueue = this._pQueue.slice(1,this._pQueue.length);
  }

  //reqstr += "<presence type='unavailable' xmlns='jabber:client'/>";
  reqstr += "</body>";

  this.oDbg.log("Disconnecting: " + reqstr,4);
  this._req[slot].r.send(reqstr);
};
