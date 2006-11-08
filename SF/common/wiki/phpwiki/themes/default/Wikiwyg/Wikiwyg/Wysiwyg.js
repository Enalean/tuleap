/*==============================================================================
This Wikiwyg mode supports a DesignMode wysiwyg editor with toolbar buttons

COPYRIGHT:

    Copyright (c) 2005 Socialtext Corporation 
    655 High Street
    Palo Alto, CA 94301 U.S.A.
    All rights reserved.

Wikiwyg is free software. 

This library is free software; you can redistribute it and/or modify it
under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation; either version 2.1 of the License, or (at
your option) any later version.

This library is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser
General Public License for more details.

    http://www.gnu.org/copyleft/lesser.txt

 =============================================================================*/

proto = new Subclass('Wikiwyg.Wysiwyg', 'Wikiwyg.Mode');

proto.classtype = 'wysiwyg';
proto.modeDescription = 'Wysiwyg';

proto.config = {
    useParentStyles: true,
    useStyleMedia: 'wikiwyg',
    iframeId: null,
    iframeObject: null,
    disabledToolbarButtons: [],
    editHeightMinimum: 150,
    editHeightAdjustment: 1.3,
    editHeightOffset: 500,
    clearRegex: null
};
    
proto.initializeObject = function() {
    this.edit_iframe = this.get_edit_iframe();
    this.div = this.edit_iframe;
    this.set_design_mode_early();
}

proto.set_design_mode_early = function() { // Se IE, below
    // Unneeded for Gecko
}

proto.fromHtml = function(html) {
    this.set_inner_html(html);
}

// Added to allow wysiwyg mode by default
proto.fromWikitext = function(wikitext) {
  var edit_iframe = this.get_edit_document().body;

  this.wikiwyg.call_action('wikiwyg_wikitext_to_html', wikitext, 
			   function(html) {edit_iframe.innerHTML = html;} );
}

proto.toHtml = function(func) {
  func(this.get_inner_html());
}

// This is needed to work around the broken IMGs in Firefox design mode.
// Works harmlessly on IE, too.
// TODO - IMG URLs that don't match /^\//
proto.fix_up_relative_imgs = function() {
    var base = location.href.replace(/(.*?:\/\/.*?\/).*/, '$1');
    var imgs = this.get_edit_document().getElementsByTagName('img');
    for (var ii = 0; ii < imgs.length; ++ii)
        imgs[ii].src = imgs[ii].src.replace(/^\//, base);
}

proto.enableThis = function() {
    this.superfunc('enableThis').call(this);
    this.edit_iframe.style.border = '1px black solid';
    this.edit_iframe.width = '100%';
    this.setHeightOf(this.edit_iframe);
    this.fix_up_relative_imgs();
    this.get_edit_document().designMode = 'on';
    // XXX - Doing stylesheets in initializeObject might get rid of blue flash
    this.apply_stylesheets();
    this.enable_keybindings();
    this.clear_inner_html();
}

proto.clear_inner_html = function() {
    var inner_html = this.get_inner_html();
    var clear = this.config.clearRegex;
    if (clear && inner_html.match(clear))
        this.set_inner_html('');
}

proto.get_keybinding_area = function() {
    return this.get_edit_document();
}

proto.get_edit_iframe = function() {
    var iframe;
    if (this.config.iframeId) {
        iframe = document.getElementById(this.config.iframeId);
        iframe.iframe_hack = true;
    }
    else if (this.config.iframeObject) {
        iframe = this.config.iframeObject;
        iframe.iframe_hack = true;
    }
    else {
        // XXX in IE need to wait a little while for iframe to load up
        iframe = document.createElement('iframe');
    }
    return iframe;
}

proto.get_edit_window = function() { // See IE, below
    return this.edit_iframe.contentWindow;
}

proto.get_edit_document = function() { // See IE, below
    return this.get_edit_window().document;
}

proto.get_inner_html = function() {
    return this.get_edit_document().body.innerHTML;
}
proto.set_inner_html = function(html) {
    this.get_edit_document().body.innerHTML = html;
}

proto.apply_stylesheets = function(styles) {
    var styles = document.styleSheets;
    var head   = this.get_edit_document().getElementsByTagName("head")[0];

    for (var i = 0; i < styles.length; i++) {
        var style = styles[i];

        if (style.href == location.href)
            this.apply_inline_stylesheet(style, head);
        else
            if (this.should_link_stylesheet(style))
                this.apply_linked_stylesheet(style, head);
    }
}

proto.apply_inline_stylesheet = function(style, head) {
    // TODO: figure this out
}

proto.should_link_stylesheet = function(style, head) {
        var media = style.media;
        var config = this.config;
        var media_text = media.mediaText ? media.mediaText : media;
        var use_parent =
             ((!media_text || media_text == 'screen') &&
             config.useParentStyles);
        var use_style = (media_text && (media_text == config.useStyleMedia));
        if (!use_parent && !use_style) // TODO: simplify
            return false;
        else
            return true;
}

proto.apply_linked_stylesheet = function(style, head) {
    var link = Wikiwyg.createElementWithAttrs(
        'link', {
            href:  style.href,
            type:  style.type,
            media: 'screen',
            rel:   'STYLESHEET'
        }, this.get_edit_document()
    );
    head.appendChild(link);
}

proto.process_command = function(command) {
    if (this['do_' + command])
        this['do_' + command](command);
    if (! Wikiwyg.is_ie)
        this.get_edit_window().focus();
}

proto.exec_command = function(command, option) {
    this.get_edit_document().execCommand(command, false, option);
}

proto.format_command = function(command) {
    this.exec_command('formatblock', '<' + command + '>');
}

proto.do_bold = proto.exec_command;
proto.do_italic = proto.exec_command;
proto.do_underline = proto.exec_command;
proto.do_strike = function() {
    this.exec_command('strikethrough');
}
proto.do_hr = function() {
    this.exec_command('inserthorizontalrule');
}
proto.do_ordered = function() {
    this.exec_command('insertorderedlist');
}
proto.do_unordered = function() {
    this.exec_command('insertunorderedlist');
}
proto.do_indent = proto.exec_command;
proto.do_outdent = proto.exec_command;

proto.do_h1 = proto.format_command;
proto.do_h2 = proto.format_command;
proto.do_h3 = proto.format_command;
proto.do_h4 = proto.format_command;
proto.do_h5 = proto.format_command;
proto.do_h6 = proto.format_command;
proto.do_pre = proto.format_command;
proto.do_p = proto.format_command;

proto.do_table = function() {
    var rows = prompt("Number of rows");
    var cols = prompt("Number of columns");

    // Test if variables are valid numbers
    if( isNaN(rows) == true || isNaN(cols) == true 
	||(rows = parseInt(rows).toFixed(0)) <= 0
	||(cols = parseInt(cols).toFixed(0)) <= 0)
      {
	alert("Please enter positive numbers");
	return false;
      }

    var html = 
    '<table border="5"><tbody>';
  
    for(i=0; i<rows;i++) {
      html = html + "<tr>";
      for(var j=0;j<cols;j++) {
 	html = html + "<td>&nbsp;</td>";
       }
      html = html + "</tr>";
    }

    html = html + "<tbody></table>";
   
    if (! Wikiwyg.is_ie)
        this.get_edit_window().focus();
    this.insert_table(html);
}

proto.insert_table = function(html) { // See IE
    this.exec_command('inserthtml', html);
}

proto.do_toc = function() {
    var html = '<p><div style="background-color:#D3D3D3;font-size:smaller;">'+
               'Wikitext { <br> &lt;?plugin  CreateToc  ?&gt; <br>}</div><br></p>';
    this.insert_html(html);
}

proto.do_wikitext = function() {
    var html = '<p><div style="background-color:#D3D3D3;font-size:smaller;">'+
               'Wikitext { <br>  <br>}</div><br></p>';
    this.insert_html(html);
}

proto.insert_html = function(html) { // See IE
    this.exec_command('inserthtml', html);
}

proto.do_link = function() {
    var selection = this.get_link_selection_text();
    if (! selection) return;

    var url = "";
    var url_selection = "";

    if(Wikiwyg.is_ie) {
      url_selection = this.get_edit_document().selection.createRange().htmlText;
    }
    else {
      if(this.get_edit_window().getSelection())
	url_selection = this.get_edit_window().getSelection();
    }

    // If IE, 
    if(Wikiwyg.is_ie) {
      var tmp_match = url_selection.match(/href=.*/m);
      if(tmp_match!=null) {
	var toreplace = /.*href=\"(.*?)\".*/m;
	url = tmp_match.toString().replace(toreplace,'$1');
      }
    }
    else {
      // If gecko
      if(url_selection.focusNode.innerHTML) {
	var reg_match = new RegExp('(href=\".*?\".*?'
				    +unescape(selection)
				    +').*?\/a\>','m');
	var tmp_match = url_selection.focusNode.innerHTML.match(reg_match);
	if(tmp_match!=null) {
	  var toreplace = /.*href=\"(.*?)\".*/m;
	  url = tmp_match.toString().replace(toreplace,'$1');
	}
      }
    }

    url = prompt('Enter the URL. No URL will remove the link.',unescape(url));
    if(url==null)
      return;
    else if (url) {
      this.exec_command('Unlink');
      this.exec_command('CreateLink',url);
      this.exec_command('ForeColor','blue');
    }
    else if(!url) {// No URL entered by the user
      this.exec_command('Unlink');
      this.exec_command('ForeColor','black');
    }
}

proto.get_selection_text = function() { // See IE, below
    return this.get_edit_window().getSelection().toString();
}

proto.get_link_selection_text = function() {
    var selection = this.get_selection_text();
    if (! selection) {
        alert("Please select the text you would like to turn into a link.");
        return;
    }
    return selection;
}

proto.do_sup = function() {
  this.exec_command('SuperScript');
}

proto.do_sub = function() {
  this.exec_command('SubScript');
}

/*==============================================================================
Support for Internet Explorer in Wikiwyg.Wysiwyg
 =============================================================================*/
if (Wikiwyg.is_ie) {

proto.set_design_mode_early = function(wikiwyg) {
    // XXX - need to know if iframe is ready yet...
    this.get_edit_document().designMode = 'on';
}

proto.get_edit_window = function() {
    return this.edit_iframe;
}

proto.get_edit_document = function() {
    return this.edit_iframe.contentWindow.document;
}

proto.get_selection_text = function() {
    var selection = this.get_edit_document().selection;
    if (selection != null)
        return selection.createRange().htmlText;
    return '';
}

proto.insert_table = function(html) {
    var doc = this.get_edit_document();
    var range = this.get_edit_document().selection.createRange();
    if (range.boundingTop == 2 && range.boundingLeft == 2)
        return;
    range.pasteHTML(html);
    range.collapse(false);
    range.select();
}

proto.insert_html = function(html) {
    var doc = this.get_edit_document();
    var range = this.get_edit_document().selection.createRange();
    if (range.boundingTop == 2 && range.boundingLeft == 2)
        return;
    range.pasteHTML(html);
    range.collapse(false);
    range.select();
}

// Use IE's design mode default key bindings for now.
proto.enable_keybindings = function() {}

} // end of global if
