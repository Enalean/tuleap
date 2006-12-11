/*==============================================================================

 * Copyright(c) STMicroelectronics, 2006
 *
 * Originally written by Jean-Nicolas GEREONE, STMicroelectronics, 2006. 

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

function getCookieVal(offset) {
    var endstr=document.cookie.indexOf (";", offset);
    if (endstr==-1) endstr=document.cookie.length;
    return unescape(document.cookie.substring(offset, endstr));
}

function readCookie(name) {
    var arg = name+"=";
    var alen = arg.length;
    var clen=document.cookie.length;
    var i=0;
    while (i<clen) {
      var j=i+alen;
      if (document.cookie.substring(i, j)==arg) return getCookieVal(j);
      i=document.cookie.indexOf(" ",i)+1;
      if (i==0) break;      
    }
    return null;
}

var wikiwyg_divs = [];

proto = new Subclass('Wikiwyg.Phpwiki', 'Wikiwyg');

proto.submit_action_form = function(action, value) {

}

// Convert to wikitext mode if needed
// and save changes in the textarea of phpwiki 
proto.saveChanges = function() {
    var self = this;
    var submit_changes = function(wikitext) {
      self.div.value = wikitext;
      self.submit_action_form(
			      'wikiwyg_save_wikitext',wikitext
			      );
    }  
    //   var self = this;
    if (this.current_mode.classname.match(/(Wysiwyg|Preview)/)) {
        this.current_mode.toHtml(
            function(html) {
                var wikitext_mode = self.mode_objects['Wikiwyg.Wikitext.Phpwiki'];
                wikitext_mode.convertHtmlToWikitext(
                    html,
                    function(wikitext) { submit_changes(wikitext) }
                );
            }
        );
    }
    else {
        submit_changes(this.current_mode.toWikitext());
    }
}

// Called in WYSIWYG mode by proto.do_preview_button to submit preview button 
// by clicking on the preview button of the toolbar
proto.previewChanges_button = function() {
    var self = this;
    var submit_preview = function(wikitext) {
        var form_element = document.getElementById('editpage');
	var input = document.createElement('input');
	input.setAttribute('type', 'hidden');
	input.setAttribute('name', 'edit[preview]');
	input.setAttribute('value', 'Preview');
	input.setAttribute('class', 'Wikiaction');
	form_element.appendChild(input);
	
	var input_content = document.createElement('input');
	input_content.setAttribute('type', 'hidden');
	input_content.setAttribute('name', 'edit[content]');
	input_content.setAttribute('value', wikitext);
	input_content.setAttribute('class', 'wikiedit');
	input_content.setAttribute('id', 'edit:content');
	form_element.appendChild(input_content);
	form_element.submit();
    }  

    this.current_mode.toHtml(
    function(html) {
      var wikitext_mode = self.mode_objects['Wikiwyg.Wikitext.Phpwiki'];
      wikitext_mode.convertHtmlToWikitext(html,
					  function(wikitext) { submit_preview(wikitext); }
					  );
    }
    );    
}

// Called in WYSIWYG mode by proto.do_save_button to submit save button 
// by clicking on the save button of the toolbar
proto.saveChanges_button = function() {
      var self = this;
      var submit_save = function(wikitext) {
      var form_element = document.getElementById('editpage');
      var input = document.createElement('input');
      input.setAttribute('type', 'hidden');
      input.setAttribute('name', 'edit[save]');
      input.setAttribute('value', 'Save');
      input.setAttribute('class', 'Wikiaction');
      form_element.appendChild(input);
      
      var input_content = document.createElement('input');
      input_content.setAttribute('type', 'hidden');
      input_content.setAttribute('name', 'edit[content]');
      input_content.setAttribute('value', wikitext);
      input_content.setAttribute('class', 'wikiedit');
      input_content.setAttribute('id', 'edit:content');
      form_element.appendChild(input_content);
      form_element.submit();
      }  
      
      this.current_mode.toHtml(
	   function(html) {
	     var wikitext_mode = self.mode_objects['Wikiwyg.Wikitext.Phpwiki'];
	     wikitext_mode.convertHtmlToWikitext(html,
	  		   function(wikitext) { submit_save(wikitext); }
						 );
	   }
	   );    
}

proto.modeClasses = [
		     'Wikiwyg.Wikitext.Phpwiki',
		     'Wikiwyg.Wysiwyg'
		     ];

//  Put the last edition mode of the user by default
if(document.cookie) {
    var mode = readCookie('Mode');
if( mode=="Wikiwyg.Wysiwyg" ) 
    proto.modeClasses = [
			 'Wikiwyg.Wysiwyg',
			 'Wikiwyg.Wikitext.Phpwiki'
			 ];
else if( mode=="Wikiwyg.Wikitext.Phpwiki" ){
    proto.modeClasses = [			 
			 'Wikiwyg.Wikitext.Phpwiki',
			 'Wikiwyg.Wysiwyg'
			  ];
 }
}

/*==============================================================================
Hack to clean not supported conversion to html yet
 =============================================================================*/
proto.clean_wikitext = function(text_to_clean) {
    var text_cleaned = text_to_clean.replace(/\~*\<\?plugin/g,'~<?plugin');

    if( text_to_clean.match(/^\#{3,}/gm) ) {
      alert("Warning : Only two levels of indentation is allowed for ordered lists.\n More won't be converted");
      text_cleaned = text_to_clean.replace(/^\#{3,}/gm,'## ');
      
    }
    
    if( text_to_clean.match(/^\*{3,}/gm) ) {
      alert("Warning : Only two levels of indentation is allowed for unordered lists. \n More won't be converted");
      text_cleaned = text_to_clean.replace(/^\*{3,}/gm,'## ');
      
    }

    return(text_cleaned);
}

proto.call_action = function(action, content, func) {

  content = this.clean_wikitext(content);
  var postdata = 'action=wikitohtml' + 
                 '&content=' + encodeURIComponent(content);
  Wikiwyg.liveUpdate(
		     'POST',
		     script_url,
		     postdata,
		     func
		     );
}

proto = new Subclass('Wikiwyg.Wikitext.Phpwiki', 'Wikiwyg.Wikitext');

proto.convertWikitextToHtml = function(wikitext, func) {
    this.wikiwyg.call_action('wikiwyg_wikitext_to_html', wikitext, func);
}

proto.markupRules = {
    bold: ['bound_phrase', '<b>', '</b>'],
    italic: ['bound_phrase', '<i>', '</i>'],
    underline: ['bound_phrase', '<u>', '</u>'],
    strike: ['bound_phrase', '<strike>', '</strike>'],
    pre: ['bound_phrase', '\n\n<pre>\n', '\n</pre>\n'],
    h2: ['start_line', '!!! '],
    h3: ['start_line', '!! '],
    h4: ['start_line', '! '],
    ordered: ['start_lines', '#'],
    unordered: ['start_lines', '*'],
    indent: ['start_lines', ' '],
    hr: ['line_alone', '----'],
    link: ['bound_phrase', '[ ', '| Link ]'],
    verbatim: ['bound_phrase', '<verbatim>\n','\n</verbatim>\n'],
    table:['line_alone', 
	   '<?plugin RichTable *border=1, cellpadding=4, cellspacing=0,\n-\n|line1\n|line1\n|line1\n-\n|line2\n|line2\n|line2\n\n?>\n\n'],
    sup:['bound_phrase', '<sup>','</sup>'],
    sub:['bound_phrase', '<sub>','</sub>'],
    big:['bound_phrase', '<big>','</big>'],
    small:['bound_phrase', '<small>','</small>'],
    tt:['bound_phrase', '<tt>','</tt>'],
    em:['bound_phrase', '<em>','</em>'],
    strong:['bound_phrase', '<strong>','</strong>'],
    abbr:['bound_phrase', '<abbr>','</abbr>'],
    acronym:['bound_phrase', '<acronym>','</acronym>'],
    cite:['bound_phrase', '<cite>','</cite>'],
    code:['bound_phrase', '<code>','</code>'],
    dfn:['bound_phrase', '<dfn>','</dfn>'],
    kbd:['bound_phrase', '<kbd>','</kbd>'],
    samp:['bound_phrase', '<samp>','</samp>'],
    var_html:['bound_phrase', '<var>','</var>'],
    toc: ['line_alone', '<?plugin CreateToc ?>\n\n']    
};

/*==============================================================================
Code to convert html to wikitext. Hack for phpwiki and IE
 =============================================================================*/
proto.convert_html_to_wikitext = function(html) {
    this.copyhtml = html;
    var dom = document.createElement('div');
    html = html.replace(/<!-=-/g, '<!--').
                replace(/-=->/g, '-->');

    // Hack for IE 
    // convert <p>&nbsp;</p> into Â ( unknown char :)
    html = html.replace(/\&nbsp;/g,'');

    dom.innerHTML = html;
    this.output = [];
    this.list_type = [];
    this.indent_level = 0;

    this._chomp = "false";
    this._isplugin = "false";
    this._iswikitext = "false";
    this._tag = "false";
    this._table = "false";

    this.walk(dom);

    // add final whitespace
    this.assert_new_line();

    if(this._iswikitext=="true") {
      var alert_message = "Warning : One of the 'Wikitext {}' section "
	+"is not well formatted !!!\nIt may cause some issues";
      alert(alert_message);
    }

    return this.join_output(this.output);
}

/*==============================================================================
Find the <tag> element to convert and apply the appropriate method
 =============================================================================*/
proto.dispatch_formatter = function(element) {
    var dispatch = 'format_' + element.nodeName.toLowerCase();
    this._tag = element.nodeName.toLowerCase();
    if (! this[dispatch])
        dispatch = 'handle_undefined';
    this[dispatch](element);
}

/*==============================================================================
[Wikitext.js] Insert string in the output buffer
 =============================================================================*/
proto.appendOutput = function(string) {
    var string_escaped = string;

    // if this method is called by chomp method
    // string has been escaped before
    if ( this._chomp == 'false' ) {
      string_escaped = this.is_escape_char_needed(string);
      if( this._iswikitext == "true" && this._tag != "text" && string != "\n") {
	return ;
      }
    }
    else {
      this._chomp = 'false';
    }
    
    this.output.push(string_escaped);    
    this._tag = "false";
}

/*==============================================================================
  [Wikitext.js] Remove fang : not used in phpwiki
 =============================================================================*/
proto.insert_new_line = function() {
    var fang = '';
    if (this.indent_level > 0)
        fang = ''.times(this.indent_level) + '';
    // XXX - ('\n' + fang) MUST be in the same element in this.output so that
    // it can be properly matched by chomp above.
    if (this.output.length)
        this.appendOutput('\n' + fang);
    else if (fang.length)
        this.appendOutput(fang);
}

/*==============================================================================
Test if escape char is needed in string
 =============================================================================*/
proto.is_escape_char_needed = function(string) {
    var escape_string = string;
    if ( typeof(string) == 'string' ) {
      escape_string = this.is_it_wikitext(escape_string);
      this.match_plugin(escape_string);
      
      if( this._iswikitext == 'false' && this._tag == "text" ) {	  
	escape_string = this.insert_escape_char(escape_string);
      }
    }
    return escape_string;
}    

/*==============================================================================
Insert escape char in the string ( escape char is '~' for phpwiki )
 =============================================================================*/
proto.insert_escape_char = function(string) {
    // Insert the escape charater before
    // interpreted markups      
    var basic_markup = /(\*|\_|^\!|\[|^\>|\~|\%\%\%|^\-\-\-|^\-|\|)/g;
    string = string.replace(basic_markup, "~$1");
    
    var tag_markup = 
    /(\<\/{0,1}(b|i|tt|em|strong|abbr|acronym|cite|code|dfn|kbd|samp|sup|sub|big|small|var)\>)/g;
    string = string.replace(tag_markup, "~$1");

    var tag_plugin = /(\<\?)/g;
    string = string.replace(tag_plugin, "~$1");

    var links =  /(http|https|ftp:\/\/)/g;
    string = string.replace(links, "~$1");


    return string;
}

/*==============================================================================
Test the string to match a wikitext section
 =============================================================================*/
proto.is_it_wikitext = function(string) {
    var _string = string;

    var  begin_wikitext_section = /Wikitext \{/;
    var end_wikitext_section = /^\}$/;

    if ( typeof(_string) == 'string' ) {
      if( this._iswikitext == 'false' ) {
	if( _string.match(begin_wikitext_section) ) {
	  this._iswikitext = 'true';
	  _string = _string.replace(begin_wikitext_section,"");
	}
      }
      else {
	if ( this._iswikitext == 'true' && _string.match(end_wikitext_section) ) {
	    this._iswikitext = 'false';
	    _string = string.replace(end_wikitext_section,'');
	  }
	}	
    }    
    return _string;
}

/*==============================================================================
Match if the string contains the beginning or the end of a plugin
 =============================================================================*/
proto.match_plugin = function(string) {
  var match_plugin = string.match(/^\<\?plugin/);
  var match_end_plugin = string.match(/\?\>$/);

  if(this._isplugin == "false") {
    if(match_plugin) {
      this._isplugin = "true";
    }
  }
    else {
      if( match_end_plugin && this._isplugin == "true" )
	this._isplugin = "false";
    }
}
/*==============================================================================
End Match if the string contain a plugin
 =============================================================================*/

/*==============================================================================
[Wikitext.js] Treatment before "assert a new line"
 =============================================================================*/
proto.chomp = function() {
    var string;
    while (this.output.length) {
        string = this.output.pop();
        if (typeof(string) != 'string') {
            this.appendOutput(string);
            return;
        }
        if (! string.match(/^\n>+ $/) && string.match(/\S/))
            break;
    }
    if (string) {
        string = string.replace(/[\r\n\s]+$/, '');
	this._chomp = 'true';
        this.appendOutput(string);
    }
}
/*==============================================================================
End Treatment before "assert a new line"
 =============================================================================*/

/*==============================================================================
[Wikitext.js] Support of Headings in phpwiki 
 =============================================================================*/

// Adding match headings : '!', for phpwiki.
proto.add_markup_lines = function(markup_start) {
    var already_set_re = new RegExp( '^' + this.clean_regexp(markup_start), 'gm');
    var other_markup_re = /^(\^+|\=+|\*+|#+|>+|!+|    )/gm;

    var match;
    // if paragraph, reduce everything.
    if (! markup_start.length) {
        this.sel = this.sel.replace(other_markup_re, '');
        this.sel = this.sel.replace(/^\ +/gm, '');
    }
    // if pre and not all indented, indent
    else if ((markup_start == '    ') && this.sel.match(/^\S/m))
        this.sel = this.sel.replace(/^/gm, markup_start);
    // if not requesting heading and already this style, kill this style
    else if (
        (! markup_start.match(/[\!\^]/)) &&
        this.sel.match(already_set_re)
    ) {
        this.sel = this.sel.replace(already_set_re, '');
        if (markup_start != '    ')
            this.sel = this.sel.replace(/^ */gm, '');
    }
    // if some other style, switch to new style
    else if (match = this.sel.match(other_markup_re))
        // if pre, just indent
        if (markup_start == '    ')
            this.sel = this.sel.replace(/^/gm, markup_start);
        // if heading, just change it
        else if (markup_start.match(/[\!\^]/))
            this.sel = this.sel.replace(other_markup_re, markup_start);
        // else try to change based on level
        else
            this.sel = this.sel.replace(
                other_markup_re,
                function(match) {
                    return markup_start.times(match.length);
                }
            );
    // if something selected, use this style
    else if (this.sel.length > 0)
        this.sel = this.sel.replace(/^(.*\S+)/gm, markup_start + ' $1');
    // just add the markup
    else
        this.sel = markup_start + ' ';

    var text = this.start + this.sel + this.finish;
    var start = this.selection_start;
    var end = this.selection_start + this.sel.length;
    this.set_text_and_selection(text, start, end);
    this.area.focus();
}

/*==============================================================================
[ Wikitext.js] Support for incremental numbers :
When there is 
# list1
## list 2
phpwiki convert it with a <p> element inside the <li> element
So the <p> element have to be ignored
 =============================================================================*/
proto.format_p = function(element) {

  // Hack to avoid \n to be inserted if an li element is parent
  if( (element.parentNode.nodeType == '1' 
	&& element.parentNode.nodeName.toLowerCase() == "li")) {
    this.walk(element);
  }
  else {
    var style = element.getAttribute('style','true');
    if ( style ) {
      if ( !Wikiwyg.is_ie ) {
	this.assert_blank_line();
	this.assert_space_or_newline();
	if (style.match(/\bbold\b/))
	  this.appendOutput(this.config.markupRules.bold[1]);
	if (style.match(/\bitalic\b/))
	  this.appendOutput(this.config.markupRules.italic[1]);
	if (style.match(/\bunderline\b/))
	  this.appendOutput(this.config.markupRules.underline[1]);
	if (style.match(/\bline-through\b/))
	  this.appendOutput(this.config.markupRules.strike[1]);
	
	this.no_following_whitespace();
	this.walk(element);
	
	if (style.match(/\bline-through\b/))
	  this.appendOutput(this.config.markupRules.strike[2]);
	if (style.match(/\bunderline\b/))
	  this.appendOutput(this.config.markupRules.underline[2]);
	if (style.match(/\bitalic\b/))
	  this.appendOutput(this.config.markupRules.italic[2]);
	if (style.match(/\bbold\b/))
	  this.appendOutput(this.config.markupRules.bold[2]);
	
	this.assert_blank_line();
      } // end if(!is_ie) 
      else{
	this.assert_blank_line();
	this.walk(element);
	this.assert_blank_line();   
      }
    }  // end if (style)   
    else {
      this.assert_blank_line();
      this.walk(element);
      this.assert_blank_line();   
    }
  }
}

/*==============================================================================
Support for <li> tag
Only two levels of indentation is possible
 =============================================================================*/
proto.format_li = function(element) {
    var level = this.list_type.length;
    if (!level) die("List error");
    var type = this.list_type[level - 1];
    var markup = this.config.markupRules[type];

    if(level<=2) 
      this.appendOutput(markup[1].times(level) + ' ');
    else {
      this.appendOutput(markup[1].times(2) + ' ');
      alert("Only two levels of indentation is possible");
    }

    this.walk(element);

    this.chomp();
    this.insert_new_line();
}


/*==============================================================================
Support for <br> tag
 =============================================================================*/
proto.format_br = function(element) {
    this.assert_new_line();
}

/*==============================================================================
Support for links
 =============================================================================*/
proto.make_wikitext_link = function(label, href, element) {
    var before = '[';
    var after  = ']';

    href = unescape(href);

    // Hack : IE add the base url 
    // to all links in wysiwyg mode
    // 10 is sizeof 'index.php/'
    var base_url="";
    var url = document.location.toString();
    var index = url.lastIndexOf("index.php");
    if( index > 0)
      base_url = url.substring(0,index+10);

    // Base URL removed
    regexp_url = new RegExp(this.clean_regexp(base_url));
    if( href.match( regexp_url,'gm' ) ) {
	href = href.replace( regexp_url, "");
    }

    this.assert_space_or_newline();
    if (! href) {
        this.appendOutput(before + label + after);
    }
    else if (href == label) {
      if (this.camel_case_link(label))
	this.appendOutput(label);
      else
        this.appendOutput(before + href + after);
    }
    else {
        this.appendOutput(before + label + '|' + href + after);
    }
}

/*==============================================================================
Convert <span> tag and 
 =============================================================================*/
proto.format_span = function(element) {
    if (this.is_opaque(element)) {
        this.handle_opaque_phrase(element);
        return;
    }

    var style = element.getAttribute('style','true');

    if (!style ) {
        this.pass(element);
        return;
    }

    if ( !Wikiwyg.is_ie ) {
      this.assert_space_or_newline();
      if (style.match(/\bbold\b/))
        this.appendOutput(this.config.markupRules.bold[1]);
      if (style.match(/\bitalic\b/))
        this.appendOutput(this.config.markupRules.italic[1]);
      if (style.match(/\bunderline\b/))
        this.appendOutput(this.config.markupRules.underline[1]);
      if (style.match(/\bline-through\b/))
        this.appendOutput(this.config.markupRules.strike[1]);
    }

    this.no_following_whitespace();
    this.walk(element);

    if ( !Wikiwyg.is_ie ) {
      if (style.match(/\bline-through\b/))
        this.appendOutput(this.config.markupRules.strike[2]);
      if (style.match(/\bunderline\b/))
        this.appendOutput(this.config.markupRules.underline[2]);
      if (style.match(/\bitalic\b/))
        this.appendOutput(this.config.markupRules.italic[2]);
      if (style.match(/\bbold\b/))
        this.appendOutput(this.config.markupRules.bold[2]);
    }
}

/*==============================================================================
Support for plugin RichTable in phpwiki
 =============================================================================*/
proto.format_table = function(element) {
    this._table="true";
    this.assert_blank_line();
    this.appendOutput('<?plugin RichTable *border=1, cellpadding=4, cellspacing=0,\n');
    this.walk(element);
    this.appendOutput('\n?>\n\n');
    this.assert_blank_line();
    this._table = "false";
}

proto.format_tr = function(element) {
    this.appendOutput('-\n');
    this.walk(element);
}

proto.format_td = function(element) {
    this.appendOutput('|');
    this.walk(element);
    this.appendOutput('\n');
}

proto.format_th = function(element) {
    this.appendOutput('|');
    this.walk(element);
    this.appendOutput('\n');
}

proto.format_img = function(element) {
    var uri = element.getAttribute('src');
    if( uri.match(/\/uploads\//) ){
      uri = escape( uri.substring( uri.lastIndexOf('/')+1, uri.length ) );
      uri = "[Upload:"+uri+" border=1]";
    }

    if (uri) {
        this.assert_space_or_newline();
        this.appendOutput(uri);
    }
}

/*==============================================================================
Support for <sup> tag
 =============================================================================*/
proto.format_sup = function(element) {
    this.appendOutput(this.config.markupRules.sup[1]);
    this.walk(element);
    this.appendOutput(this.config.markupRules.sup[2]);
}

/*==============================================================================
Support for <sub> tag
 =============================================================================*/
proto.format_sub = function(element) {
    this.appendOutput(this.config.markupRules.sub[1]);
    this.walk(element);
    this.appendOutput(this.config.markupRules.sub[2]);
}

/*==============================================================================
Support for <big> tag
 =============================================================================*/
proto.format_big = function(element) {
    this.appendOutput(this.config.markupRules.big[1]);
    this.walk(element);
    this.appendOutput(this.config.markupRules.big[2]);
}

/*==============================================================================
Support for <small> tag
 =============================================================================*/
proto.format_small = function(element) {
    this.appendOutput(this.config.markupRules.small[1]);
    this.walk(element);
    this.appendOutput(this.config.markupRules.small[2]);
}

/*==============================================================================
Support for <tt> tag
 =============================================================================*/
proto.format_tt = function(element) {
    this.appendOutput(this.config.markupRules.tt[1]);
    this.walk(element);
    this.appendOutput(this.config.markupRules.tt[2]);
}

/*==============================================================================
Support for <em> tag
 =============================================================================*/
proto.format_em = function(element) {
    this.appendOutput(this.config.markupRules.em[1]);
    this.walk(element);
    this.appendOutput(this.config.markupRules.em[2]);
}

/*==============================================================================
Support for <strong> tag
 =============================================================================*/
proto.format_strong = function(element) {
    this.appendOutput(this.config.markupRules.strong[1]);
    this.walk(element);
    this.appendOutput(this.config.markupRules.strong[2]);
}

/*==============================================================================
Support for <abbr> tag
 =============================================================================*/
proto.format_abbr = function(element) {
    this.appendOutput(this.config.markupRules.abbr[1]);
    this.walk(element);
    this.appendOutput(this.config.markupRules.abbr[2]);
}

/*==============================================================================
Support for <acronym> tag
 =============================================================================*/
proto.format_acronym = function(element) {
    this.appendOutput(this.config.markupRules.acronym[1]);
    this.walk(element);
    this.appendOutput(this.config.markupRules.acronym[2]);
}

/*==============================================================================
Support for <cite> tag
 =============================================================================*/
proto.format_cite = function(element) {
    this.appendOutput(this.config.markupRules.cite[1]);
    this.walk(element);
    this.appendOutput(this.config.markupRules.cite[2]);
}

/*==============================================================================
Support for <code> tag
 =============================================================================*/
proto.format_code = function(element) {
    this.appendOutput(this.config.markupRules.code[1]);
    this.walk(element);
    this.appendOutput(this.config.markupRules.code[2]);
}

/*==============================================================================
Support for <dfn> tag
 =============================================================================*/
proto.format_dfn = function(element) {
    this.appendOutput(this.config.markupRules.dfn[1]);
    this.walk(element);
    this.appendOutput(this.config.markupRules.dfn[2]);
}

/*==============================================================================
Support for <kbd> tag
 =============================================================================*/
proto.format_kbd = function(element) {
    this.appendOutput(this.config.markupRules.kbd[1]);
    this.walk(element);
    this.appendOutput(this.config.markupRules.kbd[2]);
}

/*==============================================================================
Support for <samp> tag
 =============================================================================*/
proto.format_samp = function(element) {
    this.appendOutput(this.config.markupRules.samp[1]);
    this.walk(element);
    this.appendOutput(this.config.markupRules.samp[2]);
}

/*==============================================================================
Support for <var> tag
 =============================================================================*/
proto.format_var = function(element) {
    this.appendOutput(this.config.markupRules.var_html[1]);
    this.walk(element);
    this.appendOutput(this.config.markupRules.var_html[2]);
}

proto.do_verbatim = Wikiwyg.Wikitext.make_do('verbatim');
proto.do_line_break = Wikiwyg.Wikitext.make_do('line_break');
proto.do_sub = Wikiwyg.Wikitext.make_do('sub');
proto.do_sup = Wikiwyg.Wikitext.make_do('sup');
proto.do_toc = Wikiwyg.Wikitext.make_do('toc');

// Preview button
proto.do_preview = function() {
    var form_element = document.getElementById('editpage');
    var input = document.createElement('input');
    input.setAttribute('type', 'hidden');
    input.setAttribute('name', 'edit[preview]');
    input.setAttribute('value', 'Preview');
    input.setAttribute('class', 'Wikiaction');
    form_element.appendChild(input);

    var input_content = document.createElement('input');
    input_content.setAttribute('type', 'hidden');
    input_content.setAttribute('name', 'edit[content]');
    input_content.setAttribute('value', this.area.value);
    input_content.setAttribute('class', 'wikiedit');
    input_content.setAttribute('id', 'edit:content');
    form_element.appendChild(input_content);
    form_element.submit();
};

// Save button
proto.do_save_button = function() {
    var form_element = document.getElementById('editpage');
    var input = document.createElement('input');
    input.setAttribute('type', 'hidden');
    input.setAttribute('name', 'edit[save]');
    input.setAttribute('value', 'Save');
    input.setAttribute('class', 'Wikiaction');
    form_element.appendChild(input);

    var input_content = document.createElement('input');
    input_content.setAttribute('type', 'hidden');
    input_content.setAttribute('name', 'edit[content]');
    input_content.setAttribute('value', this.area.value);
    input_content.setAttribute('class', 'wikiedit');
    input_content.setAttribute('id', 'edit:content');
    form_element.appendChild(input_content);
    form_element.submit();
};


// Draft function to add plugins 
// in wikitext mode
// Doesn't work yet
proto.do_plugins = function() {
    showPulldown('Insert Plugin (double-click)',[['AddComment','%0A<?plugin AddComment\npagename=%5Bpagename%5D order=normal mode=add,show jshide=0 noheader= ?>'],['text2png','%0A<?plugin text2png\ntext=%27Hello WikiWorld!%27 l=en ?>']],'Insert','Close');
  return;
}

proto = new Subclass('Wikiwyg.Preview.Phpwiki', 'Wikiwyg.Preview');

proto.fromHtml = function(html) {
    if (this.wikiwyg.previous_mode.classname.match(/(Wysiwyg|HTML)/)) {
        var wikitext_mode = this.wikiwyg.mode_objects['Wikiwyg.Wikitext.Phpwiki'];
        var self = this;
        wikitext_mode.convertWikitextToHtml(
            wikitext_mode.convert_html_to_wikitext(html),
            function(new_html) { self.div.innerHTML = new_html }
        );
    }
    else {
        this.div.innerHTML = html;
    }
}

/*==============================================================================
Support for Internet Explorer in Wikiwyg
 =============================================================================*/
if (Wikiwyg.is_ie) {

if (window.ActiveXObject && !window.XMLHttpRequest) {
  window.XMLHttpRequest = function() {
    return new ActiveXObject((navigator.userAgent.toLowerCase().indexOf('msie 5') != -1) ? 'Microsoft.XMLHTTP' : 'Msxml2.XMLHTTP');
  };
}

} // end of global if statement for IE overrides
