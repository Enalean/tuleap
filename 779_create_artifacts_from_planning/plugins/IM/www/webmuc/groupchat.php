<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>

<?php
require_once('pre.php');

$group_id = $request->get('group_id');
?>

  <head>
    <title>JWChat - Groupchat</title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <script src="shared.js"></script>
    <script src="browsercheck.js"></script>
    <script src="emoticons.js"></script>
    <script src="../config.js.php?group_id=<?php echo $group_id; ?>"></script>
    <script src="config.js"></script>

    <script src="switchStyle.js"></script>

    <script src="roster.js.php?group_id=<?php echo $group_id; ?>"></script>

    <script src="lib/jsjac/jsjac.js"></script>
    <script>

function submitClicked() {
  if (!clearedEls['msgbox'])
    return false;

  var body = document.getElementById("msgbox").value;
  var to = ''; 

  if (body == '') // don't send empty message
    return false;

  var aMessage = new JSJaCMessage();
  if (frames['groupchatIRoster'].userSelected) {
    to = frames['groupchatIRoster'].userSelected.getAttribute('username');
    var jid = roster.getFullJIDByNick(htmlFullEnc(to));
    if (jid == null) {
      putMsgHTML("No such nick"+": " + htmlFullEnc(to), new Date().toLocaleTimeString(), group);

      // unselect user
      if (frames['groupchatIRoster'].userSelected)
        frames['groupchatIRoster'].userSelected.className = "rosterUser";;
      frames['groupchatIRoster'].userSelected = null;;
      document.getElementById('send_to_label').innerHTML = '';

      srcW.addtoHistory(body);
      document.getElementById('msgbox').value=''; // empty box
      document.getElementById('msgbox').focus(); // set focus back on input field
      return;
    } else {
      aMessage.setType('chat');
      aMessage.setTo(jid);
    }
  } else {
    aMessage.setType('groupchat');
    aMessage.setTo(group);
  }
	
  /* handle commands */
  if (body.match(/^\/say (.+)/)) {

    /* *** say *** */

    body = RegExp.$1;
    aMessage.setBody(body);
    srcW.con.send(aMessage);
  } else if (body.match(/^\/clear/)) {

    /* *** clear *** */

    cFrame.body.innerHTML = '';
  } else if (body.match(/^\/nick (.+)/)) {

    /* *** nick *** */

    var nick2 = body.replace(/^\/nick (.+)/,"$1");
    var aPresence = new JSJaCPresence();
    aPresence.setTo(group+"/"+nick2);
    srcW.con.send(aPresence);
  } else if (body.match(/^\/topic (.+)/)) {

    /* *** topic *** */

    var topic = body.replace(/^\/topic (.+)/,"$1");
    aMessage.setType('groupchat');
    aMessage.setSubject(topic);
    srcW.con.send(aMessage);
  } else if (body.match(/^\/ban (\S+)\s*(.*)/)) {

    /* *** ban *** */

    var nick2 = RegExp.$1;
    var reason = RegExp.$2;

    var jid = roster.getFullJIDByNick(nick2);
    if (jid == null) {
      putMsgHTML("No such nick"+": " + nick2, new Date().toLocaleTimeString(), group);
    } else {
      changeAffiliation(jid,'outcast',false,reason);
    }
  } else if (body.match(/^\/kick (\S+)\s*(.*)/)) {

    /* *** kick *** */

    var nick2 = RegExp.$1;
    var reason = RegExp.$2;

    var jid = roster.getFullJIDByNick(nick2);
    if (jid == null) {
      putMsgHTML("No such nick"+": " + nick2, new Date().toLocaleTimeString(),group);
    } else {
      changeRole(jid,'none',false,reason);
    }
  } else if (body.match(/^\/join (\S+)\s*(.*)/)) {

    /* *** join *** */

    var room = RegExp.$1;
    var pass = RegExp.$2;
    srcW.roster.openGroupchat(room,nick,pass);
  } else if (body.match(/^\/msg (\S+)\s*(.*)/)) {

    /* *** msg *** */

    to = RegExp.$1;
    var body = RegExp.$2;

    var jid = roster.getFullJIDByNick(to);
    if (jid == null)
      putMsgHTML("No such nick"+": " + to, new Date().toLocaleTimeString(), group);
    else {
      aMessage.setType('chat');
      aMessage.setTo(jid);
      aMessage.setBody(body);
      srcW.con.send(aMessage);
    }
  } else if (body.match(/^\/part\s*(.*)/)) {

    /* *** part *** */

    var msg = RegExp.$1;
    var aPresence = new JSJaCPresence();
    aPresence.setTo(group);
    aPresence.setType('unavailable');
    if (msg && msg != '')
      aPresence.setStatus(msg);
    srcW.con.send(aPresence);
  } else if (body.match(/^\/help/)) {

    /* *** help *** */

    open("http://www.jabber.org/jeps/jep-0045.html#impl-client-irc");
  } else {
    aMessage.setBody(body);
    srcW.con.send(aMessage);
  }			

  if (aMessage.getType() == 'chat') { // a private message we have to putMsgHTML ourself
    putMsgHTML(aMessage.getBody(),new Date().toLocaleTimeString(),to,'purple',null,'to');
  }

  // add message to our message history
  srcW.addtoHistory(body);
  document.getElementById('msgbox').value=''; // empty box
  document.getElementById('msgbox').focus(); // set focus back on input field
  
  return false;
}

function partRoom() {
  if (confirm('Are you sure you want to leave this chat room?')) 
    if (parent.document.referrer)
      return parent.location.replace(parent.document.referrer);
    else
      return parent.location.replace('index.html');
}

var colors = new Array('maroon','green','olive','navy','purple','teal','red','blue');

function putMsgHTML(msg,mtime,user,usercolor,err,type) {
  var msgHTML = '';

  msgHTML += "<div>";    

  if (msg.match(/^\/me /)) {
    msg = msgFormat(msg);

    msg = msg.replace(/^\/me /,"<span class='meMsg'>*&nbsp;<span class='chatUser' username=\""+htmlFullEnc(user)+"\" title='@ "+mtime+"'>"+htmlEnc(user)+"</span></span>&nbsp;");
  } else if (user && user != group) {
    msg = msgFormat(msg);

    if (type == 'chat' || type == 'to') { // a private message
      msgHTML += "<span class='privMsg'>[";
      msgHTML += "<span class='chatUser' username=\""+htmlFullEnc(user)+"\" title=\"@ "+mtime+"\">" + htmlEnc(user) + "</span>";
      if (type == 'to')
        msgHTML += "&laquo;";
      else
        msgHTML += "&raquo;";
      msgHTML += "]</span>&nbsp;";
    } else
      msgHTML += "<span style=\"color:"+usercolor+";\" class='chatUser' username=\""+htmlFullEnc(user)+"\" title='@ "+mtime+"'>&lt;" + htmlEnc(user) + "&gt;</span>&nbsp;";
  }

  if (user == group) {/* channel status messages */
    if (err)
      msgHTML += "<span style=\"font-weight:bold;color:red;\">"+msg+"</span>";
    else
      msgHTML += "<span style=\"font-weight:bold;\">"+msg+"</span>";
  } else {

    if (user != nick && meRegExp.test(msg) && !notHREFMeRegExp.test(msg))
      msgHTML += msg.replace(meRegExp,"<span class='highlighted'>$1</span>");
    else 
      msgHTML += msg;
  }

  msgHTML += "</div>";
  
  scroll_bottom = false;
  if (cFrame.body.scrollTop+cFrame.body.clientHeight >= cFrame.body.scrollHeight)
    scroll_bottom = true;

  cFrame.body.innerHTML += msgHTML;

  if (scroll_bottom)
    frames.groupchatIChat.scrollTo(0,cFrame.body.scrollHeight);
}

function popMsgs() {
  if (!user) 
    user = srcW.roster.getUserByJID(group);

  while (user.chatmsgs.length>0) {
    var msg;
    if (is.ie5||is.op) {
      msg = user.chatmsgs[0];
      user.chatmsgs = user.chatmsgs.slice(1,user.chatmsgs.length);
    } else
      msg = user.chatmsgs.shift();

    var from = msg.getFrom();
    if (msg.getFrom().indexOf('/') != -1)
      from = msg.getFrom().substring(msg.getFrom().indexOf('/')+1);

    /* get date */
    var timestamp;
    if (msg.jwcTimestamp)
      timestamp = msg.jwcTimestamp;
    else
      timestamp = new Date();

    var mtime = '';
    if (new Date() - timestamp > 24*3600*1000)
      mtime += timestamp.toLocaleDateString() + " ";
		
    mtime += timestamp.toLocaleTimeString();
    
    /* look for a subject */
    if (msg.getType() == 'groupchat' && msg.getSubject()) { // set topic
      user.roster.subject = msg.getSubject();
      document.getElementById('room_topic').innerHTML = htmlEnc(msg.getSubject());
      putMsgHTML("/me <?php echo $GLOBALS['Language']->getText('plugin_im', 'muckl_hassetthetopicto'); ?> "+msg.getSubject(), mtime, from);
      return;
    }

    if(!msg.getBody() || msg.getBody() == '')
      return;
	
    srcW.playSound('chat_recv');

    /* calculate color */
    var charSum = 0;
    for (var i=0; i<from.length; i++)
      charSum += from.charCodeAt(i);

    putMsgHTML(msg.getBody(),mtime,from,colors[charSum%(colors.length)],null,msg.getType());	
  } /* end while */

}

function displayTimestamp() {
  var tstyle;
  if (is.ie) {
    tstyle = cFrame.styleSheets('timestampstyle');
    tstyle.disabled = srcW.timestamps;
  } else {
    tstyle = cFrame.getElementById("timestampstyle");
    tstyle.sheet.disabled = srcW.timestamps;
  }
}

function updateMe() {
  document.getElementById('submit_button').disabled = (roster.me.role == 'none');

  if (roster.me.role == 'none') {// seems we left
    cFrame.body.innerHTML += "<span style='color:red';>"+"Disconnected."+"</span><br>";
    groupchatIChat.scrollTo(0,cFrame.body.scrollHeight);
  }
}

function changeUserStat(jid,stat,val,confirm,reason) {
  var user = roster.getUserByJID(jid);
  var iq = new JSJaCIQ();
  iq.setType('set');
  iq.setTo(group);

  var query = iq.setQuery('http://jabber.org/protocol/muc#admin');
  var item = query.appendChild(iq.getDoc().createElement('item'));
  item.setAttribute('nick',user.name);

  item.setAttribute(stat,val);
	
  if (reason || (confirm && (reason = prompt("Reason","")) != ''))
    item.appendChild(iq.getDoc().createElement('reason')).appendChild(iq.getDoc().createTextNode(reason));

  me = this;
  srcW.con.send(iq,me.handleError);
}

function handleError(iq) {
  // handle error
  if (iq && iq.getType() == 'error') {
    var error = iq.getNode().getElementsByTagName('error').item(0);
    if (error) {
      var msg = '';
      for (var i=0; i<error.childNodes.length; i++) {
        switch (error.childNodes.item(i).nodeName) {
        case 'not-allowed':
          putMsgHTML("Not Allowed",new Date(),group,null,true);
          break;
        case 'forbidden':
          putMsgHTML("Forbidden",new Date(),group,null,true);
          break;
        case 'item-not-found':
          putMsgHTML("Not Found",new Date(),group,null,true);
          break;
        default:
          putMsgHTML(error.childNodes.item(i).nodeName,new Date(),group,null,true);
          break;
        }
      }
    }
  }
}

function changeRole(jid,role,confirm,reason) {
  changeUserStat(jid,"role",role,confirm,reason);
}

function changeAffiliation(jid,affil,confirm,reason) {
  changeUserStat(jid,"affiliation",affil,confirm);
}

var soundOnImg = new Image();
soundOnImg.src = 'images/stock_volume.png';
var soundOffImg = new Image();
soundOffImg.src = 'images/stock_volume-mute.png';

function toggleSound(aImg) {
  if (parent.playSounds)
    aImg.src = soundOffImg.src;
  else
    aImg.src = soundOnImg.src;
  parent.playSounds = !parent.playSounds;
}

function part() {
  if (srcW.con) {
    var presence = new JSJaCPresence();
    presence.setType('unavailable');
    presence.setTo(group);
    srcW.con.send(presence);
  }

  if (user && !user.messages.length && !user.chatmsgs.length) {
    srcW.roster.removeUser(user);
  }
}

/* global vars */
var srcW,user,roster,cFrame,jid,nick,pass,meRegExp,notHREFMeRegExp;

/* event handler */
function init() {
  getArgs();

  srcW = parent;

  jid = passedArgs['jid'];
  group = jid;

  if (typeof(passedArgs['nick']) != 'undefined')
    nick = passedArgs['nick'];
  if(typeof(nick) == 'undefined' || nick == '')
    nick = srcW.roster.nick; // guess a nick

  meRegExp = new RegExp("\\b("+nick+")\\b","i");
  notHREFMeRegExp = new RegExp("href=\"\\S*\\b"+nick+"\\b\\S*\"","i");

  if (passedArgs['pass'] != 'undefined')
    pass = passedArgs['pass'];
 
  if (!srcW.con)
    return;

  // send presence
  var aPresence = new JSJaCPresence();
  aPresence.setTo(group+'/'+nick);
  aPresence.setFrom(srcW.jid);

  var x = aPresence.getDoc().createElement('x');
  x.setAttribute('xmlns','http://jabber.org/protocol/muc');
  if (typeof(pass) != 'undefined' && pass != '')
    x.appendChild(aPresence.getDoc().createElement('password')).appendChild(aPresence.getDoc().createTextNode(pass));
  if (CONFERENCENOHIST)
    x.appendChild(aPresence.getDoc().createElement('history')).setAttribute('maxchars','0');
	
  aPresence.getNode().appendChild(x);

  if (srcW.onlstat != 'available' && srcW.onlstat != 'invisible')
    aPresence.setShow(srcW.onlstat);

  if (srcW.onlmsg != '')
    aPresence.setStatus(srcW.onlmsg);

  srcW.con.send(aPresence);
  
  cFrame = groupchatIChat.document;

  user = srcW.roster.getUserByJID(group);
  if(!user) {
    user = srcW.roster.addUser(new RosterUser(group,'',["Chat Rooms"],group.substring(0,group.indexOf('@'))));
    user.chatW = window.self;
  }
  user.status = 'available';
  
  user.roster = new GroupchatRoster(window.self);
  user.roster.nick = nick; // remember my nickname
  roster = user.roster;
  //        user.roster.print();
  
  //  document.title += " - " + group;
  //  document.title = group+'/'+nick;

  // do we have a room logo?
  var have_logo = false;
  for (var i in ROOMS) {
    if (ROOMS[i].server == jid.substring(jid.indexOf('@')+1) &&
        ROOMS[i].name == jid.substring(0,jid.indexOf('@')) &&
        ROOMS[i].logo)
      {
        have_logo = true;
        var logo = new Image();
        logo.src = ROOMS[i].logo;
        try {
          document.getElementById('room_name').appendChild(logo);
        } catch (e) {  }
        break;
      }
  }
  if (!have_logo)
    document.getElementById('room_name').innerHTML = group.substring(0,group.indexOf('@'));

  // toggle sound for init
  parent.playSounds = !parent.playSounds;
  toggleSound(document.getElementById('toggle_sound_button'));

  popMsgs();
  displayTimestamp();
  document.onclick = frames['groupchatIRoster'].selectUser;
}

function setStatus(el) {
  srcW.changeStatus(el.value, srcW.onlmsg);
}

function setStatusMessage(el) {
  srcW.changeStatus(srcW.onlstat,el.value);
}

var clearedEls = new Array();
function clearOnFirstFocus(el) {
  if (clearedEls[el.id])
    return;
		
  el.value = '';
  clearedEls[el.id] = true;
}
		

function msgboxKeyPressed(el,e) {
  var keycode;
  if (window.event) { e  = window.event; keycode = window.event.keyCode; }
  else if (e) keycode = e.keyCode;
  else return true;

  switch (keycode) {
  case 9: // tab
    var txt = document.getElementById('msgbox');
    var pos1, pos2;
    var part;
    var possibilities = new Array();
    if(is.ie)
      return false;
    else {
      pos1 = txt.selectionStart; // current cursor position

      // no selection, not at the beginning of the line and at the end of a word or at the end of the line
      if(pos1 == txt.selectionEnd && pos1 > 0 && (txt.value.substring(pos1, pos1+1) == ' ' || pos1 == txt.value.length)) { 
        part = txt.value.substring(0, pos1);
        pos2 = part.lastIndexOf(" ") + 1;
        if(pos2 != -1)
          part = part.substring(pos2, pos1);
      }
    }
    if(part) {
      for (i in roster.users) {
        if(roster.users[i].name.indexOf(part) == 0)
          possibilities.push(roster.users[i].name);
      }
      if(possibilities.length == 1) { // complete, if only one possibility has been found or enumerate possibilities
        if(pos2 == 0) //special case: beginning of line, add additional ":"
          txt.value = txt.value.substring(0, pos2) + possibilities.pop() + ": " + txt.value.substring(pos1, txt.value.length)
          else
            txt.value = txt.value.substring(0, pos2) + possibilities.pop() + " " + txt.value.substring(pos1, txt.value.length)
              return false;
      }
      else if(possibilities.length > 1) {
        var string = possibilities.join(" ");
        putMsgHTML(string, new Date().toLocaleTimeString(), jid);
        return false;
      }
           
    }
    return true;

  case 13:
    if (!e.shiftKey && !e.ctrlKey)
      return submitClicked();
    break;
  }
  return true;
}

function msgboxKeyDown(el,e) {
  var keycode;
  if (window.event) { e  = window.event; keycode = window.event.keyCode; }
  else if (e) keycode = e.which;
  else return true;

  switch (keycode) {

  case 38:				// shift+up
    if (e.ctrlKey) {
      el.value = srcW.getHistory('up', el.value);
      el.focus(); el.select();
    }
    break;
  case 40:				// shift+down 
    if (e.ctrlKey) {
      el.value = srcW.getHistory('down', el.value);
      el.focus(); el.select();
    }
    break;
  case 76:
    if (e.ctrlKey) {   // ctrl+l
      cFrame.body.innerHTML = '';
      return false;
    }
    break;
  }
  return true;
}

function cancelEvent(e) {
  if (window.event) { e = window.event; }
  e.returnValue = false;
  return false;
}

onload = init;
onunload = part;
    </script>

  </head>

  <body>
    <table border=0 width="100%" height="100%" cellspacing=0 cellpadding=0>
        <tr> <!-- topic -->
          <td colspan=2 class="spaced" style="padding-right: 8px;">
                <span
              id="room_name" title="<?php echo $GLOBALS['Language']->getText('plugin_im', 'muckl_alt_roomname'); ?>"></span><br><span
              class="room_topic" title="<?php echo $GLOBALS['Language']->getText('plugin_im', 'muckl_alt_roomtopic'); ?>"><?php echo $GLOBALS['Language']->getText('plugin_im', 'muckl_topic'); ?>
              <span id="room_topic"></span></span></td>
        </tr>
        <tr>
          <td height="100%" width="100%" class="spaced">
            <table width="100%" height="100%" border=0 cellspacing=0 cellpadding=0>
                <tr>
                  <td><img src="images/border_corner_topleft.gif"
                      width=7 height=7></td>
                  <td><img src="images/border_top.gif" width="100%"
                      height=7></td>
                  <td><img src="images/border_corner_topright.gif"
                      width=8 height=7></td>
                </tr>
                <tr>
                  <td><img src="images/border_left.gif" height="100%"
                      width=7></td>
                  <td width="100%" height="100%"><iframe
                      id="groupchatIChat" name="groupchatIChat"
                      src="groupchat_ichat.html" scrolling="auto"
                      style="width:100%;height:100%;" class="gcIframe"
                      frameborder="0"></iframe></td>
                  <td><img src="images/border_right.gif" height="100%"
                      width=8></td>
                </tr>
                <tr>
                  <td><img src="images/border_corner_bottomleft.gif"
                      width=7 height=7></td>
                  <td><img src="images/border_bottom.gif" width="100%"
                      height=7></td>
                  <td><img src="images/border_corner_bottomright.gif"
                      width=8 height=7></td>
                  <td></td>
                </tr>
            </table>
          </td>
          <td height="100%" width="120" class="spaced"
            style="padding-right:8px;">
            <table width="120" height="100%" border=0 cellspacing=0 cellpadding=0>
                <tr>
                  <td><img src="images/border_corner_topleft.gif"
                      width=7 height=7></td>
                  <td><img src="images/border_top.gif" width="100%"
                      height=7></td>
                  <td><img src="images/border_corner_topright.gif"
                      width=8 height=7></td>
                </tr>
                <tr>
                  <td><img src="images/border_left.gif" height="100%"
                      width=7></td>
                  <td width="120" height="100%"><iframe
                      src="groupchat_iroster.html"
                      id="groupchatIRoster" name="groupchatIRoster"
                      scrolling="auto"
                      style="height:100%;width:120px;"
                      class="gcIframe" frameborder="0"></iframe></td>
                  <td><img src="images/border_right.gif" height="100%"
                      width=8></td>
                </tr>
                <tr>
                  <td><img src="images/border_corner_bottomleft.gif"
                      width=7 height=7></td>
                  <td><img src="images/border_bottom.gif" width="100%"
                      height=7></td>
                  <td><img src="images/border_corner_bottomright.gif"
                      width=8 height=7></td>
                  <td></td>
                </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td class="spaced">
            <table border=0 cellspacing=0 cellpadding=0 style="margin-left:8px;margin-right:8px;" id="inputarea">
                <tr>
                  <td><img src="images/border_corner_topleft.gif"
                      width=7 height=7></td>
                  <td colspan=2><img src="images/border_top.gif"
                      width="100%" height=7></td>
                  <td><img src="images/border_corner_topright.gif"
                      width=8 height=7></td>
                  <td rowspan=3><img src="<?php echo $GLOBALS['Language']->getText('plugin_im', 'muckl_send_button_img'); ?>"
                      width=101 height=29 
                      onClick="return submitClicked();" 
                      id='submit_button'
                      title="<?php echo $GLOBALS['Language']->getText('plugin_im', 'muckl_alt_sendbutton'); ?>"></td>
                </tr>
                <tr>
                  <td><img src="images/border_left.gif" height="100%"
                      width=7></td>
                  <td id="send_to_label"
                    style="background-color:white;" nowrap></td>
                  <td width="100%" height="100%"
                    style="background-color:white;"><input type="text"
                      id="msgbox" style="width:100%;" tabindex=1
                      onKeyPress="return msgboxKeyPressed(this,event);" 
                      onKeyDown="return msgboxKeyDown(this,event);"
                      onFocus="clearOnFirstFocus(this);" 
                      value="<?php echo $GLOBALS['Language']->getText('plugin_im', 'muckl_help_startwriting'); ?>"></td>
                  <td><img src="images/border_right.gif" height="100%"
                      width=8></td>
                </tr>
                <tr>
                  <td><img src="images/border_corner_bottomleft.gif"
                      width=7 height=7></td>
                  <td colspan=2><img src="images/border_bottom.gif"
                      width="100%" height=7></td>
                  <td><img src="images/border_corner_bottomright.gif"
                      width=8 height=7></td>
                </tr>
            </table>
          </td>
          <td align=right style="padding-right:7px;"></td>
        </tr>
        <tr>
          <td colspan=2 style="padding-top: 8px;">
            <table width="100%" id="toolbar" border=0 cellspacing=0 cellpadding=0>
                <tr>
                  <td style="padding-left: 6px;" width="50%"><table
                      width="100%" border=0 cellspacing=0
                      cellpadding=0><tr><td nowrap><label
                              for="status_selector"><?php echo $GLOBALS['Language']->getText('plugin_im', 'muckl_status'); ?>
                            </label><select id="status_selector"
                              onChange="setStatus(this);">
                              <option value="available"
                                class="user_available"><?php echo $GLOBALS['Language']->getText('plugin_im', 'muckl_status_online'); ?></option>
                              <option value="chat"
                                class="user_chat"><?php echo $GLOBALS['Language']->getText('plugin_im', 'muckl_status_free'); ?></option>
                              <option value="away"
                                class="user_away"><?php echo $GLOBALS['Language']->getText('plugin_im', 'muckl_status_away'); ?></option>
                              <option value="xa" class="user_xa"><?php echo $GLOBALS['Language']->getText('plugin_im', 'muckl_status_notavailable'); ?></option>
                              <option value="dnd" class="user_dnd"><?php echo $GLOBALS['Language']->getText('plugin_im', 'muckl_status_donotdisturb'); ?></option>
                              <!--option value="offline" class="user_unavailable">Offline</option-->
                            </select></td><td width="100%"
                                            style="padding-left:
                                            4px;"><input type="text"
                              id="status_message" style="width:100%;"
                              onChange="setStatusMessage(this);"
                              title="<?php echo $GLOBALS['Language']->getText('plugin_im', 'muckl_alt_statusmessage'); ?>"
                              onFocus="clearOnFirstFocus(this);"
                              value="<?php echo $GLOBALS['Language']->getText('plugin_im', 'muckl_statusmessage'); ?>"></td></tr></table></td>
                  <td style="padding-right:6px;" align=right><img
                      src="images/stock_volume.png" width=16 height=16
                      id="toggle_sound_button" alt="Sound"
                      align=middle title="<?php echo $GLOBALS['Language']->getText('plugin_im', 'muckl_alt_togglesound'); ?>"
                      onClick="toggleSound(this);"
                      class='actionButton'>
                  </td>
                </tr>
            </table>
          </td>
        </tr>
    </table>
  </body>
</html>
