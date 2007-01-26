//
//  This script was created
//  by Mircho Mirev
//  mo /mo@momche.net/
//
//	:: feel free to use it BUT
//	:: if you want to use this code PLEASE send me a note
//	:: and please keep this disclaimer intact
//

//define the cEvent object
cDomEvent = {
	e 		: null,
	type	: '',
	button	: 0,
	key		: 0,
	x		: 0,
	y		: 0,
	pagex	: 0,
	pagey	: 0,
	target	: null,
	from	: null,
	to		: null
}

cDomEvent.init = function( e )
{
	if( bw.ie ) e = window.event
	this.e = e
	this.type = e.type
	this.button = ( bw.ns4 ) ? e.which : e.button
	this.key = ( bw.ns4 ) ? e.which : e.keyCode
	this.target = ( e.srcElement ) ? e.srcElement : e.originalTarget 
	this.from = ( bw.ns5 ) ? e.originalTarget : ( bw.ie ) ? e.fromElement : null
	this.to  = ( bw.ns5 ) ? e.currentTarget : ( bw.ie ) ? e.toElement : null
	this.x = ( bw.ns ) ? e.layerX : e.offsetX
	this.y = ( bw.ns ) ? e.layerY : e.offsetY
	this.screenX = e.screenX
	this.screenY = e.screenY
	this.pageX = ( bw.ns ) ? e.pageX : e.x + document.body.scrollLeft
	this.pageY = ( bw.ns ) ? e.pageY : e.y + document.body.scrollTop
}

cDomEvent.addEvent = function( hElement, sEvent, handler, bCapture )
{
	if( hElement.addEventListener )
	{
		hElement.addEventListener( sEvent, handler, bCapture )
		return true
	}
	else if( hElement.attachEvent )
	{
		return hElement.attachEvent( 'on'+sEvent, handler )
	}
	else if( bw.ie4 || bw.ns4 )
	{
		if( bw.ns4 ) eval( 'hElement.captureEvents( Event.'+sEvent.toUpperCase()+' )' )
		eval( 'hElement.on'+sEvent+' = '+handler )
	}
	else
	{
		alert('Not implemented yet!')
	}
}

cDomEvent.removeEvent = function( hElement, sEvent, handler, bCapture )
{
	if( hElement.addEventListener )
	{
		hElement.removeEventListener( sEvent, handler, bCapture )
		return true
	}
	else if( hElement.attachEvent )
	{
		return hElement.detachEvent( 'on'+sEvent, handler )
	}
	else if( bw.ie4 || bw.ns4 )
	{
		eval( 'hElement.on'+sEvent+' = null' )
	}
	else
	{
		alert('Not implemented yet!')
	}
}


//Mouse button mapper object
function MouseButton()
{
	if( bw.ns4 )
	{
		this.left = 1
		this.middle = 2
		this.right = 3
	}
	else if( bw.ns5 )
	{
		this.left = 0
		this.middle = 1
		this.right = 2
	}
	else if( bw.ie )
	{
		this.left = 1
		this.middle = 4
		this.right = 2
	}
}

var MB = new MouseButton()