<?php
    include_once('pre.php');
    $request = HTTPRequest::instance();
    // Var initialization
	$username = $request->get('username');
	$pwd = $request->get('sessid');
	$host = $request->get('host');
	$conference_service = $request->get('cs');
	$room = $request->get('room');

	$group_id = $request->get('group_id');
	
	$hp = Codendi_HTMLPurifier::instance();
	
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<html>
  <head>
    <title>MUCkl</title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />

    <script src="../config.js.php?group_id=<?php echo $group_id; ?>"></script>
    <script src="config.js"></script>
    <script src="shared.js"></script>
    <script src="browsercheck.js"></script>
    <script src="sounds.js"></script>
    <script src="roster.js.php?group_id=<?php echo $group_id; ?>"></script>

<!-- JSJaC -->
    <script src="lib/jsjac/jsjac.js"></script>

<!-- Debugger -->
    <script src="lib/Debugger/Debugger.js"></script>

    <script>
<!--<![CDATA[

 /* ***
  * MUCkl, an easy to use, web based groupchat application.
  * Copyright (C) 2004-2007 Stefan Strigler <steve@zeank.in-berlin.de>
  *
  * This program is free software; you can redistribute it and/or
  * modify it under the terms of the GNU General Public License
  * as published by the Free Software Foundation; either version 2
  * of the License, or (at your option) any later version.
  *
  * This program is distributed in the hope that it will be useful,
  * but WITHOUT ANY WARRANTY; without even the implied warranty of
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with this program; if not, write to the Free Software
  * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
  *
  */

/************************************************************************
 *                       ******  GLOBAL WARS(tm) *******
 ************************************************************************
 */
var jid;
var pass;
var nick;
var status = '';
var onlstat = 'available';
var onlmsg = '';
var playSounds = false;

/* some globals */
var roster;

var onlstatus = new Object();
onlstatus["available"] = "online";
onlstatus["chat"] = "free for chat";
onlstatus["away"] = "away";
onlstatus["xa"] = "not available";
onlstatus["dnd"] = "do not disturb";
onlstatus["invisible"] = "invisible";
onlstatus["unavailable"] = "offline";

/************************************************************************
 * nifty helpers - always there if you need 'em
 ************************************************************************
 */

/* command line history */
var messageHistory = new Array();
var historyIndex = 0;
function getHistory(key, message) {
  if ((key == "up") && (historyIndex > 0)) historyIndex--;
  if ((key == "down") && (historyIndex < messageHistory.length)) historyIndex++;
  if (historyIndex >= messageHistory.length) {
    if (historyIndex == messageHistory.length) return '';
    return message;
  } else {
    return messageHistory[historyIndex];
  }
}

function addtoHistory(message) {
  if (is.ie5)
    messageHistory = messageHistory.concat(message);
  else
    messageHistory.push(message);
  historyIndex = messageHistory.length;
}

/* system sounds */
var soundPlaying = false;
function soundLoaded() {
  soundPlaying = false;
}

function playSound(action) {
  if (!playSounds)
    return;

  if(!SOUNDS[action]) {
    Debug.log("no sound for '" + action + "'",1);
    return;
  }

  if (onlstat != '' && onlstat != 'available' && onlstat != 'chat')
    return;

  if (soundPlaying)
    return;
  
  soundPlaying = true;
	
  var frameD = frames["jwc_sound"].document;

  var html = "<embed src=\""+SOUNDS[action]+"\" width=\"1\" height=\"1\" quality=\"high\" pluginspage=\"http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash\" type=\"application/x-shockwave-flash\">";
  frameD.open();
  frameD.write(html);
  frameD.close();
}

/************************************************************************
 *                       ******  CHANGESTATUS   *******
 ************************************************************************
 */

function sendPresence2Groupchats(gc,val,away) {
  var aPresence;
  for (var i=0; i<gc.length; i++) {
    aPresence = new JSJaCPresence();
    aPresence.setTo(gc[i]);
    if (away && away != '')
      aPresence.setStatus(away);
    if (val != 'available')
      aPresence.setShow(val);
    con.send(aPresence);
  }
}

function changeStatus(val,away) {
 
  Debug.log("changeStatus: "+val+","+away, 2);
  
  onlstat = val;
  if (away)
    onlmsg = away;

  sendPresence2Groupchats(roster.getGroupchats(),onlstat,onlmsg);
  return;

  // Ignore the rest of the function.
  //################################################################
  //		!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
  //################################################################


  if (!con.connected() && val != 'offline') {
    init();
    return;
  }

  var aPresence = new JSJaCPresence();
        
  switch(val) {
  case "unavailable":
    val = "invisible";
    aPresence.setType('invisible');
    break;
  case "offline":
    val = "unavailable";
    aPresence.setType('unavailable');
    con.send(aPresence);
    con.disconnect();
    cleanUp();
    return;
    break;
  case "available":
    val = 'available'; // needed for led in status bar
    if (away) {
      aPresence.setStatus(away);
    }
    aPresence.setPriority(8);
    break;
  case "chat":
    aPresence.setPriority(8);
  default:
    if (away) {
      aPresence.setStatus(away);
    }
    aPresence.setShow(val);
  }

  con.send(aPresence);

  // send presence to chatrooms
  if (typeof(roster) != 'undefined' && onlstat != 'invisible') {
    sendPresence2Groupchats(roster.getGroupchats(),onlstat,onlmsg);
  }
  
}

/************************************************************************
 *                   ***** EVENT - HANDLER *****
 ************************************************************************
 */


/************************************************************************
 * handleMessage
 ************************************************************************
 */
function handleMessage(aMessage) {

  Debug.log(aMessage.getDoc().xml,2);

  if (aMessage.getType() == 'error')
    return;

  var from = cutResource(aMessage.getFrom());
  var type = aMessage.getType();
  Debug.log("from: "+from+"\naMessage.getFrom(): "+aMessage.getFrom(),3);

  var user = roster.getUserByJID(from);
  if (user == null) {// users not in roster (yet)
    Debug.log("creating new user "+from,3);
    user = roster.addUser(new RosterUser(from));
  }

  Debug.log("got user jid: "+user.jid,3);

  // set current timestamp
  var x;
  for (var i=0; i<aMessage.getNode().getElementsByTagName('x').length; i++)
    if (aMessage.getNode().getElementsByTagName('x').item(i).getAttribute('xmlns') == 'jabber:x:delay') {
      x = aMessage.getNode().getElementsByTagName('x').item(i);
      break;
    }

  if (x) {
    Debug.log("found offline message: "+x.getAttribute('stamp'),3);
    var stamp = x.getAttribute('stamp');
    aMessage.jwcTimestamp = new Date(Date.UTC(stamp.substring(0,4),stamp.substring(4,6)-1,stamp.substring(6,8),stamp.substring(9,11),stamp.substring(12,14),stamp.substring(15,17)));
  } else
    aMessage.jwcTimestamp = new Date();


  // send message to frame
  user.chatmsgs = user.chatmsgs.concat(aMessage);
  if (user.chatW && !user.chatW.closed && user.chatW.srcW && typeof(user.chatW.srcW.roster) != 'undefined' && user.chatW.popMsgs) {
    user.chatW.popMsgs();
  }

}


/************************************************************************
 * handlePresence
 ************************************************************************
 */

function handlePresence(presence) {
  Debug.log(presence.getDoc().xml,2);

  var from = cutResource(presence.getFrom());
  var type = presence.getType();
  var show = presence.getShow();
  var status = presence.getStatus();

  var aRoster = roster;

  if (from == cutResource(jid)) // skip my own presence msgs
    return;

  var user = roster.getUserByJID(from);
  if (!user) // presence from unsubscribed user
    return;

  if (type == 'error') {

    if (user && user.chatW && !user.chatW.closed && user.chatW.putMsgHTML) {
      if (presence.getNode().getElementsByTagName('error').item(0)) {
        var error = presence.getNode().getElementsByTagName('error').item(0);
        if (error.getElementsByTagName('text').item(0))
          user.chatW.putMsgHTML(error.getElementsByTagName('text').item(0).firstChild.nodeValue,new Date(),from,null,true);
        else if (error.firstChild && error.firstChild.nodeValue)
          user.chatW.putMsgHTML(error.firstChild.nodeValue,new Date(),from,null,true);
        else if (error.firstChild.tagName == 'conflict') {
          user.chatW.putMsgHTML('<?php echo $hp->purify($GLOBALS['Language']->getText('plugin_im', 'muckl_connection_conflict'), CODENDI_PURIFIER_JS_QUOTE); ?>',new Date(),from,null,true);
        }
      }
    }
    return;
  }
  
  /* handle presence for MUC */
  var x;
  for (var i=0; i<presence.getNode().getElementsByTagName('x').length; i++)
    if (presence.getNode().getElementsByTagName('x').item(i).getAttribute('xmlns') == 'http://jabber.org/protocol/muc#user') {
      x = presence.getNode().getElementsByTagName('x').item(i);
      break;
    }

  if (user.roster && x) { 
    var ofrom = presence.getFrom().substring(presence.getFrom().indexOf('/')+1);

    Debug.log("jabber.from:"+presence.getFrom()+", ofrom:"+ofrom,3);
    
    var ouser = user.roster.getUserByJID(presence.getFrom());
    if (!ouser) // no user? create one!
      ouser = new GroupchatRosterUser(presence.getFrom(),ofrom);
    
    var item = x.getElementsByTagName('item').item(0);
                
    ouser.affiliation = item.getAttribute('affiliation');
    ouser.role = item.getAttribute('role');
    ouser.nick = item.getAttribute('nick');
    ouser.realjid = item.getAttribute('jid');
    if (item.getElementsByTagName('reason').item(0))
      ouser.reason = item.getElementsByTagName('reason').item(0).firstChild.nodeValue;
    if (actor = item.getElementsByTagName('actor').item(0)) {
      if (actor.getAttribute('jid') != null)
        ouser.actor = actor.getAttribute('jid');
      else if (item.getElementsByTagName('actor').item(0).firstChild != null)
        ouser.actor = item.getElementsByTagName('actor').item(0).firstChild.nodeValue;
    }	
    if (ouser.role != '') {
      ouser.add2Group(ouser.role+'s');
      
      /* check if it is our own presence
       * must be done here cause we want to be sure that role != ''
       */
      
      if (ouser.name == htmlEnc(user.roster.nick)) { // seems to be me
        user.roster.me = ouser; // store this reference
        if (user.chatW.updateMe)
          user.chatW.updateMe();
      }
    }

    Debug.log("ouser.jid: "+ ouser.jid + ", ouser.fulljid:" + ouser.fulljid + ", ouser.name:"+ouser.name+", user.roster.nick:"+user.roster.nick,3);

    var nickChanged = false;
    if (x.getElementsByTagName('status').item(0)) {
      var code = x.getElementsByTagName('status').item(0).getAttribute('code');
      switch (code) {
      case '201': // room created
        /* popup dialog to ask for whether to accept default
         * configuration or make a custom room 
         */
        if (confirm("A new room has been created but it awaits configuration from you. Do you want to do a custom configuration now?\nNote: Click on 'Cancel' to start with a default configuration!"))
          user.chatW.openConfig();
        else {
          var iq = new JSJaCIQ();
          iq.setType('set');
          iq.setTo(user.jid);
          var query = iq.setQuery('http://jabber.org/protocol/muc#owner');
          var x = query.appendChild(iq.getDoc().createElement('x'));
          x.setAttribute('xmlns','jabber:x:data');
          x.setAttribute('type','submit');
          
          con.send(iq);
        }
        break;
      case '303': // nick change
        // display message
        if (!ouser.nick)
          return;
        
        var aMessage = new JSJaCMessage();
        aMessage.setFrom(user.jid);
        aMessage.setBody(""+ouser.name+" <?php echo $hp->purify($GLOBALS['Language']->getText('plugin_im', 'muckl_isnowknownas'), CODENDI_PURIFIER_JS_QUOTE); ?> "+htmlEnc(ouser.nick));
        user.chatmsgs = user.chatmsgs.concat(aMessage);
        if (user.chatW && !user.chatW.closed && user.chatW.popMsgs)
          user.chatW.popMsgs();
        
        // update nick if it's me
        if (ouser.name == htmlEnc(user.roster.nick))
          user.roster.nick = ouser.nick;
        
        // remove old user
        var aChatW = ouser.chatW;
        user.roster.removeUser(ouser);
        
        // add new user
        ouser = new GroupchatRosterUser(presence.getFrom().substring(0,presence.getFrom().lastIndexOf('/')+1).concat(ouser.nick),ouser.nick);
        
        if (aChatW && !aChatW.closed) {
          ouser.chatW = aChatW;
          ouser.chatW.user = ouser;
        }
        user.roster.addUser(ouser);
        nickChanged = true;
        break;
      case '301': // user has been banned
        
        // check if it's me
        if (ouser.name == user.chatW.nick) {
          var ts = new Date();
          ac = hex_sha1(user.jid) + '=' + (ts.getTime() + (MAX_LOCK_MINS*60*1000));
          ts.setTime(ts.getTime() + (365*24*3600*1000));
          document.cookie = ac + '; expires='+ts.toGMTString();				 
        }
        
        var aMessage = new JSJaCMessage();
        aMessage.setFrom(user.jid);
        var body;
        if (ouser.actor)
          body = ""+ouser.name+" has been banned by "+ouser.actor;
        else
          body = ""+ouser.name+" has been banned";
        if (ouser.reason)
          body += ": " + ouser.reason;
        aMessage.setBody(body);
        user.chatmsgs = user.chatmsgs.concat(aMessage);
        if (user.chatW && !user.chatW.closed && user.chatW.popMsgs)
          user.chatW.popMsgs();			
        
        playSound('chat_recv');
        break;
      case '307': // user has been kicked
        
        // check if it's me
        if (ouser.name == user.chatW.nick) {
          var ts = new Date();
          var mins = DEFAULT_LOCK_MINS;
          if (ouser.reason && ouser.reason.match(/^min(utes)?s?:\s*(\d+)\s*/)) 
            mins = RegExp.$2;
          if (typeof(MAX_LOCK_MINS) != 'undefined' && mins > MAX_LOCK_MINS)
            mins = MAX_LOCK_MINS;
          ac = hex_sha1(user.jid) + '=' + (ts.getTime() + (mins*60*1000));
          ts.setTime(ts.getTime() + (365*24*3600*1000));
          document.cookie = ac + '; expires='+ts.toGMTString();
        }
        
        var aMessage = new JSJaCMessage();
        aMessage.setFrom(user.jid);
        var body;
        if (ouser.actor)
          body = ""+ouser.name+" has been kicked by "+ouser.actor;
        else
					body = ""+ouser.name+" has been kicked";
        if (ouser.reason)
          body += ": " + ouser.reason;
        aMessage.setBody(body);
        user.chatmsgs = user.chatmsgs.concat(aMessage);
        if (user.chatW && !user.chatW.closed && user.chatW.popMsgs)
          user.chatW.popMsgs();	
        
        playSound('chat_recv');
        break;
      }
    }
    
    Debug.log("<"+ouser.name+"> affiliation:"+ouser.affiliation+", role:"+ouser.role,3);
    
    if (!user.roster.getUserByJID(presence.getFrom()) && !nickChanged) {
      // add user
      user.roster.addUser(ouser);
      
      // show join message
      var aMessage = new JSJaCMessage();
      aMessage.setFrom(user.jid);
      aMessage.setBody(ouser.name+" <?php echo $hp->purify($GLOBALS['Language']->getText('plugin_im', 'muckl_hasbecomeavailable'), CODENDI_PURIFIER_JS_QUOTE); ?>");
      user.chatmsgs = user.chatmsgs.concat(aMessage);
      if (user.chatW && !user.chatW.closed && user.chatW.popMsgs)
        user.chatW.popMsgs();			
      
      playSound('online');
      
    } else if (presence.getType() == 'unavailable' && !nickChanged) {
      // show part message
      var aMessage = new JSJaCMessage();
      aMessage.setFrom(user.jid);
      var body = ""+ouser.name+" <?php echo $hp->purify($GLOBALS['Language']->getText('plugin_im', 'muckl_hasleft'), CODENDI_PURIFIER_JS_QUOTE); ?>";
      if (presence.getStatus())
        body += ": " + presence.getStatus();
      aMessage.setBody(body);
      user.chatmsgs = user.chatmsgs.concat(aMessage);
      if (user.chatW && !user.chatW.closed && user.chatW.popMsgs)
        user.chatW.popMsgs();			
      
      playSound('offline');
      
    } else
      user.roster.updateGroups();
    
    // relink roster and user
    aRoster = user.roster;
    user = ouser;
  } 

  if (show) {
    if (user.status == 'unavailable')
      playSound('online');
		if (show == 'online') // quick fix for JIT which sends this presence ...
			show = 'available';
    user.status = show;
  } else if (type) {
    if (type == 'unsubscribe') {
      user.subscription = 'from';
      user.status = 'stalker';
    } else if (user.status != 'stalker')
      user.status = 'unavailable';
    if (aRoster.name == 'GroupchatRoster' && !nickChanged) { // it's a groupchat roster
      // remove user
      if (!user.chatW || user.chatW.closed)
        aRoster.removeUser(user); // we don't need offline users in there
    }
    playSound('offline');
  } else {
    if (user.status == 'unavailable') // user was offline before
      playSound('online');
    user.status = 'available';
  }

  // show away message
  if (status)
    user.statusMsg = status;
  else
    user.statusMsg = null;

  // update presence indicator of chat window
  if (user.chatW && !user.chatW.closed && user.chatW.updateUserPresence) 
    user.chatW.updateUserPresence();
  
  aRoster.print(); // update roster
}


function handleConError(e) {
  switch (e.getAttribute('code')) {
  case '401':
    alert("Authorization failed");
    if (!con.connected())
      window.close();
    break;
  case '409':
    alert("Registration failed!\n\nPlease choose a different username!");
    break;
  case '503':
    if (!logoutCalled && onlstat != 'offline')
      alert("Service unavailable");
    break;
  case '500':
    if (!con.connected()  && !logoutCalled && onlstat != 'offline')
      if (confirm("Internal Server Error.\n\nDisconnected.\n\nReconnect?"))
        changeStatus(onlstat,onlmsg);
    break;
  default:
    alert("An Error Occured:\nCode: "+e.getAttribute('code')+"\nType: "+e.getAttribute('type')+"\nCondition: "+e.firstChild.nodeName); // this shouldn't happen :)
    break;
  }
}

function handleDisconnect() {
  if (logoutCalled || onlstat == 'offline')
    return;
  Debug.log("Disconnected");
}

var conTimer;
function handleConnected() {
  Debug.log("Connected",0);
  roster = new Roster();
  jid = con.jid;
  Debug.log("jid: "+jid);
  
  var aRoom = '<?php echo $hp->purify($room, CODENDI_PURIFIER_JS_QUOTE) ?>@<?php echo $hp->purify($conference_service, CODENDI_PURIFIER_JS_QUOTE) ?>.<?php echo $hp->purify($host, CODENDI_PURIFIER_JS_QUOTE) ?>';
/*
  if (passedArgs['room'] && ROOMS[passedArgs['room']])
    aRoom = ROOMS[passedArgs['room']].name+'@'+ROOMS[passedArgs['room']].server;
  else
    return; // should indicate an error here
*/
  
  var ac = getSecs(readCookie(hex_sha1(aRoom)));
  if (ac>0) {
    setTimeout("roster.openGroupchat('"+aRoom+"','"+nick+"');",ac*1000);
    //alert("I'm sorry but it seems you're locked out for "+ Math.round((ts.getTime()-now.getTime())/1000) + " seconds.\nPlease stand by, you'll be redirected automatically!");
    frames['chatW'].document.body.innerHTML = "<div class='infoBox'>Sorry, you're locked out for<br /> <span id='var_secs'></span>.<br />Please stand by, you'll be redirected automatically!</div>";
    updateVarSecs(ac);
  } else 
    roster.openGroupchat(aRoom, nick);
}

function updateVarSecs(ac) {
  var html = '';
  if (ac > 60)
    html = Math.floor(ac/60) + " minutes, ";
  html += (ac - Math.floor(ac/60)*60) + " seconds";
  if (frames['chatW'].document.getElementById('var_secs'))
    frames['chatW'].document.getElementById('var_secs').innerHTML = html;
  if (ac-- > 0)
    setTimeout("updateVarSecs("+ac+");",1000);
}

function getSecs(ts_millis) {
  var ts = new Date();
  ts.setTime(ts_millis);
  var now = new Date();
  
  return Math.round((ts.getTime()-now.getTime())/1000);
}

/************************************************************************
 *                       ******  END HANDLERS  ******* 
 ************************************************************************
 */


/************************************************************************
 *                           ******  INIT  *******
 ************************************************************************
 */
var con, Debug, srcW;
function init() {
  /* initialise debugger */
  if (!Debug || typeof(Debug) == 'undefined' || !Debug.start) {
    if (DEBUG && typeof(Debugger) != 'undefined')
      Debug = new Debugger(DEBUG_LVL,'MUCkl');
    else {
      Debug = new Object();
      Debug.log = function() {};
      Debug.start = function() {};
    }
  }
  Debug.start();
  
  if (typeof(AUTHTYPE) == 'undefined' || AUTHTYPE == "anonymous")
    Debug.log("jid: "+jid+"\npass: "+pass,2);
  else
    Debug.log("using sasl anonymous for login",2);
  
  /* set title */
  document.title = "MUCkl - " + nick;
  
  /* ***
   * create new connection
   */
  var oArg = {oDbg: Debug, httpbase: HTTPBASE, timerval: timerval};
  if (BACKENDTYPE=='polling')
    con = new JSJaCHttpPollingConnection(oArg);
  else if (BACKENDTYPE=='binding')
    con = new JSJaCHttpBindingConnection(oArg);
  else {
    alert('unknown backend type. aborting...');
    return;
  }
  
  /* register handlers */
  con.registerHandler('presence',handlePresence);
  con.registerHandler('message',handleMessage);
  con.registerHandler('ondisconnect',handleDisconnect);
  con.registerHandler('onconnect',handleConnected);
  con.registerHandler('onerror',handleConError);
  
  /* connect to remote */
  if (typeof(AUTHTYPE) == 'undefined' || AUTHTYPE!="saslanon")
    con.connect({domain: XMPPDOMAIN,username:jid.substring(0,jid.indexOf('@')),resource:jid.substring(jid.indexOf('/')+1),pass:pass});
  else {
    oArg = {domain: XMPPDOMAIN, authtype: AUTHTYPE, resource: 'MUCkl'};
    if (typeof(AUTHHOST) != 'undefined')
      oArg.authhost = AUTHHOST;
    con.connect(oArg);
  }
}

/************************************************************************
 *                       ******  LOGOUT  *******
 ************************************************************************
 */

function cleanUp() {
  /* close dependent windows */
  if (roster)
    roster.cleanUp();
  
  // clear frames
  if (frames['jwc_sound']) {
    frames["jwc_sound"].document.open();
    frames["jwc_sound"].document.write();
    frames["jwc_sound"].document.close();
  }
}

var logoutCalled = false;
function logout() {
  logoutCalled = true;
  cleanUp();
  
  if (!con.connected())
    return;
  
  var aPresence = new JSJaCPresence();
  aPresence.setType('unavailable');
  con.send(aPresence);
  
  con.disconnect();
}

/************************************************************************
 *                     ******  INITIALISE VARS  *******
 ************************************************************************
 */

/* check for unsupported browsers */
if (is.b == 'op' || 
    is.b == 'Konqueror' || 
    navigator.userAgent.indexOf('Safari') != -1 ||
    (is.b == 'ns' && is.v < '5') ||
    (is.ie && is.mac) ||
    is.ie4
    ) 
  {
    open("unsupported.html","unsupported","width=380,height=180");
  }


/* quick hack - need this info before onload */
/* get args */
getArgs();

if (typeof(AUTHTYPE) == 'undefined' || AUTHTYPE!='saslanon') {
  pass = '<?php echo $hp->purify($pwd, CODENDI_PURIFIER_JS_QUOTE) ?>';
  jid = '<?php echo $hp->purify($username, CODENDI_PURIFIER_JS_QUOTE) ?>'+'@'+'<?php echo $hp->purify($host, CODENDI_PURIFIER_JS_QUOTE) ?>';
}


nick = '<?php echo $hp->purify($username, CODENDI_PURIFIER_JS_QUOTE) ?>';

if (nick && nick.match(/^\s*(\S+|\S+.*\S+)\s*$/))
  // skip blanks
  nick = RegExp.$1;
else {
  alert("Nickname missing or invalid!\nUsing default nick...");
  nick = 'muckl'; // make sure to use some predefined default
}

// create a unique resource
if (typeof(ANONHOST) == 'undefined') 
  jid += '/' + hex_sha1(navigator.userAgent + Date.UTC(new Date()) + nick);

/* get style */
if (opener && opener.myStyle)
  stylesheet = THEMESDIR + "/" + opener.myStyle + "/" + stylesheet;
else if (passedArgs['myStyle']) 
  stylesheet = THEMESDIR + "/" + passedArgs['myStyle'] + "/" + stylesheet;

onload = init;
onunload = logout;

//]]>-->
    </script>
  </head>
  <frameset rows="100%,0,0,0,0" border="0">
    <frame src="empty.html" name="chatW" marginwidth="0" marginheight="0" scrolling="no" />
    <frame src="empty.html" name="jwc_sound" marginwidth="0" marginheight="0" onLoad="soundLoaded();" />
  </frameset>
  <body>
    Your browser must support frames and javascript to use this application.
  </body>
</html>
