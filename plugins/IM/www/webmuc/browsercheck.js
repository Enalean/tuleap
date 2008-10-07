/*	BrowserCheck Object
	provides most commonly needed browser checking variables
	19990326
*/
// Copyright (C) 1999 Dan Steinman
// Distributed under the terms of the GNU Library General Public License
// Available at http://www.dansteinman.com/dynapi/

function BrowserCheck() {
	var b = navigator.appName
	if (b=="Netscape") this.b = "ns"
	else if (b=="Microsoft Internet Explorer") this.b = "ie"
	else if (b=="Opera") this.b = "op"
	else this.b = b
	this.v = parseInt(navigator.appVersion)
	this.ns = (this.b=="ns" && this.v>=4)
	this.ns4 = (this.b=="ns" && this.v==4)
	this.ns5 = (this.b=="ns" && this.v==5)
	this.ie = (this.b=="ie" && this.v>=4)
	this.op = (this.b=="op" && this.v>=7)
	this.ie4 = (navigator.userAgent.indexOf('MSIE 4')>0)
	this.ie5 = (navigator.userAgent.indexOf('MSIE 5')>0)
	this.ie55 = (navigator.userAgent.indexOf('MSIE 5.5')>0)
	if (this.ie55) {
		this.v = 5.5;
		this.ie5 = false;
	}
	this.mac = (navigator.userAgent.indexOf('Mac')>0);
	if (this.ie5) this.v = 5
	this.min = (this.ns||this.ie)
}

// automatically create the "is" object
is = new BrowserCheck()
