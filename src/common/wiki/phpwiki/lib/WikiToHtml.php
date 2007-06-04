<?php //-*-php-*-
rcs_id('$Id: WikiToHtml.php,v 1.2 2006/06/05 08:10:19 rurban Exp $');
/*
 * Copyright(c) STMicroelectronics, 2006
 *
 * Originally written by Jean-Nicolas GEREONE, STMicroelectronics, 2006. 

 This file is part of PhpWiki.

 PhpWiki is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 PhpWiki is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with PhpWiki; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class WikiToHtml {
  function WikiToHtml ($wikitext, &$request) {
        $this->_wikitext = $wikitext;
	$this->_request =& $request;
	$this->_html = "";
	$this->html_content = "";
    }

    function send() {
        $this->convert();
	echo $this->html_content;
    }

    function convert() {
        require_once("lib/BlockParser.php");       
	$xmlcontent = TransformText($this->_wikitext, 2.0, $this->_request->getArg('pagename')); 
	$this->_html = $xmlcontent->AsXML();

	$this->replace_inside_html();
    }

    function replace_inside_html() {
	$this->clean_links();
        $this->clean_plugin_name();
        $this->replace_known_plugins();
        $this->replace_unknown_plugins();
	//	$this->replace_tags();
	$this->clean_plugin();
	if ($charset != 'utf-8'){
	    if($charset == 'iso-8959-1'){
	        $this->_html = utf8_decode($this->_html);
	    }else{
	        // Check for iconv support
		LoadPhpExtension("iconv");
		$this->_html = iconv("utf-8", $charset, $this->_html);
	    }
	}
	$this->html_content = $this->_html;
    }

    // Draft function to replace RichTable
    // by a html table
    // Works only on one plugin for the moment
    function replace_known_plugins() {
      // If match a plugin
      $pattern = '/\&lt\;\?plugin\s+RichTable(.*)\?\&gt\;/Umsi';
      $replace_string = "replace_rich_table";       
      $this->_html = preg_replace_callback($pattern,
					   $replace_string,
					   $this->_html);
    }
    
    // This function is intended to replace unknown plugins by keyword Wikitext { tag }
    // For the moment we only use wysiwyg edition mode so wikitext tags are not useful.
    // Plugins markup is returned as it is.
    function replace_unknown_plugins() {
        $pattern = '/(\&lt\;\?plugin[^?]*\?\&gt\;)/Usi';
	/*$replace_string = 
	  '<p><div style="background-color:#D3D3D3;font-size:smaller;">Wikitext {
 <br> \1 <br>}</div><br></p>';*/
        $replace_string = '<br> \1 <br>';
	$this->_html = preg_replace($pattern,
				    $replace_string,
				    $this->_html);
    }

    // Clean links to keep only <a href="link">name</a>
    function clean_links() {
      // Existing links
      $pattern = '/\<a href\=\"index.php\?pagename\=(\w+)\"([^>])*\>/Umsi';      
      $replace_string = '<a href="\1">';      
      $this->_html = preg_replace($pattern,
				  $replace_string,
				  $this->_html) ;
      // Non existing links
	$pattern = '/\<a href\=\"index.php\?pagename\=([^"]*)(&amp;action){1}([^>])*\>/Umsi';
	$replace_string = '<a href="\1">';
	
	$this->_html = preg_replace($pattern,
				    $replace_string,
				    $this->_html) ;

	// Clean underline 
        $pattern = '/\<u\>(.*)\<\/u\>(\<a href="(.*))[?"]{1}.*\>.*\<\/a\>/Umsi';
	$replace_string = 
	  '<span>\2" style="color:blue;">\1</a></span>';
	
	$this->_html = preg_replace($pattern,
				    $replace_string,
				    $this->_html) ;
    }
    
    // Put unknown tags in Wikitext {}
    function replace_tags() {
      // Replace old table format ( non plugin )
      $pattern = '/(\ {0,4}(?:\S.*)?\|\S+\s*$.*?\<\/p\>)/ms';
      $replace_string = 
	'<p><div style="background-color:#D3D3D3;font-size:smaller;">Wikitext {
 <br> \1 <br>}</div><br></p>';
      
      $this->_html = preg_replace($pattern,
				  $replace_string,
				  $this->_html);

}
    
    // Replace \n by <br> only in 
    // <?plugin ? > tag to keep formatting
    function clean_plugin() {
        $pattern = '/(\&lt\;\?plugin.*\?\&gt\;)/Umsei';
	$replace_string = 'preg_replace("/\n/Ums","<br>","\1")';
	
	$this->_html = preg_replace($pattern,
				    $replace_string,
				    $this->_html) ; 
    }
    
    function clean_plugin_name() {
	// Remove plugin name converted in a link
	$pattern = '/(\&lt\;\?plugin\s)\<span.*\>\<span\>\<a href=.*\>(\w+)\<\/a\><\/span\><\/span>([^?]*\?\&gt\;)/Umsi';
 	$replace_string = '\1 \2 \3';
 	$this->_html = preg_replace($pattern,
 				    $replace_string,
 				    $this->_html) ; 
    } 
}

// This is called to replace the RichTable plugin by an html table
// $matched contains html <p> tags so 
// they are deleted before the conversion.
function replace_rich_table($matched) {
  $plugin = $matched[1];

  // External links
  $pattern = '/\<a href\=\"(.*)\".*<img.*\/\>(.*)\<\/span\>(.*)\<\/a\>/Umsi';     
  $replace_string = "[".'\2\3'."|".'\1'."]";
  $plugin = preg_replace($pattern, $replace_string, $plugin) ;
      
  $unknown_options = "/colspan|rowspan|width|height/";
  
  // if the plugin contains one of the options bellow
  // it won't be converted
  if (preg_match($unknown_options,$plugin))
    return $matched[0]."\n";   
  else {
    //Replace unused <p...>
    $pattern = '/\<p.*\>/Umsi';
    $replace_string = "";
    
    $plugin = preg_replace($pattern,
			   $replace_string,
			   $plugin) ;
    
    //replace unused </p> by \n
    $pattern = '/\<\/p\>/Umsi';
    $replace_string = "\n";
    
    $plugin = preg_replace($pattern,
			   $replace_string,
			   $plugin) ;
    
    $plugin = "<?plugin RichTable ".$plugin." ?>";
    
    require_once("lib/BlockParser.php");       
    $xmlcontent = TransformText($plugin, 2.0, $GLOBALS['request']->getArg('pagename')); 
    return $xmlcontent->AsXML();
  }
}

// $Log: WikiToHtml.php,v $
// Revision 1.2  2006/06/05 08:10:19  rurban
// stylistic fixup: clarify request argument
//

?>