//
//  This script was created
//  by Mircho Mirev
//  mo /mo@momche.net/
//
//	:: feel free to use it BUT
//	:: if you want to use this code PLEASE send me a note
//	:: and please keep this disclaimer intact
//

if ( document.ELEMENT_NODE == null )
{
	document.ELEMENT_NODE = 1
	document.TEXT_NODE = 3
}


function getSubNodeByName( hNode, sNodeName )
{
	if( hNode != null )
	{
		var nNc = 0
		var nC	= 0
		var hNodeChildren = hNode.childNodes
		var hCNode = null
		while( nC < hNodeChildren.length )
		{
			hCNode = hNodeChildren.item( nC++ )
			if( ( hCNode.nodeType == 1 ) && ( hCNode.nodeName.toLowerCase() == sNodeName ) )
			{
				return hCNode
			}
		}
	}
	return null
}

function getPrevNodeSibling( hNode )
{
	if( hNode != null )
	{
		do {
			hNode = hNode.previousSibling
		} while( hNode != null && hNode.nodeType != 1 )
		return hNode
	}
}

function getNextNodeSibling( hNode )
{
	if( hNode != null )
	{
		do {
			hNode = hNode.nextSibling
		} while( hNode != null && hNode.nodeType != 1 )
		return hNode
	}
}

function getLastSubNodeByName( hNode, sNodeName )
{
	if( hNode != null )
	{
		var nNc = 0
		var nC	= 0
		var hNodeChildren = hNode.childNodes
		var hCNode = null
		var nLength = hNodeChildren.length - 1
		while( nLength >=0  )
		{
			hCNode = hNodeChildren.item( nLength )
			if( ( hCNode.nodeType == 1 ) && ( hCNode.nodeName.toLowerCase() == sNodeName ) )
			{
				return hCNode
			}
			nLength--
		}
	}
	return null
}

function getSubNodeByProperty( hNode, sProperty, sPropValue )
{
	if( hNode != null )
	{
		var nNc = 0
		var nC	= 0
		var hNodeChildren = hNode.childNodes
		var hCNode = null
		var sAttribute
		var hProp 
		sPropValue = sPropValue.toLowerCase()
		while( nC < hNodeChildren.length )
		{
			hCNode = hNodeChildren.item( nC++ )
			if( hCNode.nodeType == document.ELEMENT_NODE )
			{
				hProp = eval( 'hCNode.'+sProperty )
				if( typeof( sPropValue ) != 'undefined' )
				{
					if( hProp.toLowerCase() == sPropValue )
					{
						return hCNode
					}
				}
				else
				{
					return hCNode
				}
			}
			nNc++
		}
	}
	return null
}

function findAttribute( hNode, sAtt )
{
	sAtt = sAtt.toLowerCase()
	for( var nI = 0; nI < hNode.attributes.length; nI++ )
	{
		if( hNode.attributes.item( nI ).nodeName.toLowerCase() == sAtt )
		{
			return hNode.attributes.item( nI ).nodeValue
		}
	}
	return null
}

function getSubNodeByAttribute( hNode, sAtt, sAttValue )
{
	if( hNode != null )
	{
		var nNc = 0
		var nC	= 0
		var hNodeChildren = hNode.childNodes
		var hCNode = null
		var sAttribute
		sAttValue = sAttValue.toLowerCase()
		while( nC < hNodeChildren.length )
		{
			hCNode = hNodeChildren.item( nC++ )
			if( hCNode.nodeType == document.ELEMENT_NODE )
			{
				sAttribute = hCNode.getAttribute( sAtt )
				if( sAttribute && sAttribute.toLowerCase() == sAttValue )
				return hCNode
			}
			nNc++
		}
	}
	return null
}

function getLastSubNodeByAttribute( hNode, sAtt, sAttValue )
{
	if( hNode != null )
	{
		var nNc = 0
		var nC	= 0
		var hNodeChildren = hNode.childNodes
		var hCNode = null
		var nLength = hNodeChildren.length - 1
		while( nLength >= 0 )
		{
			hCNode = hNodeChildren.item( nLength )
			if( hCNode.nodeType == document.ELEMENT_NODE )
			{
				sAttribute = hCNode.getAttribute( sAtt )
				if( sAttribute && sAttribute.toLowerCase() == sAttValue )
				return hCNode
			}
			nLength--
		}
	}
	return null
}

function getParentByTagName( hNode, sParentTagName )
{
	while( ( hNode.tagName ) && !( /(body|html)/i.test( hNode.tagName ) ) )
	{
		if( hNode.tagName == sParentTagName )
		{
			return hNode
		}
		hNode = hNode.parentNode
	}
	return null
}

function getParentByAttribute( hNode, sAtt, sAttValue )
{
	while( ( hNode.tagName ) && !( /(body|html)/i.test( hNode.tagName ) ) )
	{
		//opera strangely returns non null result sometimes
		var sAttr = hNode.getAttribute( sAtt )
		if( sAttr != null && sAttr.toString().length > 0 )
		{	
			if( sAttValue !== null )
			{
				if( sAttr == sAttValue )
				{
					return hNode
				}
			}
			else
			{
				return hNode
			}
		}
		hNode = hNode.parentNode
	}
	return null
}

function getParentByProperty( hNode, sProperty, sPropValue )
{
	while( ( hNode.tagName ) && !( /(body|html)/i.test( hNode.tagName ) ) )
	{
		//opera strangely returns non null result sometimes
		var hProp = eval( 'hNode.'+sProperty )
		if( hProp != null && hProp.toString().length > 0 )
		{	
			if( sPropValue !== null )
			{
				if( hProp == sPropValue )
				{
					return hNode
				}
			}
			else
			{
				return hNode
			}
		}
		hNode = hNode.parentNode
	}
	return null
}


function getNodeText( hNode )
{
	if( hNode == null )
	{
		return ''
	}
	var sRes
	if( hNode.hasChildNodes() )
	{
		sRes = hNode.childNodes.item(0).nodeValue
	}
	else
	{
		sRes = hNode.text
	}
	return sRes
}