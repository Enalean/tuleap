//
//  This script was created
//  by Mircho Mirev
//  mo /mo@momche.net/
//
//	:: feel free to use it BUT
//	:: if you want to use this code PLEASE send me a note
//	:: and please keep this disclaimer intact
//

//	This in fact is a simple dom iterator
//	requires: mobrowser.js

function cDomExtension( hParent, aSelectors, hInitFunction )
{
	this.hParent = hParent
	this.aSelectors = aSelectors
	this.hInitFunction = hInitFunction
}

cDomExtensionManager = 
{
	aExtensions : new Array()
}

cDomExtensionManager.register = function( hDomExtension )
{
	cDomExtensionManager.aExtensions.push( hDomExtension )
}

cDomExtensionManager.initSelector = function( hParent, sSelector, hInitFunction )
{
	var hSelectorRegEx
	var hAttributeRegEx
	var aSelectorData
	var aAttributeData
	var sAttribute 

	hSelectorRegEx = /([a-z0-9_]*)\[?([^\]]*)\]?/i
	hAttributeRegEx = /([a-z0-9_]*)([\*\^\$]?)(=?)(([a-z0-9_=]*))/i

	if( hSelectorRegEx.test( sSelector ) && !/[@#\.]/.test( sSelector ) )
	{
		aSelectorData = hSelectorRegEx.exec( sSelector )
		if( aSelectorData[ 1 ] != '' )
		{
			hGroup  = hParent.getElementsByTagName( aSelectorData[ 1 ].toLowerCase() )
			for( nI = 0; nI < hGroup.length; nI ++ )
			{
				hGroup[ nI ].markExt = true
			}
			for( nI = 0; nI < hGroup.length; nI ++ )
			{
				if( !hGroup[ nI ].markExt )
				{
					continue
				}
				else
				{
					hGroup[ nI ].markExt = false
				}
				if( aSelectorData[ 2 ] == '' )
				{
					if( hGroup[ nI ].tagName.toLowerCase() == aSelectorData[ 1 ].toLowerCase()  )
					{
						hInitFunction( hGroup[ nI ] )
					}
				}
				else
				{
					aAttributeData = hAttributeRegEx.exec( aSelectorData[ 2 ] )
					if( aAttributeData[ 1 ] == 'class' )
					{
						sAttribute = hGroup[ nI ].className
					}
					else
					{
						sAttribute = hGroup[ nI ].getAttribute( aAttributeData[ 1 ] )
					}
					if( sAttribute != null && sAttribute.length > 0 )
					{
						if( aAttributeData[ 3 ] == '=' )
						{
							if( aAttributeData[ 2 ] == '' )
							{
								if( sAttribute == aAttributeData[4] )
								{
									hInitFunction( hGroup[ nI ] )
								}
							}
							else
							{
								switch( aAttributeData[ 2 ] )
								{
									case '^' :	if( sAttribute.indexOf( aAttributeData[ 4 ] ) == 0 )
												{
													hInitFunction( hGroup[ nI ] )
												}
												break
									case '$' :	if( sAttribute.lastIndexOf( aAttributeData[ 4 ] ) == sAttribute.length - aAttributeData[ 4 ].length )
												{
													hInitFunction( hGroup[ nI ] )
												}
												break
									case '*' :	if( sAttribute.indexOf( aAttributeData[ 4 ] ) >= 0 )
												{
													hInitFunction( hGroup[ nI ] )
												}
												break
								}
							}
						}
						else
						{
							hInitFunction( hGroup[ nI ] )
						}
					}
				}
			}
			//we have the new implementation - css3 style selectors, so return
			return
		}
	}


	hSelectorRegEx = /([a-z0-9_]*)([\.#@]?)([a-z0-9_=~]*)/i
	hAttributeRegEx = /([a-z0-9_]*)([=~])?([a-z0-9_]*)/i
	aSelectorData = hSelectorRegEx.exec( sSelector )
	
	if( aSelectorData[ 1 ] != '' )
	{
		var hGroup  = hParent.getElementsByTagName( aSelectorData[ 1 ] )
		for( nI = 0; nI < hGroup.length; nI ++ )
		{
			hGroup[ nI ].markExt = true
		}
		for( nI = 0; nI < hGroup.length; nI ++ )
		{
			if( !hGroup[ nI ].markExt )
			{
				continue
			}
			else
			{
				hGroup[ nI ].markExt = false
			}
			if( aSelectorData[ 2 ] != '' )
			{
				switch( aSelectorData[ 2 ] )
				{
					case '.' : 	if( hGroup[ nI ].className == aSelectorData[ 3 ] )
								{
									hInitFunction( hGroup[ nI ] )
								}
								break
								
					case '#' : 	if( hGroup[ nI ].id == aSelectorData[ 3 ] )
								{
									hInitFunction( hGroup[ nI ] )
								}
								break
								
					case '@' : 	aAttributeData = hAttributeRegEx.exec( aSelectorData[ 3 ] )
								sAttribute = hGroup[ nI ].getAttribute( aAttributeData[ 1 ] )
								if(  sAttribute != null && sAttribute.length > 0  )
								{					
									if( aAttributeData[ 3 ] != '' )
									{
										if( aAttributeData[ 2 ] == '=' )
										{
											if( sAttribute == aAttributeData[ 3 ] )
											{
												hInitFunction( hGroup[ nI ] )
											}
										}
										else /* the case is like ~ */
										{
											if( sAttribute.indexOf( aAttributeData[ 3 ] ) >= 0 )
											{
												hInitFunction( hGroup[ nI ] )
											}
										}
									}
									else
									{
										hInitFunction( hGroup[ nI ] )
									}
								}
								break
				}
			}
		}
	}

}

cDomExtensionManager.initialize = function()
{
	var hDomExtension = null
	var aSelectors
	
	for( var nKey in cDomExtensionManager.aExtensions )
	{
		aSelectors = cDomExtensionManager.aExtensions[ nKey ].aSelectors
		for( var nKey2 in aSelectors )
		{
			cDomExtensionManager.initSelector( cDomExtensionManager.aExtensions[ nKey ].hParent, aSelectors[ nKey2 ], cDomExtensionManager.aExtensions[ nKey ].hInitFunction )
		}
	}
}

if( window.addEventListener )
{
	window.addEventListener( 'load', cDomExtensionManager.initialize, false )
}
else if( window.attachEvent )
{
	window.attachEvent( 'onload', cDomExtensionManager.initialize )
}
