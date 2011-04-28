var DEBUGGER_MAX_LEVEL = 4;

// fucking IE is too stupid for window names
function encWN(wName) {
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
  return wName;
}

function htmlEnc(str) {
  if (!str)
    return null;

  str = str.replace(/&/g,"&amp;");
  str = str.replace(/</g,"&lt;");
  str = str.replace(/>/g,"&gt;");
  str = str.replace(/\n/g,"<br>");
  return str;
}

function DebugMsg(str,lvl,caller) {
  this.str = str || '';
  this.str = htmlEnc(this.str);
  this.lvl = lvl || 0;
  this.caller = (caller&&caller.name)? caller.name : 'unknown';
}

function DebugLog(str,lvl) {
  if (!this.debug) // nothing to do
    return;

  lvl = (isNaN(lvl))? 0 : lvl;
  if (!this.debugW.oDbg || this.debugW.oDbg != this)
    this.debugW.oDbg = this;
  
  this.debugMsgs = this.debugMsgs.concat(new DebugMsg(str,lvl,DebugLog.caller));
  
  if (!this.debugW || !this.debugW.popMsgs || (this.debugW.document && this.debugW.document.readyState && this.debugW.document.readyState!='complete'))
    return; // debugW not ready yet ... only queue message
  
  this.debugW.popMsgs();
}

function DebugSetLevel(lvl) {
  if (lvl < 0)
    lvl = 0;
  if (lvl > DEBUGGER_MAX_LEVEL)
    lvl = DEBUGGER_MAX_LEVEL;
  this.lvl = lvl;
}

function DebugStart() {
  if (!this.debugW || this.debugW.closed) { // open the debugger window
    debugW = window.open('','debugW'+encWN(this.id),"width=480,height=320,scrollbars=yes,resizable=yes");
    if (!debugW)
      debugW = window.open("lib/Debugger/Debugger.html","debugW"+encWN(this.id),"width=480,height=320,scrollbars=yes,resizable=yes");
    else if (!debugW.location.href.match(/Debugger.html$/))
      debugW.location.href = "lib/Debugger/Debugger.html";
      
    
    this.debugW = debugW;
    
    if (!this.debugW)
      return;
    
    if (!this.debugW.oDbg)
      this.debugW.oDbg = this;
  } else {
    this.debugW.frames['DebugTop'].document.getElementById('toggleLogButton').innerHTML = 'Stop';
  }
  
  this.debug = true;
}

function DebugStop() {
	this.debug = false;
}

function Debugger(lvl,id) {
	this.lvl = lvl || 0;
	if (this.lvl > DEBUGGER_MAX_LEVEL)
		this.lvl = DEBUGGER_MAX_LEVEL;

	this.id = id || '';

	this.debugMsgs = new Array();

	this.log = DebugLog;
	this.setLevel = DebugSetLevel;
	this.start = DebugStart;
	this.stop = DebugStop;
}
