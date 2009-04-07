//misc objects
//a simple encapsulation object
//used to query widths and heights

function cDomObject( sId )
{
	if( bw.dom || bw.ie )
	{
		this.hElement = document.getElementById( sId )
		this.hStyle = this.hElement.style
	}
}

cDomObject.prototype.getWidth = function( )
{
	return  cDomObject.getWidth( this.hElement )
}

cDomObject.getWidth = function( hElement )
{
	if( hElement.currentStyle )
	{
		var nWidth = parseInt( hElement.currentStyle.width )
		if( isNaN( nWidth ) )
		{
			return parseInt( hElement.offsetWidth )
		}
		else
		{
			return nWidth
		}
	}
	else
	{
		return parseInt( hElement.offsetWidth )
	}
}

cDomObject.prototype.getHeight = function( )
{
	return  cDomObject.getHeight( this.hElement )
}

cDomObject.getHeight = function( hElement )
{
	if( hElement.currentStyle )
	{
		var nHeight = parseInt( hElement.currentStyle.height )
		if( isNaN( nHeight ) )
		{
			return parseInt( hElement.offsetHeight )
		}
		else
		{
			return nHeight
		}
	}
	else
	{
		return parseInt( hElement.offsetHeight )
	}
}

cDomObject.prototype.getLeft = function()
{
	return cDomObject.getLeft( this.hElement )
}

cDomObject.getLeft = function( hElement )
{
	return parseInt( hElement.offsetLeft )
}

cDomObject.prototype.getTop = function( )
{
	return cDomObject.getTop( this.hElement )
}

cDomObject.getTop = function( hElement )
{
	return parseInt( hElement.offsetTop )
}


// used to get the absolute position of an relativeli position element
// by accumulating the offset parameters
// example
// cDomObject.getOffsetParam( hElement,'offsetLeft' )

cDomObject.getOffsetParam = function( hElement, sParam, hLimitParent )
{
	var nRes = 0
	if( hLimitParent == null )
	{
		hLimitParent = document.body.parentElement
	}
	while( hElement != hLimitParent )
	{
		nRes += eval( 'hElement.' + sParam )
		if( !hElement.offsetParent ) { break }
		hElement = hElement.offsetParent
	}
	return nRes
}


// used to get the absolute position of an relativeli position element
// by accumulating the scroll offset parameters
// example
// cDomObject.getScrollOffset( hElement,'Left' )

cDomObject.getScrollOffset = function( hElement, sParam, hLimitParent  )
{
	nRes = 0
	if( hLimitParent == null )
	{
		hLimitParent = document.body.parentElement
	}
	while( hElement != hLimitParent )
	{
		nRes += eval( 'hElement.scroll' + sParam )
		if( !hElement.offsetParent ) { break }
		hElement = hElement.parentNode
	}
	return nRes
}