/*==================================================*
 $Id$

 CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
 Copyright (c) Xerox Corporation, CodeX, 2001-2004. All Rights Reserved
 http://codex.xerox.com

*===================================================*/

/**
 * show the block identified by id=idName (the page layout is changed)
 */
function showBlock(idName){ 
	if(document.getElementById) {//NN6,Mozilla,IE5?
		document.getElementById(idName).style.display = "block";; 
	}
	else if(document.all) {      //IE4?
		document.all(idName).style.display = "block";; 
	}
	else if(document.layers) {   //NN4?
		document.layers[idName].display = "block";; 
	}
}

/**
 * hide the block identified by id=idName (the page layout is changed)
 */
function hideBlock(idName){ 
	if(document.getElementById) {//NN6,Mozilla,IE5?
		document.getElementById(idName).style.display = "none";; 
	}
	else if(document.all) {      //IE4?
		document.all(idName).style.display = "none";; 
	}
	else if(document.layers) {   //NN4?
		document.layers[idName].display = "none";; 
	}
}

