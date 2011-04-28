<?php
require_once('pre.php');
$group_id = $request->get('group_id');
?>

function RosterGroup(name) {
  this.name = name;
  this.users = new Array();
  this.onlUserCount = 0;
  this.messagesPending = 0;
}

function RosterUserAdd2Group(group) {
  this.groups = this.groups.concat(group);
}

function RosterUser(jid,subscription,groups,name) {

	this.fulljid = jid;
  this.jid = cutResource(jid) || 'unknown';
  this.jid = this.jid.toLowerCase(); // jids are case insensitive

  this.subscription = subscription || 'none';
  this.groups = groups || [''];

  if (name)
    this.name = name;
  else if (this.jid == XMPPDOMAIN)
    this.name = "System";
  else if ((this.jid.indexOf('@') != -1) && this.jid.substring(this.jid.indexOf('@')+1) == XMPPDOMAIN) // we found a local user
    this.name = this.jid.substring(0,jid.indexOf('@'));
  else
    this.name = this.jid;

  this.name = htmlFullEnc(this.name);

  // initialise defaults
  this.status = (this.subscription == 'from' || this.subscription == 'none') ? 'stalker' : 'unavailable';
  this.statusMsg = null;
  this.lastsrc = null;
  this.messages = new Array();
  this.chatmsgs = new Array();
  this.chatW = null; // chat window

  // methods
  this.add2Group = RosterUserAdd2Group;

}

function getElFromArrByProp(arr,prop,str) {
  for (var i in arr) {
    if (arr[i][prop] == str)
      return arr[i];
  }
  return null;
}

function getRosterGroupByName(groupName) {
  return getElFromArrByProp(this.groups,"name",groupName);
}

function getRosterUserByJID(jid) {
  return getElFromArrByProp(this.users,"jid",jid.toLowerCase());
}

function RosterUpdateStyleIE() {
  if(!is.ie)
    return;
  this.rosterW.getElementById("roster").style.width = this.rosterW.body.clientWidth;
}

function RosterGetUserIcons(from) {
  var images = new Array();
  
  for (var i=0; i<this.groups.length; i++) {
    var img = this.rosterW.images[from+"/"+this.groups[i].name];
    if (img) {
      images = images.concat(img);
      continue; // skip this group
    }
  }
  return images;
}

function RosterToggleHide() {
  this.usersHidden = !this.usersHidden;
  this.print();
  return;
}
	
function RosterToggleGrp(name) {
  var el = this.rosterW.getElementById(name);
  if (el.className == 'hidden') {
    el.className = 'rosterGroup';
    this.hiddenGroups[name] = false;
		//    this.rosterW.images[name+"Img"].src = grp_open_img.src;
  } else {
    el.className = 'hidden';
    this.hiddenGroups[name] = true;
		//    this.rosterW.images[name+"Img"].src = grp_close_img.src;
  }
  this.updateStyleIE();
}

function RosterOpenMessage(jid) {
  var user = this.getUserByJID(jid);
  var wName = makeWindowName(user.jid); 

  if (user.messages.length > 0 && (!user.mW || user.mW.closed)) // display messages
    user.mW = open('message.html?jid='+escape(jid),"mw"+wName,'width=360,height=270,dependent=yes,resizable=yes');
  else if (!user.sW || user.sW.closed) // open send dialog
    user.sW = open("send.html?jid="+escape(jid),"sw"+wName,'width=320,height=200,dependent=yes,resizable=yes');
  return false;
}

function RosterOpenChat(jid) {

  var user = this.getUserByJID(jid);

	if (!user)
		return;

	if (user.messages.length > 0 && (!user.mW || user.mW.closed)) // display messages
		this.openMessage(jid);
		
  if (!user.chatW || user.chatW.closed)
    user.chatW = open("chat.html?jid="+escape(jid),"chatW"+makeWindowName(user.jid),"width=320,height=360,resizable=yes");
  else if (user.chatW.popMsgs)
    user.chatW.popMsgs();
}

function RosterOpenGroupchat(aJid,nick,pass) {
	pass = pass || '';
	nick = nick || '';

  var user = this.getUserByJID(aJid);
  if(!user) {
    user = this.addUser(new RosterUser(aJid,'',["Chat Rooms"],aJid.substring(0,aJid.indexOf('@'))));
		user.type = 'groupchat';
  }

	frames['chatW'].location.replace("groupchat.php?jid="+escape(aJid)+"&nick="+escape(nick)+"&pass="+escape(pass)+"&group_id="+<?php echo $group_id; ?>);
	user.chatW = frames['chatW'];
}

function RosterCleanUp() {
  for (var i in this.users) {
    if (this.users[i].roster)
      this.users[i].roster.cleanUp();
    if (this.users[i].sW)
      this.users[i].sW.close();
    if (this.users[i].mW)
      this.users[i].mW.close();
    if (this.users[i].chatW)
      this.users[i].chatW.close();
    if (this.users[i].histW)
      this.users[i].histW.close();
  }
}

function RosterUpdateGroupForUser(user) {
  for (var j in user.groups) {
    if (user.groups.length > 1 && user.groups[j] == '')
      continue;
    var groupName = (user.groups[j] == '') ? "Unfiled" : user.groups[j];
    var group = this.getGroupByName(groupName);
    if(group == null) {
      group = new RosterGroup(groupName);
      this.groups = this.groups.concat(group);
    }
    group.users = group.users.concat(user);
  }
}
	
function RosterUpdateGroups() {
  this.groups = new Array();
  for (var i in this.users)
    this.updateGroupsForUser(this.users[i]);
}

function RosterUserAdd(user) {
  this.users = this.users.concat(user);
	
  // add to groups
  this.updateGroupsForUser(user);
  return user;
}

function RosterRemoveUser(user) {
  var uLen = this.users.length;
  for (var i=0; i<uLen; i++) {
    if (user == this.users[i]) {
      this.users = this.users.slice(0,i).concat(this.users.slice(i+1,uLen));
      break;
    }
  }
  this.updateGroups();
}

function RosterGetGroupchats() {
	var groupchats = new Array();
	for (var i in this.users)
		if (this.users[i].roster)
			groupchats[groupchats.length] = this.users[i].jid+'/'+this.users[i].roster.nick;
	return groupchats;
}
	
function Roster(items,targetW) {
  this.users = new Array();
  this.groups = new Array();
	this.hiddenGroups = new Array();
  this.name = 'Roster';

  this.rosterW = targetW;
	
  /* object's methods */
  this.print = printRoster;
  this.getGroupByName = getRosterGroupByName;
  this.getUserByJID = getRosterUserByJID;
  this.addUser = RosterUserAdd;
  this.removeUser = RosterRemoveUser;
  this.updateGroupsForUser = RosterUpdateGroupForUser;
  this.updateGroups = RosterUpdateGroups;
  this.toggleGrp = RosterToggleGrp;
  this.updateStyleIE = RosterUpdateStyleIE;
  this.toggleHide = RosterToggleHide;
  this.getUserIcons = RosterGetUserIcons;
  this.openMessage = RosterOpenMessage;
  this.openChat = RosterOpenChat;
  	this.openGroupchat = RosterOpenGroupchat;
  this.cleanUp = RosterCleanUp;
	this.getGroupchats = RosterGetGroupchats;
 
  /* setup groups */
	if (!items)
		return;
  for (var i=0;i<items.length;i++) {
    /* if (items[i].jid.indexOf("@") == -1) */ // no user - must be a transport
    if (typeof(items.item(i).getAttribute('jid')) == 'undefined')
      continue;
    var name = items.item(i).getAttribute('name') || cutResource(items.item(i).getAttribute('jid'));
		var groups = new Array('');
		for (var j=0;j<items.item(i).childNodes.length;j++)
			if (items.item(i).childNodes.item(j).nodeName == 'group')
				groups = groups.concat(items.item(i).childNodes.item(j).firstChild.nodeValue);
    this.addUser(new RosterUser(items.item(i).getAttribute('jid'),items.item(i).getAttribute('subscription'),groups,name));
  }
}

function rosterSort(a,b) {
  return (a.name.toLowerCase()<b.name.toLowerCase())?-1:1;
}

function printRoster() {
	if (!this.rosterW)
		return;

  /* update user count for groups */
  for (var i=0; i<this.groups.length; i++) {
    this.groups[i].onlUserCount = 0;
    this.groups[i].messagesPending = 0;
    for (var j=0; j<this.groups[i].users.length; j++) {
      if (this.groups[i].users[j].status != 'unavailable' && this.groups[i].users[j].status != 'stalker')
        this.groups[i].onlUserCount++;
      if (this.groups[i].users[j].lastsrc)
        this.groups[i].messagesPending++;
    }
  }
	
  var rosterHTML = '';
  
  this.groups = this.groups.sort(rosterSort);
  
	/* ***
	 * loop rostergroups 
	 */
  for (var i=0; i<this.groups.length; i++) {
		if (this.groups[i].name == 'nones') // [qnd] skip this one
			continue;

    var rosterGroupHeadClass = (this.usersHidden && this.groups[i].onlUserCount == 0 && this.groups[i].messagesPending == 0) ? 'rosterGroupHeaderHidden':'rosterGroupHeader';
    rosterHTML += "<div id='"+this.groups[i].name+"Head' class='"+rosterGroupHeadClass+"'><nobr>";
		rosterHTML += this.groups[i].onlUserCount+"&nbsp;"+this.groups[i].name;

    rosterHTML += "</nobr></div>";
    var rosterGroupClass = ((this.usersHidden && this.groups[i].onlUserCount == 0 && this.groups[i].messagesPending == 0) || this.hiddenGroups[this.groups[i].name])?'hidden':'rosterGroup';
    rosterHTML += "<div id='"+this.groups[i].name+"' class='"+rosterGroupClass+"'>";
    
    this.groups[i].users = this.groups[i].users.sort(rosterSort);

		/* ***
		 * loop users in rostergroup 
		 */
    for (var j=0; j<this.groups[i].users.length; j++) {
      var user = this.groups[i].users[j];
      rosterHTML += "<div id=\"rosterUser_"+user.name+"\" class=\"rosterUser\" onMouseOver=\"highlight(this);\" onMouseOut=\"unhighlight(this);\" username=\""+user.name+"\" title=\"Send Private Message\"><span class=\"user_"+user.status+"\">"+user.name+"</span>";
			if (user.statusMsg)
				rosterHTML += "<div class='statusMsg'>"+htmlEnc(user.statusMsg)+"</div>";
			
			rosterHTML += "</div>";
    } /* END inner loop */
    rosterHTML += "</div>";
  }

  this.rosterW.getElementById("roster").innerHTML = rosterHTML;
  this.updateStyleIE();

  if (typeof(this.eprint) != 'undefined')
    this.eprint();
}


/***********************************************************************
 * GROUPCHAT ROSTER
 *+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
 */

function GroupchatRosterUserAdd2Group(group) {
  this.groups = [group];
}

function GroupchatRosterUser(jid,name) {
  this.base = RosterUser;
  this.base(jid,'',[''],name);
	this.jid = this.fulljid; // always use fulljid
  this.affiliation = 'none';
  this.role = 'none';

  this.add2Group = GroupchatRosterUserAdd2Group;
}

GroupchatRosterUser.prototype = new RosterUser;

function getRosterGetRealJIDByNick(nick) {
	for (var i in this.users)
		if (this.users[i].name == nick)
			return this.users[i].realjid;
	return null;
}

function getRosterGetFullJIDByNick(nick) {
	for (var i in this.users)
		if (this.users[i].name == nick)
			return this.users[i].fulljid;
	return null;
}
			
function GroupchatRosterPrint() {
  this.targetW.document.getElementById('chan_count').innerHTML = this.users.length;
}

function getGroupchatRosterUserByJID(jid) {
	// need to search fulljid here
  return getElFromArrByProp(this.users,"fulljid",jid);
}

function GroupchatRoster(targetW) {

  this.base = Roster;
  this.base(null);
  this.usersHidden = true;

  this.targetW = targetW;

  this.rosterW = this.targetW.groupchatIRoster.document;

  this.name = 'GroupchatRoster';

  //  this.eprint = GroupchatRosterPrint;
	this.getUserByJID = getGroupchatRosterUserByJID;
	this.getRealJIDByNick = getRosterGetRealJIDByNick;
	this.getFullJIDByNick = getRosterGetFullJIDByNick;
}

GroupchatRoster.prototype = new Roster();

