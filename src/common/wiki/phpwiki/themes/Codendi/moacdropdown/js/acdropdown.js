//
//  This script was created
//  by Mircho Mirev
//  mo /mo@momche.net/
//	Copyright (c) 2004-2005 Mircho Mirev
//
//	:: feel free to use it BUT
//	:: if you want to use this code PLEASE send me a note
//	:: and please keep this disclaimer intact
//

function cAutocomplete( sInputId )
{
	this.init( sInputId )
}

cAutocomplete.CS_NAME = 'Autocomplete component'
cAutocomplete.CS_OBJ_NAME = 'AC_COMPONENT'
cAutocomplete.CS_LIST_PREFIX = 'ACL_'
cAutocomplete.CS_BUTTON_PREFIX = 'ACB_'
cAutocomplete.CS_INPUT_PREFIX = 'AC_'
cAutocomplete.CS_HIDDEN_INPUT_PREFIX = 'ACH_'
cAutocomplete.CS_INPUT_CLASSNAME = 'dropdown'

cAutocomplete.CB_AUTOINIT = true

cAutocomplete.CB_AUTOCOMPLETE = false

cAutocomplete.CB_FORCECORRECT = false

//the separator when autocompleting multiple values
cAutocomplete.CB_MATCHSUBSTRING = false
cAutocomplete.CS_SEPARATOR = ','

//the separator of associative arrays
cAutocomplete.CS_ARRAY_SEPARATOR = ','

//match the input string only against the begining of the strings
//or anywhere in the string
cAutocomplete.CB_MATCHSTRINGBEGIN = true

cAutocomplete.CN_OFFSET_TOP = 2
cAutocomplete.CN_OFFSET_LEFT = -1

cAutocomplete.CN_LINE_HEIGHT = 19
cAutocomplete.CN_NUMBER_OF_LINES = 10
cAutocomplete.CN_HEIGHT_FIX = 2

cAutocomplete.CN_CLEAR_TIMEOUT = 300
cAutocomplete.CN_SHOW_TIMEOUT = 300
cAutocomplete.CN_REMOTE_SHOW_TIMEOUT = 500
cAutocomplete.CN_MARK_TIMEOUT = 400

cAutocomplete.hListDisplayed = null
cAutocomplete.nCount = 0

cAutocomplete.autoInit = function()
{
	var nI = 0
	var hACE = null
	var sLangAtt

	var nInputsLength = document.getElementsByTagName( 'INPUT' ).length
	for( nI = 0; nI < nInputsLength; nI++ )
	{
		if( document.getElementsByTagName( 'INPUT' )[ nI ].type.toLowerCase() == 'text' )
		{
		 	sLangAtt = document.getElementsByTagName( 'INPUT' )[ nI ].getAttribute( 'acdropdown' )
			if( sLangAtt != null && sLangAtt.length > 0 )
			{
				if( document.getElementsByTagName( 'INPUT' )[ nI ].id == null || document.getElementsByTagName( 'INPUT' )[ nI ].id.length == 0 )
				{
					document.getElementsByTagName( 'INPUT' )[ nI ].id = cAutocomplete.CS_OBJ_NAME + cAutocomplete.nCount
				}
				hACE = new cAutocomplete( document.getElementsByTagName( 'INPUT' )[ nI ].id )
			}
		}
	}

	var nTALength = document.getElementsByTagName( 'TEXTAREA' ).length
	for( nI = 0; nI < nTALength; nI++ )
	{
	 	sLangAtt = document.getElementsByTagName( 'TEXTAREA' )[ nI ].getAttribute( 'acdropdown' )
		if( sLangAtt != null && sLangAtt.length > 0 )
		{
			if( document.getElementsByTagName( 'TEXTAREA' )[ nI ].id == null || document.getElementsByTagName( 'TEXTAREA' )[ nI ].id.length == 0 )
			{
				document.getElementsByTagName( 'TEXTAREA' )[ nI ].id = cAutocomplete.CS_OBJ_NAME + cAutocomplete.nCount
			}
			hACE = new cAutocomplete( document.getElementsByTagName( 'TEXTAREA' )[ nI ].id )
		}
	}


	var nSelectsLength = document.getElementsByTagName( 'SELECT' ).length
	var aSelect = null
	for( nI = 0; nI < nSelectsLength; nI++ )
	{
		aSelect = document.getElementsByTagName( 'SELECT' )[ nI ]
		sLangAtt = aSelect.getAttribute( 'acdropdown' )
		if( sLangAtt != null && sLangAtt.length > 0 )
		{
			if( aSelect.id == null || aSelect.id.length == 0 )
			{
				aSelect.id = cAutocomplete.CS_OBJ_NAME + cAutocomplete.nCount
			}
			hACE = new cAutocomplete( aSelect.id )
			nSelectsLength--
			nI--
		}
	}
}

if( cAutocomplete.CB_AUTOINIT )
{
	if( window.attachEvent )
	{
		window.attachEvent( 'onload', cAutocomplete.autoInit )
	}
	else if( window.addEventListener )
	{
		window.addEventListener( 'load', cAutocomplete.autoInit, false )
	}
}

cAutocomplete.prototype.init = function( sInputId )
{
	this.sInputId = sInputId
	this.sListId = cAutocomplete.CS_LIST_PREFIX + sInputId

	this.sObjName = cAutocomplete.CS_OBJ_NAME + '_obj_' + (cAutocomplete.nCount++)
	this.hObj = this.sObjName

	this.hActiveSelection = null
	this.nSelectedItemIdx = -1

	//the value of the input before the list is displayed
	this.sLastActiveValue = ''
	this.sActiveValue = ''
	this.bListDisplayed = false
	this.nItemsDisplayed = 0

	//if I transform a select option or the supplied array is associative I create a hidden input
	//with the name of the original input and replace the original input's name
	this.bAssociative = false
	this.sHiddenInputId = null
	this.bHasButton = false

	//the actual data
	this.aData = null
	//the search array object
	this.aSearchData = new Array()
	this.bSorted = false

	//the length of the last matched typed string
	this.nLastMatchLength = 0

	this.bForceCorrect = cAutocomplete.CB_FORCECORRECT
	var sForceCorrect = document.getElementById( this.sInputId ).getAttribute( 'autocomplete_forcecorrect' )
	if( sForceCorrect != null && sForceCorrect.length > 0 )
	{
		this.bForceCorrect = eval( sForceCorrect )
	}

	//match a only from the beginning or anywhere in the values
	this.bMatchBegin = cAutocomplete.CB_MATCHSTRINGBEGIN
	var sMatchBegin = document.getElementById( this.sInputId ).getAttribute( 'autocomplete_matchbegin' )
	if( sMatchBegin != null && sMatchBegin.length > 0 )
	{
		this.bMatchBegin = eval( sMatchBegin )
	}
	//match substrings separated by cAutocomplete.CS_SEPARATOR
	this.bMatchSubstring = cAutocomplete.CB_MATCHSUBSTRING
	var sMatchSubstring = document.getElementById( this.sInputId ).getAttribute( 'autocomplete_matchsubstring' )
	if( sMatchSubstring != null && sMatchSubstring.length > 0 )
	{
		this.bMatchSubstring = true
	}

	//autocomplete with the first option from the list
	this.bAutoComplete = cAutocomplete.CB_AUTOCOMPLETE
	this.bAutocompleted = false
	var sAutoComplete = document.getElementById( this.sInputId ).getAttribute( 'autocomplete_complete' )
	if( sAutoComplete != null && sAutoComplete.length > 0 )
	{
		this.bAutoComplete = eval( sAutoComplete )
	}
	//format function
	this.formatOptions = null
	var sFormatFunction = document.getElementById( this.sInputId ).getAttribute( 'autocomplete_format' )
	if( sFormatFunction != null && sFormatFunction.length > 0 )
	{
		this.formatOptions = eval( sFormatFunction )
	}
	//onselect callback function - get called when a new option is selected
	this.onSelect = null
	var sOnSelectFunction = document.getElementById( this.sInputId ).getAttribute( 'autocomplete_onselect' )
	if( sOnSelectFunction != null && sOnSelectFunction.length > 0 )
	{
		this.onSelect = eval( sOnSelectFunction )
	}

	//I assume that we always have the associative type
	//you can turn it off only with the autocomplete_assoc=false attribute
	this.bAssociative = true
	var sAssociative = document.getElementById( this.sInputId ).getAttribute( 'autocomplete_assoc' )
	if( sAssociative != null && sAssociative.length > 0 )
	{
		if( sAssociative == 'false' )
		{
			this.bAssociative = false
		}
	}

	//if we have remote list then we postpone the list creation
	if( this.getListArrayType() == 'url' || this.getListArrayType() == 'xmlrpc' )
	{
		this.bRemoteList = true
		this.sListURL = this.getListURL()
		this.hXMLHttp = XmlHttp.create()
		this.bXMLRPC = (this.getListArrayType() == 'xmlrpc')
		    
	}
	else
	{
		this.bRemoteList = false
	}
	this.initListArray()
	this.initListContainer()
	//this.createList()
	this.initInput()

	eval( this.hObj + '= this' )
}

cAutocomplete.prototype.initInput = function()
{
	var hInput = document.getElementById( this.sInputId )
	hInput.hAutocomplete = this
	var hContainer = document.getElementById( this.sListId )
	hContainer.hAutocomplete = this

	//any element (and it's children) with display:none have offset values of 0 (in mozilla)
	var hOWInput = hInput.cloneNode( true )
	hOWInput.style.position = 'absolute'
	hOWInput.style.top = '-1000px'
	document.body.appendChild( hOWInput )
	var nWidth = hOWInput.offsetWidth
	document.body.removeChild( hOWInput )

	var sInputName = hInput.name
	var hForm = hInput.form
	var bHasButton = false
	var sHiddenValue = hInput.value
	var sValue = hInput.type.toLowerCase() == 'text' ? hInput.value : ''

 	var sHasButton = hInput.getAttribute( 'autocomplete_button' )
	if( sHasButton != null && sHasButton.length > 0 )
	{
		bHasButton = true
	}

	//if it is a select - I unconditionally add a button
	if( hInput.type.toLowerCase() == 'select-one' )
	{
		bHasButton = true
		if( hInput.selectedIndex >= 0 )
		{
			sHiddenValue = hInput.options[ hInput.selectedIndex ].value
			sValue = hInput.options[ hInput.selectedIndex ].text
		}
	}

	//this is the case when the control is a transformed select or the list supplied is of the type - key,value not only values
	if( hForm )
	{
		var hHiddenInput = document.createElement( 'INPUT' )
		hHiddenInput.id = cAutocomplete.CS_HIDDEN_INPUT_PREFIX + this.sInputId
		hHiddenInput.type = 'hidden'
		hForm.appendChild( hHiddenInput )

		if( this.bAssociative )
		{
			hHiddenInput.name = sInputName
			hInput.name = cAutocomplete.CS_INPUT_PREFIX + sInputName
		}
		else
		{
			hHiddenInput.name = cAutocomplete.CS_INPUT_PREFIX + sInputName
		}

		hHiddenInput.value = sHiddenValue
		this.sHiddenInputId = hHiddenInput.id
	}

	if( bHasButton )
	{
		this.bHasButton = true

		var hInputContainer = document.createElement( 'DIV' )
		hInputContainer.className = 'acinputContainer'
		hInputContainer.style.width = nWidth

		var hInputButton = document.createElement( 'INPUT' )
		hInputButton.id = cAutocomplete.CS_BUTTON_PREFIX + this.sInputId
		hInputButton.type = 'button'
		hInputButton.className = 'button'
		hInputButton.tabIndex = hInput.tabIndex + 1
		hInputButton.hAutocomplete = this

		var hNewInput = document.createElement( 'INPUT' )
		if( this.bAssociative )
		{
			hNewInput.name = cAutocomplete.CS_INPUT_PREFIX + sInputName
		}
		else
		{
			hNewInput.name = sInputName
		}

		hNewInput.type = 'text'
		hNewInput.value = sValue
		hNewInput.style.width = nWidth-20
		hNewInput.className = cAutocomplete.CS_INPUT_CLASSNAME
		hNewInput.tabIndex = hInput.tabIndex
		hNewInput.hAutocomplete = this

		hInputContainer.appendChild( hNewInput )
		hInputContainer.appendChild( hInputButton )

		hInput.parentNode.replaceChild( hInputContainer, hInput )

		hNewInput.id = this.sInputId
		hInput = hNewInput
	}

	if( hInput.attachEvent )
	{
		hInput.attachEvent( 'onkeyup', cAutocomplete.onInputKeyUp )
		hInput.attachEvent( 'onkeyup', cAutocomplete.saveCaretPosition )
		hInput.attachEvent( 'onkeydown', cAutocomplete.onInputKeyDown )
		hInput.attachEvent( 'onblur', cAutocomplete.onInputBlur )
		hInput.attachEvent( 'onfocus', cAutocomplete.onInputFocus )

		if( hInputButton )
		{
			hInputButton.attachEvent( 'onclick', cAutocomplete.onButtonClick )
		}
	}
	else if( hInput.addEventListener )
	{
		hInput.addEventListener( 'keyup', cAutocomplete.onInputKeyUp, false )
		hInput.addEventListener( 'keyup', cAutocomplete.saveCaretPosition, false )
		hInput.addEventListener( 'keydown', cAutocomplete.onInputKeyDown, false )
		hInput.addEventListener( 'keypress', cAutocomplete.onInputKeyPress, false )
		hInput.addEventListener( 'blur', cAutocomplete.onInputBlur, false )
		hInput.addEventListener( 'focus', cAutocomplete.onInputFocus, false )

		if( hInputButton )
		{
			hInputButton.addEventListener( 'click', cAutocomplete.onButtonClick, false )
		}
	}

	//I don't need the standard autocomplete
	hInput.setAttribute( 'autocomplete', 'OFF' )

	if( hForm )
	{
		if( hForm.attachEvent )
		{
			hForm.attachEvent( 'onsubmit', cAutocomplete.onFormSubmit )
		}
		else if( hForm.addEventListener )
		{
			hForm.addEventListener( 'submit', cAutocomplete.onFormSubmit, false )
		}
	}
}

cAutocomplete.prototype.initListContainer = function()
{
	var hInput = document.getElementById( this.sInputId )
	var hContainer = document.createElement( 'DIV' )
	hContainer.className = 'autocomplete_holder'
	hContainer.id = this.sListId
	hContainer.style.zIndex = 10000 + cAutocomplete.nCount
	hContainer.hAutocomplete = this

	var hFirstBorder =  document.createElement( 'DIV' )
	hFirstBorder.className = 'autocomplete_firstborder'
	var hSecondBorder =  document.createElement( 'DIV' )
	hSecondBorder.className = 'autocomplete_secondborder'

	var hList = document.createElement( 'UL' )
	hList.className = 'autocomplete'

	hSecondBorder.appendChild( hList )
	hFirstBorder.appendChild( hSecondBorder )
	hContainer.appendChild( hFirstBorder )
	document.body.appendChild( hContainer )

	if( hContainer.attachEvent )
	{
		hContainer.attachEvent( 'onblur', cAutocomplete.onListBlur )
		hContainer.attachEvent( 'onfocus', cAutocomplete.onListFocus )
	}
	else if( hInput.addEventListener )
	{
		hContainer.addEventListener( 'blur', cAutocomplete.onListBlur, false )
		hContainer.addEventListener( 'focus', cAutocomplete.onListFocus, false )
	}


	if( hContainer.attachEvent )
	{
		hContainer.attachEvent( 'onclick', cAutocomplete.onItemClick )
	}
	else if( hContainer.addEventListener )
	{
		hContainer.addEventListener( 'click', cAutocomplete.onItemClick, false )
	}
}

cAutocomplete.prototype.createList = function()
{
	var hInput = document.getElementById( this.sInputId )
	var hContainer = document.getElementById( this.sListId )
	var hList = hContainer.getElementsByTagName( 'UL' )[0]
	if( hList )
	{
		hList = hList.parentNode.removeChild( hList )
		while( hList.hasChildNodes() )
		{
			hList.removeChild( hList.childNodes[ 0 ] )
		}
	}

	var hListItem = null
	var hListItemLink = null
	var hArrKey = null
	var sArrEl = null

	var hArr = this.aData
	var nI = 0
	for( hArrKey in hArr )
	{
		sArrEl = hArr[ hArrKey ]
		hListItem = document.createElement( 'LI' )
		hListItemLink = document.createElement( 'A' )
		hListItemLink.setAttribute( 'itemvalue', hArrKey )
		hListItemLink.href = '#'
		hListItemLink.appendChild( document.createTextNode( sArrEl ) )
		hListItemLink.realText = sArrEl
		if( nI == this.nSelectedItemIdx )
		{
			this.hActiveSelection = hListItemLink
			this.hActiveSelection.className = 'selected'
		}
		hListItem.appendChild( hListItemLink )
		hList.appendChild( hListItem )
		this.aSearchData[ nI++ ] = sArrEl.toLowerCase()
	}
	var hSecondBorder = hContainer.firstChild.firstChild
	hSecondBorder.appendChild( hList )
	this.bListUpdated = false
}

/* list array functions */

cAutocomplete.prototype.initListArray = function()
{
	var hInput = document.getElementById( this.sInputId )
	var hArr = null

	if( hInput.type.toLowerCase() == 'select-one' )
	{
		hArr = new Object()
		for( var nI = 0; nI < hInput.options.length; nI++ )
		{
			hArrKey = hInput.options.item( nI ).value
			sArrEl = hInput.options.item( nI ).text
		    hArr[ hArrKey ] = sArrEl
			if( hInput.options.item( nI ).selected )
			{
			    this.nSelectedItemIdx = nI
			}
		}
	}
	else
	{
		var sAA = hInput.getAttribute( 'autocomplete_list' )
		var sAAS = hInput.getAttribute( 'autocomplete_list_sort' )

		var sArrayType = this.getListArrayType()

		switch( sArrayType )
		{
			case 'array'	:	hArr = eval( sAA.substring( 6 ) )
								break

			case 'list'		:	hArr = new Array()
								var hTmpArray = sAA.substring( 5 ).split( '|' )
								var aValueArr
								for( hKey in hTmpArray )
								{
									aValueArr = hTmpArray[ hKey ].split( cAutocomplete.CS_ARRAY_SEPARATOR )
									if( aValueArr.length == 1 )
									{
										hArr[ hKey ] = hTmpArray[ hKey ]
										this.bAssociative = false
									}
									else
									{
										hArr[ aValueArr[ 0 ] ] = aValueArr[ 1 ]
									}
								}
								break
		}
		if( sAAS != null && eval( sAAS ) )
		{
			this.bSorted = true
			this.aData = hArr.sort()
			hArr = hArr.sort()
		}
	}
	this.setArray( hArr )
}

cAutocomplete.prototype.setArray = function( sArray )
{
	if( typeof sArray == 'string' )
	{
		this.aData = eval( sArray )
	}
	else
	{
		this.aData = sArray
	}
	this.bListUpdated = true
}

//use this function to change the list of autocomplete values to a new one
//supply as an argument the name as a literal of an JS array object
//well things changed - you can supply  an actual array too
cAutocomplete.prototype.setListArray = function( sArray )
{
	this.setArray( sArray )
	this.updateAndShowList()
}

cAutocomplete.prototype.getListArrayType = function()
{
	var hInput = document.getElementById( this.sInputId )
	var sAA = hInput.getAttribute( 'autocomplete_list' )
	if( sAA != null && sAA.length > 0 )
	{
		if( sAA.indexOf( 'array:' ) >= 0 )
		{
			return 'array'
		}
		else if(  sAA.indexOf( 'list:' ) >= 0 )
		{
			return 'list'
		}
		else if(  sAA.indexOf( 'url:' ) >= 0 )
		{
			return 'url'
		}
		else if(  sAA.indexOf( 'xmlrpc:' ) >= 0 )
		{
			return 'xmlrpc'
		}
	}
}

cAutocomplete.prototype.getListURL = function()
{
	var hInput = document.getElementById( this.sInputId )
	var sAA = hInput.getAttribute( 'autocomplete_list' )
	if( sAA != null && sAA.length > 0 )
	{
		if(  sAA.indexOf( 'url:' ) >= 0 )
		{
			return sAA.substring( 4 )
		}
		if(  sAA.indexOf( 'xmlrpc:' ) >= 0 )
		{
			return sAA.substring( 7 )
		}
	}
}

cAutocomplete.prototype.setListURL = function( sURL )
{
	this.sListURL = sURL;
}

cAutocomplete.onXmlHttpLoad = function( hThis )
{
	if( hThis.hXMLHttp.readyState == 4 )
	{
		var hError = hThis.hXMLHttp.parseError
		if( hError && hError.errorCode != 0 )
		{
			alert( hError.reason )
		}
		else
		{
		    if (hthis.bXMLRPC)
		    {
			hThis.afterRemoteLoadXMLRPC()
		    }
		    else 
		    {
			hThis.afterRemoteLoad()
		    }
		}
	}
}

cAutocomplete.prototype.loadListArray = function()
{
	var sURL = this.sListURL
	var sStartWith = this.getStringForAutocompletion( this.sActiveValue, this.nInsertPoint )
	sStartWith = sStartWith.replace( /^\s/, '' )
	sStartWith = sStartWith.replace( /\s$/, '' )
	if( sURL.indexOf( '[S]' ) >= 0 )
	{
		sURL = sURL.replace( '[S]', sStartWith )
	}
	else
	{
		sURL += this.sActiveValue
	}
	this.hXMLHttp.open( 'GET', sURL, true )
	this.hXMLHttp.onreadystatechange = new Function( 'var sAC = "'+this.sObjName+'"; cAutocomplete.onXmlHttpLoad( eval( sAC ) )' )
	this.hXMLHttp.send( null )
}

cAutocomplete.prototype.afterRemoteLoad = function()
{
	var hInput = document.getElementById( this.sInputId )

	var hArr = new Array()
	var hTmpArray = this.hXMLHttp.responseText.split( '|' )
	var aValueArr
	for( hKey in hTmpArray )
	{
		aValueArr = hTmpArray[ hKey ].split( cAutocomplete.CS_ARRAY_SEPARATOR )
		if( aValueArr.length == 1 )
		{
			hArr[ hKey ] = hTmpArray[ hKey ]
		}
		else
		{
			hArr[ aValueArr[ 0 ] ] = aValueArr[ 1 ]
		}
	}

	hInput.className = ''
	hInput.readonly = false
	hInput.value = this.sActiveValue
	this.setListArray( hArr )
}

cAutocomplete.prototype.afterRemoteLoadXMLRPC = function()
{
	var hInput = document.getElementById( this.sInputId )

	var hArr = new Array()
	/* how does the response XML look like? */
	var hTmpArray = this.hXMLHttp.documentElement.getElementsByTagName("array");
	for( hKey in hTmpArray )
	{
	    if (hTmpArray[ hKey ].getAttribute("string"))
	        hArr[ hKey ] = hTmpArray[ hKey ].getAttribute("string")
	}

	hInput.className = ''
	hInput.readonly = false
	hInput.value = this.sActiveValue
	this.setListArray( hArr )
}

/**/

cAutocomplete.prototype.prepareList = function( bFullList )
{
	var hInput = document.getElementById( this.sInputId )
	this.sActiveValue = hInput.value

	//check if this was invoked by a key that did not change the value
	var sST = this.getStringForAutocompletion( this.sActiveValue, this.nInsertPoint )
	var sLST = this.getStringForAutocompletion( this.sLastActiveValue, this.nInsertPoint )

	if( sLST != sST || bFullList || !this.bListDisplayed || this.bMatchSubstring  )
	{
		if( this.bRemoteList )
		{
			hInput.className = 'search'
			hInput.readonly = true
			hInput.value = 'please wait...'
			this.loadListArray()
			return
		}
		this.updateAndShowList( bFullList )
	}
}

cAutocomplete.prototype.updateAndShowList = function( bFullList )
{
	var hContainer = document.getElementById( this.sListId )
	var hList = hContainer.getElementsByTagName( 'UL' )[ 0 ]
	var hInput = document.getElementById( this.sInputId )

	if( this.bListUpdated )
	{
		this.createList()
	}

	//stupid hack just for speed
	var sST = this.bMatchSubstring ? this.getStringForAutocompletion( this.sActiveValue, this.nInsertPoint ) : this.sActiveValue
	var sLST = this.bMatchSubstring ? this.getStringForAutocompletion( this.sLastActiveValue, this.nInsertPoint ) : this.sLastActiveValue

	//nothing changed since last type - maybe only function keys were pressed
	//this is the case when for example the down key was pressed
	if( sST == sLST )
	{
		if( !this.bMatchSubstring )
		{
			bFullList = true
		}
	}
	this.filterOptions( bFullList )

	if( this.nItemsDisplayed == 0 )
	{
		if( this.bForceCorrect )
		{
			var aPos = this.getInsertPos( this.sActiveValue, this.nInsertPoint, '' )
			cAutocomplete.markInputRange( hInput, this.nLastMatchLength, aPos[0] )
		}
	}

	this.sLastActiveValue = this.sActiveValue

	if( this.nItemsDisplayed > 0 )
	{
		if( !bFullList || this.bMatchSubstring )
		{
			this.deselectOption()
		}
		if( this.bAutoComplete && this.nItemsDisplayed == 1 )
		{
			//test if we have a full match i.e. the user typed the entire value
			var sStartWith = this.getStringForAutocompletion( this.sActiveValue, this.nInsertPoint )
			var sItemText = hList.getElementsByTagName( 'LI' )[ this.nFirstDisplayed ].getElementsByTagName( 'A' )[ 0 ].realText
			if( sStartWith.toLowerCase() == sItemText.toLowerCase() )
			{
				this.selectOption( hList.getElementsByTagName( 'LI' )[ this.nFirstDisplayed ].getElementsByTagName( 'A' )[ 0 ] )
				this.hideOptions()
				//and do not show the list
				return
			}
		}
		if( this.bAutoComplete && !bFullList )
		{
			this.selectOption( hList.getElementsByTagName( 'LI' )[ this.nFirstDisplayed ].getElementsByTagName( 'A' )[ 0 ] )
		}
		this.showList()
	}
	else
	{
		this.clearList()
	}
}

cAutocomplete.prototype.showList = function()
{
	if( cAutocomplete.hListDisplayed )
	{
		cAutocomplete.hListDisplayed.clearList()
	}
	var hInput = document.getElementById( this.sInputId )
	var nTop = cDomObject.getOffsetParam( hInput, 'offsetTop' )
	var nLeft = cDomObject.getOffsetParam( hInput, 'offsetLeft' )
	var hContainer = document.getElementById( this.sListId )

	var hList = hContainer.getElementsByTagName( 'UL' )[ 0 ]
	if( this.bHasButton )
	{
		hContainer.style.width = document.getElementById( this.sInputId ).parentNode.offsetWidth
	}
	else
	{
		hContainer.style.width = document.getElementById( this.sInputId ).offsetWidth
	}
	var nNumLines = ( this.nItemsDisplayed < cAutocomplete.CN_NUMBER_OF_LINES ) ? this.nItemsDisplayed : cAutocomplete.CN_NUMBER_OF_LINES;
	hList.style.height = nNumLines * cAutocomplete.CN_LINE_HEIGHT + cAutocomplete.CN_HEIGHT_FIX + 'px'

	hContainer.style.top = nTop + hInput.offsetHeight + cAutocomplete.CN_OFFSET_TOP + 'px'
	hContainer.style.left = nLeft + cAutocomplete.CN_OFFSET_LEFT + 'px'

	hContainer.style.display = 'none'
	hContainer.style.visibility = 'visible'
	hContainer.style.display = 'block'

	cAutocomplete.hListDisplayed = this
	this.bListDisplayed = true
}

cAutocomplete.prototype.binarySearch = function( sFilter )
{
	var nLow = 0
	var nHigh = this.aSearchData.length - 1
	var nMid
	var nTry, nLastTry
	var sData
	var nLen = sFilter.length

	var lastTry

	while ( nLow <= nHigh )
	{
		nMid = ( nLow + nHigh ) / 2
		nTry = ( nMid < 1 ) ? 0 : parseInt( nMid )

		sData = this.aSearchData[ nTry ].substr( 0, nLen )

		if ( sData < sFilter )
		{
			nLow = nTry + 1
			continue
		}
		if ( sData > sFilter )
		{
			nHigh = nTry - 1
			continue
		}
		if ( sData == sFilter )
		{
			nHigh = nTry - 1
			nLastTry = nTry
			continue
		}
		return nTry
	}

	if ( typeof ( nLastTry ) != "undefined" )
	{
		return nLastTry
	}
	else
	{
		return null
	}
}

cAutocomplete.prototype.getStringForAutocompletion = function( sString, nPos )
{
	if( sString == null || sString.length == 0 )
	{
		return ''
	}
	if( this.bMatchSubstring )
	{
		var nStartPos = sString.lastIndexOf( cAutocomplete.CS_SEPARATOR, nPos - 1 )
		nStartPos = nStartPos < 0 ? 0 : nStartPos
		var nEndPos = sString.indexOf( cAutocomplete.CS_SEPARATOR, nPos )
		nEndPos = nEndPos < 0 ? sString.length : nEndPos
		var sStr = sString.substr( nStartPos, nEndPos - nStartPos )
		sStr = sStr.replace( /^(\,?)(\s*)(\S*)(\s*)(\,?)$/g, '$3' )
		return sStr
	}
	else
	{
		return sString
	}
}

cAutocomplete.prototype.insertString = function( sString, nPos, sInsert )
{
	if( this.bMatchSubstring )
	{
		var nStartPos = sString.lastIndexOf( cAutocomplete.CS_SEPARATOR, nPos - 1 )
		nStartPos = nStartPos < 0 ? 0 : nStartPos
		var nEndPos = sString.indexOf( cAutocomplete.CS_SEPARATOR, nPos )
		nEndPos = nEndPos < 0 ? sString.length : nEndPos
		var sStr = sString.substr( nStartPos, nEndPos - nStartPos )
		sStr = sStr.replace( /^(\,?)(\s*)(\S?[\S\s]*\S?)(\s*)(\,?)$/g, '$1$2'+sInsert+'$4$5' )
		sStr = sString.substr( 0, nStartPos ) + sStr + sString.substr( nEndPos )
		return sStr
	}
	else
	{
		return sInsert
	}
}

cAutocomplete.prototype.getInsertPos = function( sString, nPos, sInsert )
{
	nPos = nPos == null ? 0 : nPos
	var nStartPos = sString.lastIndexOf( cAutocomplete.CS_SEPARATOR, nPos - 1 )
	nStartPos = nStartPos < 0 ? 0 : nStartPos
	var nEndPos = sString.indexOf( cAutocomplete.CS_SEPARATOR, nPos )
	nEndPos = nEndPos < 0 ? sString.length : nEndPos
	var sStr = sString.substr( nStartPos, nEndPos - nStartPos )
	sStr = sStr.replace( /^(\,?)(\s*)(\S?[\S\s]*\S?)(\s*)(\,?)$/g, '$1$2'+sInsert )
	return [ nPos, nStartPos + sStr.length ]
}

cAutocomplete.prototype.filterOptions = function( bShowAll )
{
	if( this.hActiveSelection && !bShowAll )
	{
		this.hActiveSelection.className = ''
	}
	if( typeof bShowAll == 'undefined' )
	{
		bShowAll = false
	}

	var hInput = document.getElementById( this.sInputId )

	var sStartWith = this.getStringForAutocompletion( this.sActiveValue, this.nInsertPoint )
	if( bShowAll )
	{
		sStartWith = ''
	}

	var hContainer = document.getElementById( this.sListId )
	var hList = hContainer.getElementsByTagName( 'UL' )[ 0 ]
	var nItemsLength = hList.childNodes.length
	var hLinkItem = null
	var nCount = 0

	var hParent = hList.parentNode
	var hList = hList.parentNode.removeChild( hList )
	var hTItems = hList.childNodes

	this.nItemsDisplayed = 0

	if( sStartWith.length == 0 )
	{
		for( var nI = 0; nI < nItemsLength; nI++ )
		{
			if( this.formatOptions )
			{
				hTItems[ nI ].childNodes[0].innerHTML = this.formatOptions( hTItems[ nI ].childNodes[0].realText, nI )
			}
			hTItems[ nI ].style.display = 'block'
		}

		nCount = nItemsLength

		if( nItemsLength > 0 )
		{
			this.nFirstDisplayed = 0
			this.nLastDisplayed = nItemsLength - 1
		}
		else
		{
			this.nFirstDisplayed = this.nLastDisplayed = -1
		}

		//this.nLastMatchLength = 0
		var aPos = this.getInsertPos( this.sActiveValue, this.nInsertPoint, sStartWith )
		this.nLastMatchLength = aPos[0]
	}
	else
	{
		this.nFirstDisplayed = this.nLastDisplayed = -1
		sStartWith = sStartWith.toLowerCase()
		var bEnd = false
		if( this.bSorted && this.bMatchBegin )
		{
			var nStartAt = this.binarySearch( sStartWith )
			for( var nI = 0; nI < nItemsLength; nI++ )
			{
				hTItems[ nI ].style.display = 'none'
				if( nI >= nStartAt && !bEnd )
				{
					if( !bEnd && this.aSearchData[ nI ].indexOf( sStartWith ) != 0 )
					{
						bEnd = true
						continue
					}
					if( this.formatOptions )
					{
						hTItems[ nI ].childNodes[0].innerHTML = this.formatOptions( hTItems[ nI ].childNodes[0].realText, nI )
					}
					hTItems[ nI ].style.display = 'block'
					nCount++
					if( this.nFirstDisplayed < 0 )
					{
						this.nFirstDisplayed = nI
					}
					this.nLastDisplayed = nI
				}
			}
		}
		else
		{
			for( var nI = 0; nI < nItemsLength; nI++ )
			{
				hTItems[ nI ].style.display = 'none'
				if( ( this.bMatchBegin && this.aSearchData[ nI ].indexOf( sStartWith ) == 0 ) || ( !this.bMatchBegin && this.aSearchData[ nI ].indexOf( sStartWith ) >= 0 ) )
				{
					if( this.formatOptions )
					{
						hTItems[ nI ].childNodes[0].innerHTML = this.formatOptions( hTItems[ nI ].childNodes[0].realText, nI )
					}
					hTItems[ nI ].style.display = 'block'
					nCount++
					if( this.nFirstDisplayed < 0 )
					{
						this.nFirstDisplayed = nI
					}
					this.nLastDisplayed = nI
				}
			}
		}

		if( nCount > 0 )
		{
			//this.nLastMatchLength = this.sActiveValue.length
			var aPos = this.getInsertPos( this.sActiveValue, this.nInsertPoint, sStartWith )
			this.nLastMatchLength = aPos[0]
		}
	}
	hParent.appendChild( hList )
	this.nItemsDisplayed = nCount
}

cAutocomplete.prototype.hideOptions = function()
{
	var hContainer = document.getElementById( this.sListId )
	hContainer.style.visibility = 'hidden'
	cAutocomplete.hListDisplayed = null
}

cAutocomplete.prototype.markAutocompletedValue = function()
{
	var hInput = document.getElementById( this.sInputId )
	var sValue = this.hActiveSelection.realText
	if( this.bMatchSubstring )
	{
		var aPos = this.getInsertPos( this.sLastActiveValue, this.nInsertPoint, sValue )
		var nStartPos = aPos[ 0 ]
		var nEndPos = aPos[ 1 ]
	}
	else
	{
		var nStartPos = this.nInsertPoint
		var nEndPos = sValue.length
	}
	this.nStartAC = nStartPos
	this.nEndAC = nEndPos

	if( this.hMarkRangeTimeout != null )
	{
		clearTimeout( this.hMarkRangeTimeout )
	}
	this.hMarkRangeTimeout = setTimeout( 'cAutocomplete.markInputRange2("'+hInput.id+'")', cAutocomplete.CN_MARK_TIMEOUT )
	//cAutocomplete.markInputRange( hInput, nStartPos, nEndPos )
}

cAutocomplete.prototype.selectOptionByIndex = function( nOptionIndex )
{
	if( this.bListUpdated )
	{
		this.createList()
	}

	var hContainer = document.getElementById( this.sListId )
	var hList = hContainer.getElementsByTagName( 'UL' )[ 0 ]
	var nItemsLength = hList.childNodes.length
	if( nOptionIndex >=0 && nOptionIndex < nItemsLength )
	{
		this.selectOption( hList.childNodes[ nOptionIndex ].getElementsByTagName( 'A' )[ 0 ] )
	}
}

cAutocomplete.prototype.selectOption = function( hNewOption )
{
	if( this.hActiveSelection )
	{
		if( this.hActiveSelection == hNewOption )
		{
			return
		}
		else
		{
			this.hActiveSelection.className = ''
		}
	}
	this.hActiveSelection = hNewOption
	var hInput = document.getElementById( this.sInputId )
	if( this.hActiveSelection != null )
	{
		if( this.sHiddenInputId != null )
		{
			if( this.bMatchSubstring )
			{
				document.getElementById( this.sHiddenInputId ).value = this.hActiveSelection.getAttribute( 'itemvalue' )
			}
			else
			{
				document.getElementById( this.sHiddenInputId ).value = this.hActiveSelection.getAttribute( 'itemvalue' )
			}
		}

		this.hActiveSelection.className = 'selected'
		if( this.bAutoComplete )
		{
			hInput.value = this.insertString( this.sLastActiveValue, this.nInsertPoint, this.hActiveSelection.realText )
			this.bAutocompleted = true
			this.markAutocompletedValue()
		}
		else
		{
			hInput.value = this.insertString( this.sActiveValue, this.nInsertPoint, this.hActiveSelection.realText )
			cAutocomplete.setInputCaretPosition( hInput, this.nInsertPoint )
		}

		this.sActiveValue = hInput.value

		if( this.onSelect )
		{
			this.onSelect()
		}
	}
	else
	{
		hInput.value = this.sActiveValue
		cAutocomplete.setInputCaretPosition( hInput, this.nInsertPoint )
	}
}

cAutocomplete.prototype.deselectOption = function( )
{
	if( this.hActiveSelection != null )
	{
		this.hActiveSelection.className = ''
		this.hActiveSelection = null
	}
}

cAutocomplete.prototype.clearList = function()
{
	//this.deselectOption()
	this.hideOptions()
	this.bListDisplayed = false
}

cAutocomplete.prototype.getPrevDisplayedItem = function( hItem )
{
	if( hItem == null )
	{
		var hContainer = document.getElementById( this.sListId )
		hItem = hContainer.getElementsByTagName( 'UL' )[ 0 ].childNodes.item( hContainer.getElementsByTagName( 'UL' )[ 0 ].childNodes.length - 1 )
	}
	else
	{
		hItem = getPrevNodeSibling( hItem.parentNode )
	}
	while( hItem != null )
	{
		if( hItem.style.display == 'block' )
		{
			return hItem
		}
		hItem = hItem.previousSibling
	}
	return null
}

cAutocomplete.prototype.getNextDisplayedItem = function( hItem )
{
	if( hItem == null )
	{
		var hContainer = document.getElementById( this.sListId )
		hItem = hContainer.getElementsByTagName( 'UL' )[ 0 ].childNodes.item( 0 )
	}
	else
	{
		hItem =  getNextNodeSibling( hItem.parentNode )
	}
	while( hItem != null )
	{
		if( hItem.style.display == 'block' )
		{
			return hItem
		}
		hItem = hItem.nextSibling
	}
	return null
}

cAutocomplete.onInputKeyDown = function ( hEvent )
{
	if( hEvent == null )
	{
		hEvent = window.event
	}
	var hElement = ( hEvent.srcElement ) ? hEvent.srcElement : hEvent.originalTarget
	var hAC = hElement.hAutocomplete
	var hContainer = document.getElementById( hAC.sListId )
	var hInput = document.getElementById( hAC.sInputId )
	var hList = hContainer.getElementsByTagName( 'UL' )[ 0 ]
	var hEl = getParentByTagName( hElement, 'A' )
	if( hContainer != null && hAC.bListDisplayed )
	{
		var hLI = null
		var hLINext = null
		//the new active selection
		if( ( hEvent.keyCode == 13 ) || ( hEvent.keyCode == 27 ) )
		{
			var bItemSelected = hEvent.keyCode == 13 ? true : false
			hAC.clearList()
		}
		if( hEvent.keyCode == 38 )
		{
			//up key pressed
			hLINext = hAC.getPrevDisplayedItem( hAC.hActiveSelection )
			if( hLINext != null )
			{
				hAC.selectOption( hLINext.childNodes.item(0) )
				if( hAC.nItemsDisplayed > cAutocomplete.CN_NUMBER_OF_LINES )
				{
					if( hList.scrollTop < 5 && hLINext.offsetTop > hList.offsetHeight )
					{
						hList.scrollTop = hList.scrollHeight - hList.offsetHeight
					}
					if( hLINext.offsetTop - hList.scrollTop < 0 )
					{
						hList.scrollTop -= hLINext.offsetHeight
					}
				}
			}
			else
			{
				hAC.selectOption( null )
			}
		}
		else if ( hEvent.keyCode == 40 )
		{
			//down key pressed
			hLINext = hAC.getNextDisplayedItem( hAC.hActiveSelection )
			if( hLINext != null )
			{
				hAC.selectOption( hLINext.childNodes.item(0) )
				if( hAC.nItemsDisplayed > cAutocomplete.CN_NUMBER_OF_LINES )
				{
					if( hList.scrollTop > 0 && hList.scrollTop > hLINext.offsetTop )
					{
						hList.scrollTop = 0
					}
					if( Math.abs( hLINext.offsetTop - hList.scrollTop - hList.offsetHeight ) < 5 )
					{
						hList.scrollTop += hLINext.offsetHeight
					}
				}
			}
			else
			{
				hAC.selectOption( null )
			}
		}
	}
	if( hInput.form )
	{
		hInput.form.bLocked = true
	}
	if ( hEvent.keyCode == 13 || hEvent.keyCode == 27 )
	{
		if( hEvent.preventDefault )
		{
			hEvent.preventDefault()
		}
		hEvent.cancelBubble = true
		hEvent.returnValue = false
		return false
	}
}

cAutocomplete.onInputKeyPress = function ( hEvent )
{
	if ( hEvent.keyCode == 13 )
	{
		if( hEvent.preventDefault )
		{
			hEvent.preventDefault()
		}
		hEvent.cancelBubble = true
		hEvent.returnValue = false
		return false
	}
}

cAutocomplete.onInputKeyUp = function ( hEvent )
{
	if( hEvent == null )
	{
		hEvent = window.event
	}
	var hElement = ( hEvent.srcElement ) ? hEvent.srcElement : hEvent.originalTarget
	var hAC = hElement.hAutocomplete
	var hInput = document.getElementById( hAC.sInputId )
	//if we press the keys for up down enter or escape skip showing the list
	switch( hEvent.keyCode )
	{
		case 8	:	if( hAC.bAutoComplete && hAC.bAutocompleted )
					{
						hAC.bAutocompleted = false
						return false
					}
					break
		case 38	:
		case 40	:	if( hAC.bListDisplayed )
					{
						if( hEvent.preventDefault )
						{
							hEvent.preventDefault()
						}
						hEvent.cancelBubble = true
						hEvent.returnValue = false
						return false
					}
					break
		case 32	:
		case 46	:
		case 37	:
		case 39	:
		case 35	:
		case 36	:	break;
		default	:	if( hEvent.keyCode < 48 )
					{
						if( hEvent.preventDefault )
						{
							hEvent.preventDefault()
						}
						hEvent.cancelBubble = true
						hEvent.returnValue = false
						return false
					}
					break
	}

	if( this.hMarkRangeTimeout != null )
	{
		clearTimeout( this.hMarkRangeTimeout )
	}

	if( hAC.hShowTimeout )
	{
		clearTimeout( hAC.hShowTimeout )
		hAC.hShowTimeout = null
	}
	var nTimeout = this.bRemoteList ? cAutocomplete.CN_REMOTE_SHOW_TIMEOUT : cAutocomplete.CN_SHOW_TIMEOUT
	hAC.hShowTimeout = setTimeout( hAC.hObj+'.prepareList()', nTimeout )
}

cAutocomplete.onInputBlur = function( hEvent )
{
	if( hEvent == null )
	{
		hEvent = window.event
	}
	var hElement = ( hEvent.srcElement ) ? hEvent.srcElement : hEvent.originalTarget
	if( hElement.form )
	{
		hElement.form.bLocked = false
	}
	var hAC = hElement.hAutocomplete
	if( !hAC.hClearTimeout )
	{
		hAC.hClearTimeout = setTimeout( hAC.hObj+'.clearList()', cAutocomplete.CN_CLEAR_TIMEOUT )
	}
}

cAutocomplete.onInputFocus = function( hEvent )
{
	if( hEvent == null )
	{
		hEvent = window.event
	}
	var hElement = ( hEvent.srcElement ) ? hEvent.srcElement : hEvent.originalTarget
	var hAC = hElement.hAutocomplete
	if( hAC.hClearTimeout )
	{
		clearTimeout( hAC.hClearTimeout )
		hAC.hClearTimeout = null
	}
}

cAutocomplete.saveCaretPosition = function( hEvent )
{
	if( hEvent == null )
	{
		hEvent = window.event
	}
	var hElement = ( hEvent.srcElement ) ? hEvent.srcElement : hEvent.originalTarget
	var hAC = hElement.hAutocomplete
	var hInput = document.getElementById( hAC.sInputId )

	//there is something weird about hitting up and down keys in a textarea
	if( hEvent.keyCode != 38 && hEvent.keyCode != 40 )
	{
		hAC.nInsertPoint = cAutocomplete.getInputCaretPosition( hInput )
	}
}

cAutocomplete.getInputCaretPosition = function( hInput )
{
	if( typeof hInput.selectionStart != 'undefined' )
	{
		if( hInput.selectionStart == hInput.selectionEnd )
		{
			return hInput.selectionStart
		}
		else
		{
			return hInput.selectionStart
		}
	}
	else if( hInput.createTextRange )
	{
		var hSelRange = document.selection.createRange()
		if( hInput.tagName.toLowerCase() == 'textarea' )
		{
			var hSelBefore = hSelRange.duplicate()
			var hSelAfter = hSelRange.duplicate()
			hSelRange.moveToElementText( hInput )
			hSelBefore.setEndPoint( 'StartToStart', hSelRange )
			return hSelBefore.text.length
		}
		else
		{
			hSelRange.moveStart( 'character', -1*hInput.value.length )
			var nLen = hSelRange.text.length
			return nLen
		}
	}
	return null
}

cAutocomplete.setInputCaretPosition = function( hInput, nPosition )
{
	if ( hInput.setSelectionRange )
	{
		hInput.setSelectionRange( nPosition ,nPosition )
	}
	else if ( hInput.createTextRange )
	{
		var hRange = hInput.createTextRange()
		hRange.moveStart( 'character', nPosition )
		hRange.moveEnd( 'character', nPosition )
		hRange.collapse(true)
		hRange.select()
	}
}

cAutocomplete.markInputRange = function( hInput, nStartPos, nEndPos )
{
	if( hInput.setSelectionRange )
	{
		hInput.focus()
		hInput.setSelectionRange( nStartPos, nEndPos )
	}
	else if( hInput.createTextRange )
	{
		var hRange = hInput.createTextRange()
		hRange.collapse(true)
		hRange.moveStart( 'character', nStartPos )
		hRange.moveEnd( 'character', nEndPos - nStartPos )
		hRange.select()
	}
}

cAutocomplete.markInputRange2 = function( sInputId )
{
	var hInput = document.getElementById( sInputId )
	var nStartPos = hInput.hAutocomplete.nStartAC
	var nEndPos = hInput.hAutocomplete.nEndAC
	cAutocomplete.markInputRange( hInput, nStartPos, nEndPos )
}


cAutocomplete.onListBlur = function( hEvent )
{
	if( hEvent == null )
	{
		hEvent = window.event
	}
	var hElement = ( hEvent.srcElement ) ? hEvent.srcElement : hEvent.originalTarget
	hElement = getParentByProperty( hElement, 'className', 'autocomplete_holder' )
	var hAC = hElement.hAutocomplete
	if( !hAC.hClearTimeout )
	{
		hAC.hClearTimeout = setTimeout( hAC.hObj+'.clearList()', cAutocomplete.CN_CLEAR_TIMEOUT )
	}
}

cAutocomplete.onListFocus = function( hEvent )
{
	if( hEvent == null )
	{
		hEvent = window.event
	}
	var hElement = ( hEvent.srcElement ) ? hEvent.srcElement : hEvent.originalTarget
	hElement = getParentByProperty( hElement, 'className', 'autocomplete_holder' )
	var hAC = hElement.hAutocomplete
	if( hAC.hClearTimeout )
	{
		clearTimeout( hAC.hClearTimeout )
		hAC.hClearTimeout = null
	}
}

cAutocomplete.onItemClick = function( hEvent )
{
	if( hEvent == null )
	{
		hEvent = window.event
	}
	var hElement = ( hEvent.srcElement ) ? hEvent.srcElement : hEvent.originalTarget
	var hContainer = getParentByProperty( hElement, 'className', 'autocomplete_holder' )
	var hEl = getParentByTagName( hElement, 'A' )
	if( hContainer != null )
	{
		var hAC = hContainer.hAutocomplete
		hAC.selectOption( hEl )
		document.getElementById( hAC.sInputId ).focus()
		hAC.clearList()
	}
	if( hEvent.preventDefault )
	{
		hEvent.preventDefault()
	}
	hEvent.cancelBubble = true
	hEvent.returnValue = false
	return false
}

cAutocomplete.onButtonClick = function ( hEvent )
{
	if( hEvent == null )
	{
		hEvent = window.event
	}
	var hElement = ( hEvent.srcElement ) ? hEvent.srcElement : hEvent.originalTarget
	var hAC = hElement.hAutocomplete
	var hInput = document.getElementById( hAC.sInputId )
	if( hInput.disabled )
	{
		return
	}
	hAC.prepareList( true )
	var hInput = document.getElementById( hAC.sInputId )
	hInput.focus()
}

cAutocomplete.onFormSubmit = function ( hEvent )
{
	if( hEvent == null )
	{
		hEvent = window.event
	}
	var hElement = ( hEvent.srcElement ) ? hEvent.srcElement : hEvent.originalTarget
	if( hElement.bLocked )
	{
		hElement.bLocked = false
		hEvent.returnValue = false
		if( hEvent.preventDefault )
		{
			hEvent.preventDefault()
		}
		return false
	}
}
