
function getArgs(){
  passedArgs=new Array();
  search = self.location.href;
  search = search.split('?');
  if(search.length>1){
    argList = search[1];
    argList = argList.split('&');
    for(var i=0; i<argList.length; i++){
      newArg = argList[i];
      newArg = argList[i].split('=');
      passedArgs[unescape(newArg[0])] = unescape(newArg[1]);
    }
  }
}

function cutResource(aJID) { // removes resource from a given jid
	if (typeof(aJID) == 'undefined' || !aJID)
		return;
  var retval = aJID;
  if (retval.indexOf("/") != -1)
    retval = retval.substring(0,retval.indexOf("/"));
  return retval;
}

function msgEscape(msg) {
	if (typeof(msg) == 'undefined' || !msg || msg == '')
		return;

  msg = msg.replace(/%/g,"%25"); // must be done first

  msg = msg.replace(/\n/g,"%0A");
  msg = msg.replace(/\r/g,"%0D");
  msg = msg.replace(/ /g,"%20");
  msg = msg.replace(/\"/g,"%22");
  msg = msg.replace(/#/g,"%23");
  msg = msg.replace(/\$/g,"%24");
  msg = msg.replace(/&/g,"%26");
  msg = msg.replace(/\(/g,"%28");
  msg = msg.replace(/\)/g,"%29");
  msg = msg.replace(/\+/g,"%2B");
  msg = msg.replace(/,/g,"%2C");
  msg = msg.replace(/\//g,"%2F");
  msg = msg.replace(/\:/g,"%3A");
  msg = msg.replace(/\;/g,"%3B");
  msg = msg.replace(/</g,"%3C");
  msg = msg.replace(/=/g,"%3D");
	msg = msg.replace(/>/g,"%3E");
	msg = msg.replace(/@/g,"%40");

  return msg;
}

// fucking IE is too stupid for window names
function makeWindowName(wName) {
  wName = wName.replace(/@/,"at");
  wName = wName.replace(/\./g,"dot");
  wName = wName.replace(/\//g,"slash");
  wName = wName.replace(/&/g,"amp");
  wName = wName.replace(/\'/g,"tick");
  wName = wName.replace(/=/g,"equals");
  wName = wName.replace(/#/g,"pound");
  wName = wName.replace(/:/g,"colon");	
  wName = wName.replace(/%/g,"percent");
  wName = wName.replace(/-/g,"dash");
  wName = wName.replace(/ /g,"blank");
	wName = wName.replace(/\*/g,"asterix");
  return wName;
}

function htmlEnc(str) {
  return htmlFullEnc(str);
}

/* for use within tag attributes */
function htmlFullEnc(str) {
    if (!str)
        return '';

    str = str.replace(/&/g,"&amp;");
    str = str.replace(/</g,"&lt;");
    str = str.replace(/>/g,"&gt;");
    str = str.replace(/`/g, "&#x60;");
    str = str.replace(/'/g, "&#039;");
    str = str.replace(/"/g,"&quot;");

    return str;
}

function msgFormat(msg) { // replaces emoticons and urls in a message
	if (!msg)
		return null;

  msg = htmlEnc(msg);

	if (typeof(emoticons) != 'undefined') {
		for (var i in emoticons) {
			var iq = i.replace(/\\/g, '');
			var emo = new Image();
			emo.src = emoticonpath+emoticons[i];
			if (emo.width > 0 && emo.height > 0)
				msg = msg.replace(eval("/\(\\s\|\^\)"+i+"\(\\s|\$\)/g"),"$1<img src=\""+emo.src+"\" width='"+emo.width+"' height='"+emo.height+"' alt=\""+iq+"\" title=\""+iq+"\">$2");
			else
				msg = msg.replace(eval("/\(\\s\|\^\)"+i+"\(\\s|\$\)/g"),"$1<img src=\""+emo.src+"\" alt=\""+iq+"\" title=\""+iq+"\">$2");

		}
	}
	
  // replace http://<url>
  msg = msg.replace(/(\s|^)(https?:\/\/\S+)/gi,"$1<a href=\"$2\" target=\"_blank\" rel=\"noreferrer\">$2</a>");

	// replace ftp://<url>
  msg = msg.replace(/(\s|^)(ftp:\/\/\S+)/gi,"$1<a href=\"$2\" target=\"_blank\" rel=\"noreferrer\">$2</a>");
  
  // replace mail-links
  msg = msg.replace(/(\s|^)(\w+\@\S+\.\S+)/g,"$1<a href=\"mailto:$2\">$2</a>");
  
  // replace *<pattern>*
  msg = msg.replace(/(\s|^)\*([^\*\r\n]+)\*/g,"$1<b>\$2\</b>");

  // replace _bla_ 
  msg = msg.replace(/(\s|^)\_([^\*\r\n]+)\_/g,"$1<u>$2</u>");

  // replace Codendi References
  codendiRefRegexpOtherProject = /(\s|^)(\S+) #([\d]+):(\S+)/g;
  codendiRefRegexpOtherProjectNotOk = /(\s|^)(\S+) #([\S]+):(\S+)/g;
  codendiRefRegexpSameProject = /(\s|^)(\S+) #([\S]+)/g;
  var serverBaseUrl = ('https:' == document.location.protocol ? 'https://'+XMPPDOMAINSSL : 'http://'+XMPPDOMAIN);
  if (codendiRefRegexpOtherProject.test(msg)) {
    msg = msg.replace(codendiRefRegexpOtherProject,"$1<a href=\"" + serverBaseUrl + "/goto?key=$2&val=$4&group_id=$3\" target=\"_blank\" rel=\"noreferrer\">$2 #$3:$4</a>");
  } else {
    if (! codendiRefRegexpOtherProjectNotOk.test(msg)) {
      if (codendiRefRegexpSameProject.test(msg)) {
        msg = msg.replace(codendiRefRegexpSameProject,"$1<a href=\"" + serverBaseUrl + "/goto?key=$2&val=$3&group_id=" + GROUP_ID  + "\" target=\"_blank\" rel=\"noreferrer\">$2 #$3</a>");
      }
    }
  }

  msg = msg.replace(/\n/g,"<br>");

  return msg;
}

/* isValidJID
 * checks whether jid is valid
 */
var prohibited = ['"',' ','&','\'','/',':','<','>','@']; // invalid chars
function isValidJID(jid) {
  var nodeprep = jid.substring(0,jid.lastIndexOf('@')); // node name (string before the @)

  for (var i in prohibited) {
    if (nodeprep.indexOf(prohibited[i]) != -1) {
      alert(loc("Invalid JID\n'[_1]' not allowed in JID.\nChoose another one!",prohibited[i]));
      return false;
    }
  }
  return true;
}

/* hrTime - human readable Time
 * takes a timestamp in the form of 2004-08-13T12:07:04±02:00 as argument
 * and converts it to some sort of humane readable format
 */
function hrTime(ts) {
	var date = new Date(Date.UTC(ts.substr(0,4),ts.substr(5,2)-1,ts.substr(8,2),ts.substr(11,2),ts.substr(14,2),ts.substr(17,2)));
	if (ts.substr(ts.length-6,1) != 'Z') { // there's an offset
		var offset = new Date();
		offset.setTime(0);
		offset.setUTCHours(ts.substr(ts.length-5,2));
		offset.setUTCMinutes(ts.substr(ts.length-2,2));
		if (ts.substr(ts.length-6,1) == '+')
			date.setTime(date.getTime() - offset.getTime());
		else if (ts.substr(ts.length-6,1) == '-')
			date.setTime(date.getTime() + offset.getTime());
	}
	return date.toLocaleString();
}

/* jabberDate
 * somewhat opposit to hrTime (see above)
 * expects a javascript Date object as parameter and returns a jabber 
 * date string conforming to JEP-0082
 */
function jabberDate(date) {
	if (!date.getUTCFullYear)
		return;

	var jDate = date.getUTCFullYear() + "-";
	jDate += (((date.getUTCMonth()+1) < 10)? "0" : "") + (date.getUTCMonth()+1) + "-";
	jDate += ((date.getUTCDate() < 10)? "0" : "") + date.getUTCDate() + "T";

	jDate += ((date.getUTCHours()<10)? "0" : "") + date.getUTCHours() + ":";
	jDate += ((date.getUTCMinutes()<10)? "0" : "") + date.getUTCMinutes() + ":";
	jDate += ((date.getUTCSeconds()<10)? "0" : "") + date.getUTCSeconds() + "Z";

	return jDate;
}

function readCookie(name)
{
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++)
	{
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1,c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
	}
	return null;
}
