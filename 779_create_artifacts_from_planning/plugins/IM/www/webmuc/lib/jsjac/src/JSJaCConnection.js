/**
 * @fileoverview Contains all things in common for all subtypes of connections
 * supported.
 * @author Stefan Strigler steve@zeank.in-berlin.de
 * @version $Revision: 476 $
 */

/**
 * Creates a new Jabber connection (a connection to a jabber server)
 * @class Somewhat abstract base class for jabber connections. Contains all
 * of the code in common for all jabber connections
 * @constructor
 * @param {JSON http://www.json.org/index} oArg JSON with properties: <br>
 * * <code>httpbase</code> the http base address of the service to be used for
 * connecting to jabber<br>
 * * <code>oDbg</code> (optional) a reference to a debugger interface
 */
function JSJaCConnection(oArg) {

  if (oArg && oArg.oDbg && oArg.oDbg.log)
    /**
     * Reference to debugger interface
     *(needs to implement method <code>log</code>)
     * @type Debugger
     */
    this.oDbg = oArg.oDbg;
  else {
    this.oDbg = new Object(); // always initialise a debugger
    this.oDbg.log = function() { };
  }

  if (oArg && oArg.httpbase)
    /**
     * @private
     */
    this._httpbase = oArg.httpbase;
 
  if (oArg && oArg.allow_plain)
    /**
     * @private
     */
    this.allow_plain = oArg.allow_plain;
  else
    this.allow_plain = JSJAC_ALLOW_PLAIN;

  /**
   * @private
   */
  this._connected = false;
  /**
   * @private
   */
  this._events = new Array();
  /**
   * @private
   */
  this._keys = null;
  /**
   * @private
   */
  this._ID = 0;
  /**
   * @private
   */
  this._inQ = new Array();
  /**
   * @private
   */
  this._pQueue = new Array();
  /**
   * @private
   */
  this._regIDs = new Array();
  /**
   * @private
   */
  this._req = new Array();
  /**
   * @private
   */
  this._status = 'intialized';
  /**
   * @private
   */
  this._errcnt = 0;
  /**
   * @private
   */
  this._inactivity = JSJAC_INACTIVITY;
  /**
   * @private
   */
  this._sendRawCallbacks = new Array();

  if (oArg && oArg.timerval)
    this.setPollInterval(oArg.timerval);
}

JSJaCConnection.prototype.connect = function(oArg) {
  this._setStatus('connecting');

  this.domain = oArg.domain || 'localhost';
  this.username = oArg.username;
  this.resource = oArg.resource;
  this.pass = oArg.pass;
  this.register = oArg.register;

  this.authhost = oArg.authhost || this.domain;
  this.authtype = oArg.authtype || 'sasl';

  if (oArg.xmllang && oArg.xmllang != '')
    this._xmllang = oArg.xmllang;

  this.host = oArg.host || this.domain;
  this.port = oArg.port || 5222;
  if (oArg.secure)
    this.secure = 'true';
  else
    this.secure = 'false';

  if (oArg.wait)
    this._wait = oArg.wait;

  this.jid = this.username + '@' + this.domain;
  this.fulljid = this.jid + '/' + this.resource;

  this._rid  = Math.round( 100000.5 + ( ( (900000.49999) - (100000.5) ) * Math.random() ) );

  // setupRequest must be done after rid is created but before first use in reqstr
  var slot = this._getFreeSlot();
  this._req[slot] = this._setupRequest(true);

  var reqstr = this._getInitialRequestString();

  this.oDbg.log(reqstr,4);

  this._req[slot].r.onreadystatechange = 
  JSJaC.bind(function() {
               if (this._req[slot].r.readyState == 4) {
                 this.oDbg.log("async recv: "+this._req[slot].r.responseText,4);
                 this._handleInitialResponse(slot); // handle response
               }
             }, this);
  
  if (typeof(this._req[slot].r.onerror) != 'undefined') {
    this._req[slot].r.onerror = 
      JSJaC.bind(function(e) {
                   this.oDbg.log('XmlHttpRequest error',1);
                   return false;
                 }, this);
  }

  this._req[slot].r.send(reqstr);
};

/**
 * Tells whether this connection is connected
 * @return <code>true</code> if this connections is connected,
 * <code>false</code> otherwise
 * @type boolean
 */
JSJaCConnection.prototype.connected = function() { return this._connected; };

/**
 * Disconnects from jabber server and terminates session (if applicable)
 */
JSJaCConnection.prototype.disconnect = function() {
  this._setStatus('disconnecting');

  if (!this.connected())
    return;
  this._connected = false;

  clearInterval(this._interval);
  clearInterval(this._inQto);

  if (this._timeout)
    clearTimeout(this._timeout); // remove timer

  var slot = this._getFreeSlot();
  // Intentionally synchronous
  this._req[slot] = this._setupRequest(false);

  request = this._getRequestString(false, true);

  this.oDbg.log("Disconnecting: " + request,4);
  this._req[slot].r.send(request);

  try {
    JSJaCCookie.read('JSJaC_State').erase();
  } catch (e) {}

  this.oDbg.log("Disconnected: "+this._req[slot].r.responseText,2);
  this._handleEvent('ondisconnect');
};

/**
 * Gets current value of polling interval
 * @return Polling interval in milliseconds
 * @type int
 */
JSJaCConnection.prototype.getPollInterval = function() {
  return this._timerval;
};

/**
 * Registers an event handler (callback) for this connection.

 * <p>Note: All of the packet handlers for specific packets (like
 * message_in, presence_in and iq_in) fire only if there's no
 * callback associated with the id.<br>

 * <p>Example:<br/>
 * <code>con.registerHandler('iq', 'query', 'jabber:iq:version', handleIqVersion);</code>


 * @param {String} event One of

 * <ul>
 * <li>onConnect - connection has been established and authenticated</li>
 * <li>onDisconnect - connection has been disconnected</li>
 * <li>onResume - connection has been resumed</li>

 * <li>onStatusChanged - connection status has changed, current
 * status as being passed argument to handler. See {@link #status}.</li>

 * <li>onError - an error has occured, error node is supplied as
 * argument, like this:<br><code>&lt;error code='404' type='cancel'&gt;<br>
 * &lt;item-not-found xmlns='urn:ietf:params:xml:ns:xmpp-stanzas'/&gt;<br>
 * &lt;/error&gt;</code></li>

 * <li>packet_in - a packet has been received (argument: the
 * packet)</li>

 * <li>packet_out - a packet is to be sent(argument: the
 * packet)</li>

 * <li>message_in | message - a message has been received (argument:
 * the packet)</li>

 * <li>message_out - a message packet is to be sent (argument: the
 * packet)</li>

 * <li>presence_in | presence - a presence has been received
 * (argument: the packet)</li>

 * <li>presence_out - a presence packet is to be sent (argument: the
 * packet)</li>

 * <li>iq_in | iq - an iq has been received (argument: the packet)</li>
 * <li>iq_out - an iq is to be sent (argument: the packet)</li>
 * </ul>

 * @param {String} childName A childnode's name that must occur within a
 * retrieved packet [optional]

 * @param {String} childNS A childnode's namespace that must occure within
 * a retrieved packet (works only if childName is given) [optional]

 * @param {String} type The type of the packet to handle (works only if childName and chidNS are given (both may be set to '*' in order to get skipped) [optional]

 * @param {Function} handler The handler to be called when event occurs. If your handler returns 'true' it cancels bubbling of the event. No other registered handlers for this event will be fired.
 */
JSJaCConnection.prototype.registerHandler = function(event) {
  event = event.toLowerCase(); // don't be case-sensitive here
  var eArg = {handler: arguments[arguments.length-1],
              childName: '*',
              childNS: '*',
              type: '*'};
  if (arguments.length > 2)
    eArg.childName = arguments[1];
  if (arguments.length > 3)
    eArg.childNS = arguments[2];
  if (arguments.length > 4)
    eArg.type = arguments[3];
  if (!this._events[event])
    this._events[event] = new Array(eArg);
  else
    this._events[event] = this._events[event].concat(eArg);

  // sort events in order how specific they match criterias thus using
  // wildcard patterns puts them back in queue when it comes to
  // bubbling the event
  this._events[event] =
  this._events[event].sort(function(a,b) {
    var aRank = 0;
    var bRank = 0;
    with (a) {
      if (type == '*')
        aRank++;
      if (childNS == '*')
        aRank++;
      if (childName == '*')
        aRank++;
    }
    with (b) {
      if (type == '*')
        bRank++;
      if (childNS == '*')
        bRank++;
      if (childName == '*')
        bRank++;
    }
    if (aRank > bRank)
      return 1;
    if (aRank < bRank)
      return -1;
    return 0;
  });
  this.oDbg.log("registered handler for event '"+event+"'",2);
};

JSJaCConnection.prototype.unregisterHandler = function(event,handler) {
  event = event.toLowerCase(); // don't be case-sensitive here

  if (!this._events[event])
    return;

  var arr = this._events[event], res = new Array();
  for (var i=0; i<arr.length; i++)
    if (arr[i].handler != handler)
      res.push(arr[i]);

  if (arr.length != res.length) {
    this._events[event] = res;
    this.oDbg.log("unregistered handler for event '"+event+"'",2);
  }
};

/**
 * Register for iq packets of type 'get'.
 * @param {String} childName A childnode's name that must occur within a
 * retrieved packet

 * @param {String} childNS A childnode's namespace that must occure within
 * a retrieved packet (works only if childName is given)

 * @param {Function} handler The handler to be called when event occurs. If your handler returns 'true' it cancels bubbling of the event. No other registered handlers for this event will be fired.
 */
JSJaCConnection.prototype.registerIQGet =
  function(childName, childNS, handler) {
  this.registerHandler('iq', childName, childNS, 'get', handler);
};

/**
 * Register for iq packets of type 'set'.
 * @param {String} childName A childnode's name that must occur within a
 * retrieved packet

 * @param {String} childNS A childnode's namespace that must occure within
 * a retrieved packet (works only if childName is given)

 * @param {Function} handler The handler to be called when event occurs. If your handler returns 'true' it cancels bubbling of the event. No other registered handlers for this event will be fired.
 */
JSJaCConnection.prototype.registerIQSet =
  function(childName, childNS, handler) {
  this.registerHandler('iq', childName, childNS, 'set', handler);
};

/**
 * Resumes this connection from saved state (cookie)
 * @return Whether resume was successful
 * @type boolean
 */
JSJaCConnection.prototype.resume = function() {
  try {
    this._setStatus('resuming');
    var s = unescape(JSJaCCookie.read('JSJaC_State').getValue());
     
    this.oDbg.log('read cookie: '+s,2);

    var o = JSJaCJSON.parse(s);
     
    for (var i in o)
      if (o.hasOwnProperty(i))
        this[i] = o[i];
     
    // copy keys - not being very generic here :-/
    if (this._keys) {
      this._keys2 = new JSJaCKeys();
      var u = this._keys2._getSuspendVars();
      for (var i=0; i<u.length; i++)
        this._keys2[u[i]] = this._keys[u[i]];
      this._keys = this._keys2;
    }

    try {
      JSJaCCookie.read('JSJaC_State').erase();
    } catch (e) {}

    if (this._connected) {
      // don't poll too fast!
      this._handleEvent('onresume');
      setTimeout(JSJaC.bind(this._resume, this),this.getPollInterval());
      this._interval = setInterval(JSJaC.bind(this._checkQueue, this),
				   JSJAC_CHECKQUEUEINTERVAL);
      this._inQto = setInterval(JSJaC.bind(this._checkInQ, this),
				JSJAC_CHECKINQUEUEINTERVAL);
    }

    return (this._connected === true);
  } catch (e) {
    if (e.message)
      this.oDbg.log("Resume failed: "+e.message, 1);
    else
      this.oDbg.log("Resume failed: "+e, 1);
    return false;
  }
};

/**
 * Sends a JSJaCPacket
 * @param {JSJaCPacket} packet  The packet to send
 * @param {Function}    cb      The callback to be called if there's a reply
 * to this packet (identified by id) [optional]
 * @param {Object}      arg     Arguments passed to the callback
 * (additionally to the packet received) [optional]
 * @return 'true' if sending was successfull, 'false' otherwise
 * @type boolean
 */
JSJaCConnection.prototype.send = function(packet,cb,arg) {
  if (!packet || !packet.pType) {
    this.oDbg.log("no packet: "+packet, 1);
    return false;
  }

  if (!this.connected())
    return false;

  // remember id for response if callback present
  if (cb) {
    if (!packet.getID())
      packet.setID('JSJaCID_'+this._ID++); // generate an ID

    // register callback with id
    this._registerPID(packet.getID(),cb,arg);
  }

  try {
    this._handleEvent(packet.pType()+'_out', packet);
    this._handleEvent("packet_out", packet);
    this._pQueue = this._pQueue.concat(packet.xml());
  } catch (e) {
    this.oDbg.log(e.toString(),1);
    return false;
  }

  return true;
};

/**
 * Sends an IQ packet. Has default handlers for each reply type.
 * Those maybe overriden by passing an appropriate handler.
 * @param {JSJaCIQPacket} iq - the iq packet to send
 * @param {Object} handlers - object with properties 'error_handler',
 *                            'result_handler' and 'default_handler'
 *                            with appropriate functions
 * @param {Object} arg - argument to handlers
 * @return 'true' if sending was successfull, 'false' otherwise
 * @type boolean
 */
JSJaCConnection.prototype.sendIQ = function(iq, handlers, arg) {
  if (!iq || iq.pType() != 'iq') {
    return false;
  }

  handlers = handlers || {};
  var error_handler = handlers.error_handler || function(aIq) {
    this.oDbg.log(iq.xml(), 1);
  };
 
  var result_handler = handlers.result_handler ||  function(aIq) {
    this.oDbg.log(aIq.xml(), 2);
  };
  // unsure, what's the use of this?
  var default_handler = handlers.default_handler || function(aIq) {
    this.oDbg.log(aIq.xml(), 2);
  };

  var iqHandler = function(aIq, arg) {
    switch (aIq.getType()) {
      case 'error':
      error_handler(aIq);
      break;
      case 'result':
      result_handler(aIq, arg);
      break;
      default: // may it be?
      default_handler(aIq, arg);
    }
  };
  return this.send(iq, iqHandler, arg);
};

/**
 * Sets polling interval for this connection
 * @param {int} millisecs Milliseconds to set timer to
 * @return effective interval this connection has been set to
 * @type int
 */
JSJaCConnection.prototype.setPollInterval = function(timerval) {
  if (timerval && !isNaN(timerval))
    this._timerval = timerval;
  return this._timerval;
};

/**
 * Returns current status of this connection
 * @return String to denote current state. One of
 * <ul>
 * <li>'initializing' ... well
 * <li>'connecting' if connect() was called
 * <li>'resuming' if resume() was called
 * <li>'processing' if it's about to operate as normal
 * <li>'onerror_fallback' if there was an error with the request object
 * <li>'protoerror_fallback' if there was an error at the http binding protocol flow (most likely that's where you interested in)
 * <li>'internal_server_error' in case of an internal server error
 * <li>'suspending' if suspend() is being called
 * <li>'aborted' if abort() was called
 * <li>'disconnecting' if disconnect() has been called
 * </ul>
 * @type String
 */
JSJaCConnection.prototype.status = function() { return this._status; };

/**
 * Suspsends this connection (saving state for later resume)
 */
JSJaCConnection.prototype.suspend = function() {
	
    // remove timers
    clearTimeout(this._timeout);
    clearInterval(this._interval);
    clearInterval(this._inQto);

    this._suspend();

    var u = ('_connected,_keys,_ID,_inQ,_pQueue,_regIDs,_errcnt,_inactivity,domain,username,resource,jid,fulljid,_sid,_httpbase,_timerval,_is_polling').split(',');
    u = u.concat(this._getSuspendVars());
    var s = new Object();

    for (var i=0; i<u.length; i++) {
      if (!this[u[i]]) continue; // hu? skip these!
      if (this[u[i]]._getSuspendVars) {
        var uo = this[u[i]]._getSuspendVars();
        var o = new Object();
        for (var j=0; j<uo.length; j++)
          o[uo[j]] = this[u[i]][uo[j]];
      } else
        var o = this[u[i]];

      s[u[i]] = o;
    }
    var c = new JSJaCCookie('JSJaC_State', escape(JSJaCJSON.toString(s)),
                            this._inactivity);
    this.oDbg.log("writing cookie: "+unescape(c.value)+"\n(length:"+
                  unescape(c.value).length+")",2);
    c.write();

    try {
      var c2 = JSJaCCookie.read('JSJaC_State');
      if (c.value != c2.value) {
        this.oDbg.log("Suspend failed writing cookie.\nRead: "+
                      unescape(JSJaCCookie.read('JSJaC_State')), 1);
        c.erase();
      }

      this._connected = false;

      this._setStatus('suspending');
    } catch (e) {
      this.oDbg.log("Failed reading cookie 'JSJaC_State': "+e.message);
    }

  };

/**
 * @private
 */
JSJaCConnection.prototype._abort = function() {
  clearTimeout(this._timeout); // remove timer

  clearInterval(this._inQto);
  clearInterval(this._interval);

  this._connected = false;

  this._setStatus('aborted');

  this.oDbg.log("Disconnected.",1);
  this._handleEvent('ondisconnect');
  this._handleEvent('onerror',
                    JSJaCError('500','cancel','service-unavailable'));
};

/**
 * @private
 */
JSJaCConnection.prototype._checkInQ = function() {
  for (var i=0; i<this._inQ.length && i<10; i++) {
    var item = this._inQ[0];
    this._inQ = this._inQ.slice(1,this._inQ.length);
    var packet = JSJaCPacket.wrapNode(item);

    if (!packet)
      return;

    this._handleEvent("packet_in", packet);

    if (packet.pType && !this._handlePID(packet)) {
      this._handleEvent(packet.pType()+'_in',packet);
      this._handleEvent(packet.pType(),packet);
    }
  }
};

/**
 * @private
 */
JSJaCConnection.prototype._checkQueue = function() {
  if (this._pQueue.length != 0)
    this._process();
  return true;
};

/**
 * @private
 */
JSJaCConnection.prototype._doAuth = function() {
  if (this.has_sasl && this.authtype == 'nonsasl')
    this.oDbg.log("Warning: SASL present but not used", 1);

  if (!this._doSASLAuth() &&
      !this._doLegacyAuth()) {
    this.oDbg.log("Auth failed for authtype "+this.authtype,1);
    this.disconnect();
    return false;
  }
  return true;
};

/**
 * @private
 */
JSJaCConnection.prototype._doInBandReg = function() {
  if (this.authtype == 'saslanon' || this.authtype == 'anonymous')
    return; // bullshit - no need to register if anonymous

  /* ***
   * In-Band Registration see JEP-0077
   */

  var iq = new JSJaCIQ();
  iq.setType('set');
  iq.setID('reg1');
  iq.appendNode("query", {xmlns: "jabber:iq:register"},
                [["username", this.username],
                 ["password", this.pass]]);

  this.send(iq,this._doInBandRegDone);
};

/**
 * @private
 */
JSJaCConnection.prototype._doInBandRegDone = function(iq) {
  if (iq && iq.getType() == 'error') { // we failed to register
    this.oDbg.log("registration failed for "+this.username,0);
    this._handleEvent('onerror',iq.getChild('error'));
    return;
  }

  this.oDbg.log(this.username + " registered succesfully",0);

  this._doAuth();
};

/**
 * @private
 */
JSJaCConnection.prototype._doLegacyAuth = function() {
  if (this.authtype != 'nonsasl' && this.authtype != 'anonymous')
    return false;

  /* ***
   * Non-SASL Authentication as described in JEP-0078
   */
  var iq = new JSJaCIQ();
  iq.setIQ(this.server,'get','auth1');
  iq.appendNode('query', {xmlns: 'jabber:iq:auth'},
                [['username', this.username]]);

  this.send(iq,this._doLegacyAuth2);
  return true;
};

/**
 * @private
 */
JSJaCConnection.prototype._doLegacyAuth2 = function(iq) {
  if (!iq || iq.getType() != 'result') {
    if (iq && iq.getType() == 'error')
      this._handleEvent('onerror',iq.getChild('error'));
    this.disconnect();
    return;
  }

  var use_digest = (iq.getChild('digest') != null);

  /* ***
   * Send authentication
   */
  var iq = new JSJaCIQ();
  iq.setIQ(this.server,'set','auth2');

  query = iq.appendNode('query', {xmlns: 'jabber:iq:auth'},
                        [['username', this.username],
                         ['resource', this.resource]]);

  if (use_digest) { // digest login
    query.appendChild(iq.buildNode('digest', {xmlns: 'jabber:iq:auth'},
                                   hex_sha1(this.streamid + this.pass)));
  } else if (this.allow_plain) { // use plaintext auth
    query.appendChild(iq.buildNode('password', {xmlns: 'jabber:iq:auth'},
                                   this.pass));
  } else {
    this.oDbg.log("no valid login mechanism found",1);
    this.disconnect();
    return false;
  }

  this.send(iq,this._doLegacyAuthDone);
};

/**
 * @private
 */
JSJaCConnection.prototype._doLegacyAuthDone = function(iq) {
  if (iq.getType() != 'result') { // auth' failed
    if (iq.getType() == 'error')
      this._handleEvent('onerror',iq.getChild('error'));
    this.disconnect();
  } else
    this._handleEvent('onconnect');
};

/**
 * @private
 */
JSJaCConnection.prototype._doSASLAuth = function() {
  if (this.authtype == 'nonsasl' || this.authtype == 'anonymous')
    return false;

  if (this.authtype == 'saslanon') {
    if (this.mechs['ANONYMOUS']) {
      this.oDbg.log("SASL using mechanism 'ANONYMOUS'",2);
      return this._sendRaw("<auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='ANONYMOUS'/>",
                           this._doSASLAuthDone);
    }
    this.oDbg.log("SASL ANONYMOUS requested but not supported",1);
  } else {
    if (this.mechs['DIGEST-MD5']) {
      this.oDbg.log("SASL using mechanism 'DIGEST-MD5'",2);
      return this._sendRaw("<auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='DIGEST-MD5'/>",
                           this._doSASLAuthDigestMd5S1);
    } else if (this.allow_plain && this.mechs['PLAIN']) {
      this.oDbg.log("SASL using mechanism 'PLAIN'",2);
      var authStr = this.username+'@'+
      this.domain+String.fromCharCode(0)+
      this.username+String.fromCharCode(0)+
      this.pass;
      this.oDbg.log("authenticating with '"+authStr+"'",2);
      authStr = btoa(authStr);
      return this._sendRaw("<auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='PLAIN'>"+authStr+"</auth>",
                           this._doSASLAuthDone);
    }
    this.oDbg.log("No SASL mechanism applied",1);
    this.authtype = 'nonsasl'; // fallback
  }
  return false;
};

/**
 * @private
 */
JSJaCConnection.prototype._doSASLAuthDigestMd5S1 = function(el) {
  if (el.nodeName != "challenge") {
    this.oDbg.log("challenge missing",1);
    this._handleEvent('onerror',JSJaCError('401','auth','not-authorized'));
    this.disconnect();
  } else {
    var challenge = atob(el.firstChild.nodeValue);
    this.oDbg.log("got challenge: "+challenge,2);
    this._nonce = challenge.substring(challenge.indexOf("nonce=")+7);
    this._nonce = this._nonce.substring(0,this._nonce.indexOf("\""));
    this.oDbg.log("nonce: "+this._nonce,2);
    if (this._nonce == '' || this._nonce.indexOf('\"') != -1) {
      this.oDbg.log("nonce not valid, aborting",1);
      this.disconnect();
      return;
    }

    this._digest_uri = "xmpp/";
    //     if (typeof(this.host) != 'undefined' && this.host != '') {
    //       this._digest-uri += this.host;
    //       if (typeof(this.port) != 'undefined' && this.port)
    //         this._digest-uri += ":" + this.port;
    //       this._digest-uri += '/';
    //     }
    this._digest_uri += this.domain;

    this._cnonce = cnonce(14);

    this._nc = '00000001';

    var A1 = str_md5(this.username+':'+this.domain+':'+this.pass)+
    ':'+this._nonce+':'+this._cnonce;

    var A2 = 'AUTHENTICATE:'+this._digest_uri;

    var response = hex_md5(hex_md5(A1)+':'+this._nonce+':'+this._nc+':'+
                           this._cnonce+':auth:'+hex_md5(A2));

    var rPlain = 'username="'+this.username+'",realm="'+this.domain+
    '",nonce="'+this._nonce+'",cnonce="'+this._cnonce+'",nc="'+this._nc+
    '",qop=auth,digest-uri="'+this._digest_uri+'",response="'+response+
    '",charset=utf-8';
   
    this.oDbg.log("response: "+rPlain,2);

    this._sendRaw("<response xmlns='urn:ietf:params:xml:ns:xmpp-sasl'>"+
                  binb2b64(str2binb(rPlain))+"</response>",
                  this._doSASLAuthDigestMd5S2);
  }
};

/**
 * @private
 */
JSJaCConnection.prototype._doSASLAuthDigestMd5S2 = function(el) {
  if (el.nodeName == 'failure') {
    if (el.xml)
      this.oDbg.log("auth error: "+el.xml,1);
    else
      this.oDbg.log("auth error",1);
    this._handleEvent('onerror',JSJaCError('401','auth','not-authorized'));
    this.disconnect();
    return;
  }

  var response = atob(el.firstChild.nodeValue);
  this.oDbg.log("response: "+response,2);

  var rspauth = response.substring(response.indexOf("rspauth=")+8);
  this.oDbg.log("rspauth: "+rspauth,2);

  var A1 = str_md5(this.username+':'+this.domain+':'+this.pass)+
  ':'+this._nonce+':'+this._cnonce;

  var A2 = ':'+this._digest_uri;

  var rsptest = hex_md5(hex_md5(A1)+':'+this._nonce+':'+this._nc+':'+
                        this._cnonce+':auth:'+hex_md5(A2));
  this.oDbg.log("rsptest: "+rsptest,2);

  if (rsptest != rspauth) {
    this.oDbg.log("SASL Digest-MD5: server repsonse with wrong rspauth",1);
    this.disconnect();
    return;
  }

  if (el.nodeName == 'success')
    this._reInitStream(this.domain, this._doStreamBind);
  else // some extra turn
    this._sendRaw("<response xmlns='urn:ietf:params:xml:ns:xmpp-sasl'/>",
                  this._doSASLAuthDone);
};

/**
 * @private
 */
JSJaCConnection.prototype._doSASLAuthDone = function (el) {
  if (el.nodeName != 'success') {
    this.oDbg.log("auth failed",1);
    this._handleEvent('onerror',JSJaCError('401','auth','not-authorized'));
    this.disconnect();
  } else
    this._reInitStream(this.domain, this._doStreamBind);
};

/**
 * @private
 */
JSJaCConnection.prototype._doStreamBind = function() {
  var iq = new JSJaCIQ();
  iq.setIQ(this.domain,'set','bind_1');
  iq.appendNode("bind", {xmlns: "urn:ietf:params:xml:ns:xmpp-bind"},
                [["resource", this.resource]]);
  this.oDbg.log(iq.xml());
  this.send(iq,this._doXMPPSess);
};

/**
 * @private
 */
JSJaCConnection.prototype._doXMPPSess = function(iq) {
  if (iq.getType() != 'result' || iq.getType() == 'error') { // failed
    this.disconnect();
    if (iq.getType() == 'error')
      this._handleEvent('onerror',iq.getChild('error'));
    return;
  }
 
  this.fulljid = iq.getChildVal("jid");
  this.jid = this.fulljid.substring(0,this.fulljid.lastIndexOf('/'));
 
  iq = new JSJaCIQ();
  iq.setIQ(this.domain,'set','sess_1');
  iq.appendNode("session", {xmlns: "urn:ietf:params:xml:ns:xmpp-session"},
                []);
  this.oDbg.log(iq.xml());
  this.send(iq,this._doXMPPSessDone);
};

/**
 * @private
 */
JSJaCConnection.prototype._doXMPPSessDone = function(iq) {
  if (iq.getType() != 'result' || iq.getType() == 'error') { // failed
    this.disconnect();
    if (iq.getType() == 'error')
      this._handleEvent('onerror',iq.getChild('error'));
    return;
  } else
    this._handleEvent('onconnect');
};
 
/**
 * @private
 */
JSJaCConnection.prototype._handleEvent = function(event,arg) {
  event = event.toLowerCase(); // don't be case-sensitive here
  this.oDbg.log("incoming event '"+event+"'",3);
  if (!this._events[event])
    return;
  this.oDbg.log("handling event '"+event+"'",2);
  for (var i=0;i<this._events[event].length; i++) {
    var aEvent = this._events[event][i];
    if (aEvent.handler) {
      try {
        if (arg) {
          if (arg.pType) { // it's a packet
            if ((!arg.getNode().hasChildNodes() && aEvent.childName != '*') ||
				(arg.getNode().hasChildNodes() &&
				 !arg.getChild(aEvent.childName, aEvent.childNS)))
              continue;
            if (aEvent.type != '*' &&
                arg.getType() != aEvent.type)
              continue;
            this.oDbg.log(aEvent.childName+"/"+aEvent.childNS+"/"+aEvent.type+" => match for handler "+aEvent.handler,3);
          }
          if (aEvent.handler.call(this,arg)) // handled!
            break;
        }
        else
          if (aEvent.handler.call(this)) // handled!
            break;
      } catch (e) { this.oDbg.log(aEvent.handler+"\n>>>"+e.name+": "+ e.message,1); }
    }
  }
};

/**
 * @private
 */
JSJaCConnection.prototype._handlePID = function(aJSJaCPacket) {
  if (!aJSJaCPacket.getID())
    return false;
  for (var i in this._regIDs) {
    if (this._regIDs.hasOwnProperty(i) &&
        this._regIDs[i] && i == aJSJaCPacket.getID()) {
      var pID = aJSJaCPacket.getID();
      this.oDbg.log("handling "+pID,3);
      try {
        if (this._regIDs[i].cb.call(this, aJSJaCPacket,this._regIDs[i].arg) === false) {
          // don't unregister
          return false;
        } else {
          this._unregisterPID(pID);
          return true;
        }
      } catch (e) {
        // broken handler?
        this.oDbg.log(e.name+": "+ e.message);
        this._unregisterPID(pID);
        return true;
      }
    }
  }
  return false;
};

/**
 * @private
 */
JSJaCConnection.prototype._handleResponse = function(req) {
  var rootEl = this._parseResponse(req);

  if (!rootEl)
    return;

  for (var i=0; i<rootEl.childNodes.length; i++) {
    if (this._sendRawCallbacks.length) {
      var cb = this._sendRawCallbacks[0];
      this._sendRawCallbacks = this._sendRawCallbacks.slice(1, this._sendRawCallbacks.length);
      cb.fn.call(this, rootEl.childNodes.item(i), cb.arg);
      continue;
    }
    this._inQ = this._inQ.concat(rootEl.childNodes.item(i));
  }
};

/**
 * @private
 */
JSJaCConnection.prototype._parseStreamFeatures = function(doc) {
  if (!doc) {
    this.oDbg.log("nothing to parse ... aborting",1);
    return false;
  }

  var errorTag;
  if (doc.getElementsByTagNameNS)
    errorTag = doc.getElementsByTagNameNS("http://etherx.jabber.org/streams", "error").item(0);
  else {
    var errors = doc.getElementsByTagName("error");
    for (var i=0; i<errors.length; i++)
      if (errors.item(i).namespaceURI == "http://etherx.jabber.org/streams") {
        errorTag = errors.item(i);
        break;
      }
  }

  if (errorTag) {
    this._setStatus("internal_server_error");
    clearTimeout(this._timeout); // remove timer
    clearInterval(this._interval);
    clearInterval(this._inQto);
    this._handleEvent('onerror',JSJaCError('503','cancel','session-terminate'));
    this._connected = false;
    this.oDbg.log("Disconnected.",1);
    this._handleEvent('ondisconnect');
    return false;
  }

  this.mechs = new Object();
  var lMec1 = doc.getElementsByTagName("mechanisms");
  this.has_sasl = false;
  for (var i=0; i<lMec1.length; i++)
    if (lMec1.item(i).getAttribute("xmlns") ==
        "urn:ietf:params:xml:ns:xmpp-sasl") {
      this.has_sasl=true;
      var lMec2 = lMec1.item(i).getElementsByTagName("mechanism");
      for (var j=0; j<lMec2.length; j++)
        this.mechs[lMec2.item(j).firstChild.nodeValue] = true;
      break;
    }
  if (this.has_sasl)
    this.oDbg.log("SASL detected",2);
  else {
    this.authtype = 'nonsasl';
    this.oDbg.log("No support for SASL detected",2);
  }

  /* [TODO]
   * check if in-band registration available
   * check for session and bind features
   */

  return true;
};

/**
 * @private
 */
JSJaCConnection.prototype._process = function(timerval) {
  if (!this.connected()) {
    this.oDbg.log("Connection lost ...",1);
    if (this._interval)
      clearInterval(this._interval);
    return;
  }

  this.setPollInterval(timerval);

  if (this._timeout)
    clearTimeout(this._timeout);

  var slot = this._getFreeSlot();

  if (slot < 0)
    return;

  if (typeof(this._req[slot]) != 'undefined' &&
      typeof(this._req[slot].r) != 'undefined' &&
      this._req[slot].r.readyState != 4) {
    this.oDbg.log("Slot "+slot+" is not ready");
    return;
  }
	
  if (!this.isPolling() && this._pQueue.length == 0 &&
      this._req[(slot+1)%2] && this._req[(slot+1)%2].r.readyState != 4) {
    this.oDbg.log("all slots busy, standby ...", 2);
    return;
  }

  if (!this.isPolling())
    this.oDbg.log("Found working slot at "+slot,2);

  this._req[slot] = this._setupRequest(true);

  /* setup onload handler for async send */
  this._req[slot].r.onreadystatechange = 
  JSJaC.bind(function() {
               if (!this.connected())
                 return;
               if (this._req[slot].r.readyState == 4) {
                 this._setStatus('processing');
                 this.oDbg.log("async recv: "+this._req[slot].r.responseText,4);
                 this._handleResponse(this._req[slot]);
                 // schedule next tick
                 if (this._pQueue.length) {
                   this._timeout = setTimeout(JSJaC.bind(this._process, this),100);
                 } else {
                   this.oDbg.log("scheduling next poll in "+this.getPollInterval()+
                                 " msec", 4);
                   this._timeout = setTimeout(JSJaC.bind(this._process, this),this.getPollInterval());
                 }
               }
             }, this);

  try {
    this._req[slot].r.onerror = 
      JSJaC.bind(function() {
                   if (!this.connected())
                     return;
                   this._errcnt++;
                   this.oDbg.log('XmlHttpRequest error ('+this._errcnt+')',1);
                   if (this._errcnt > JSJAC_ERR_COUNT) {
                     // abort
                     this._abort();
                     return false;
                   }
                   
                   this._setStatus('onerror_fallback');
			
                   // schedule next tick
                   setTimeout(JSJaC.bind(this._resume, this),this.getPollInterval());
                   return false;
                 }, this);
  } catch(e) { } // well ... no onerror property available, maybe we
  // can catch the error somewhere else ...

  var reqstr = this._getRequestString();

  if (typeof(this._rid) != 'undefined') // remember request id if any
    this._req[slot].rid = this._rid;

  this.oDbg.log("sending: " + reqstr,4);
  this._req[slot].r.send(reqstr);
};

/**
 * @private
 */
JSJaCConnection.prototype._registerPID = function(pID,cb,arg) {
  if (!pID || !cb)
    return false;
  this._regIDs[pID] = new Object();
  this._regIDs[pID].cb = cb;
  if (arg)
    this._regIDs[pID].arg = arg;
  this.oDbg.log("registered "+pID,3);
  return true;
};

/**
 * send empty request
 * waiting for stream id to be able to proceed with authentication
 * @private
 */
JSJaCConnection.prototype._sendEmpty = function JSJaCSendEmpty() {
  var slot = this._getFreeSlot();
  this._req[slot] = this._setupRequest(true);

  this._req[slot].r.onreadystatechange = 
  JSJaC.bind(function() {
               if (this._req[slot].r.readyState == 4) {
                 this.oDbg.log("async recv: "+this._req[slot].r.responseText,4);
                 this._getStreamID(slot); // handle response
               }
             },this);

  if (typeof(this._req[slot].r.onerror) != 'undefined') {
    this._req[slot].r.onerror = 
      JSJaC.bind(function(e) {
                   this.oDbg.log('XmlHttpRequest error',1);
                   return false;
                 }, this);
  }

  var reqstr = this._getRequestString();
  this.oDbg.log("sending: " + reqstr,4);
  this._req[slot].r.send(reqstr);
};

/**
 * @private
 */
JSJaCConnection.prototype._sendRaw = function(xml,cb,arg) {
  if (cb)
    this._sendRawCallbacks.push({fn: cb, arg: arg});
 
  this._pQueue.push(xml);
  this._process();

  return true;
};

/**
 * @private
 */
JSJaCConnection.prototype._setStatus = function(status) {
  if (!status || status == '')
    return;
  if (status != this._status) { // status changed!
    this._status = status;
    this._handleEvent('onstatuschanged', status);
    this._handleEvent('status_changed', status);
  }
};

/**
 * @private
 */
JSJaCConnection.prototype._unregisterPID = function(pID) {
  if (!this._regIDs[pID])
    return false;
  this._regIDs[pID] = null;
  this.oDbg.log("unregistered "+pID,3);
  return true;
};
