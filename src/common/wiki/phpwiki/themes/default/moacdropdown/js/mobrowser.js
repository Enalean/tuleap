/*
*	mo's browser checker
*	heavily based on the v1 library
*	mo / mircho@hotmail.com
*	please keep this note here
*/

//make a browser checker object
function cBrowser() {
	var userAgent = navigator.userAgent.toLowerCase()
	this.version = parseInt(navigator.appVersion)
	this.subVersion = parseFloat(navigator.appVersion)
	this.ns  = ((userAgent.indexOf('mozilla')!=-1) && ((userAgent.indexOf('spoofer')==-1) && (userAgent.indexOf('compatible') == -1)))
	this.ns2 = (this.ns && (this.version == 2))
	this.ns3 = (this.ns && (this.version == 3))
	this.ns4b = (this.ns && (this.subVersion < 4.04))
	this.ns4 = (this.ns && (this.version == 4))
	this.ns5 = (this.ns && (this.version == 5))
	this.ie   = (userAgent.indexOf('msie') != -1)
	this.ie3  = (this.ie && (this.version == 2))
	this.ie4  = (this.ie && (this.version == 4) && (userAgent.indexOf('msie 4.')!=-1))
	this.ie5  = (this.ie && (this.version == 4) && (userAgent.indexOf('msie 5.0')!=-1))
	this.ie55 = (this.ie && (this.version == 4) && (userAgent.indexOf('msie 5.5')!=-1))
	this.ie6 = (this.ie && (this.version == 4) && (userAgent.indexOf('msie 6.0')!=-1))
	this.op3 = (userAgent.indexOf('opera') != -1)
	this.win   = (userAgent.indexOf('win')!=-1)
	this.mac   = (userAgent.indexOf('mac')!=-1)
	this.unix  = (userAgent.indexOf('x11')!=-1)
	this.name = navigator.appName
	this.dom = this.ns5 || this.ie5 || this.ie55 || this.ie6
}

var bw = new cBrowser()