 /**
 * $Id: editor_plugin_src.js 42 2006-08-08 14:32:24Z spocke $
 *
 * @author Moxiecode - based on work by Andrew Tetlaw
 * @copyright Copyright � 2004-2008, Moxiecode Systems AB, All rights reserved.
 */

function init() {
	SXE.initElementDialog('ins');
	if (SXE.currentAction == "update") {
		setFormValue('datetime', tinyMCEPopup.editor.dom.getAttrib(SXE.updateElement, 'datetime'));
		setFormValue('cite', tinyMCEPopup.editor.dom.getAttrib(SXE.updateElement, 'cite'));
		SXE.showRemoveButton();
	}
}

function setElementAttribs(elm) {
	setAllCommonAttribs(elm);
	setAttrib(elm, 'datetime');
	setAttrib(elm, 'cite');
}

function insertIns() {
	var elm = tinyMCEPopup.editor.dom.getParent(SXE.focusElement, 'INS');
	tinyMCEPopup.execCommand('mceBeginUndoLevel');
	if (elm == null) {
		var s = SXE.inst.selection.getContent();
		if(s.length > 0) {
			tinyMCEPopup.execCommand('mceInsertContent', false, '<ins id="#sxe_temp_ins#">' + s + '</ins>');
			var elementArray = tinymce.grep(SXE.inst.dom.select('ins'), function(n) {return n.id == '#sxe_temp_ins#';});
			for (var i=0; i<elementArray.length; i++) {
				var elm = elementArray[i];
				setElementAttribs(elm);
			}
		}
	} else {
		setElementAttribs(elm);
	}
	tinyMCEPopup.editor.nodeChanged();
	tinyMCEPopup.execCommand('mceEndUndoLevel');
	tinyMCEPopup.close();
}

function removeIns() {
	SXE.removeElement('ins');
	tinyMCEPopup.close();
}

tinyMCEPopup.onInit.add(init);
