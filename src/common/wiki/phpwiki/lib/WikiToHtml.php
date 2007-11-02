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
	  $this->color_pre_tags();
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
        $this->_html = preg_replace_callback($pattern, $replace_string, $this->_html);
    }
    
    // Replace unknown plugins by keyword Wikitext { tag }
    function replace_unknown_plugins() {
        $pattern = '/(\<blockquote.*\>\<p class\=\"tightenable top.*\"\>)?(\&lt\;\?plugin.*\?\&gt\;)(\<\/p\> \<\/blockquote\>)?/Umsi';
        $replace_string = '<p><div style="background-color:#D3D3D3;font-size:smaller;">Wikitext {<br>\2<br>}</div><br></p>';
        $this->_html = preg_replace($pattern, $replace_string, $this->_html);
    }
    
    // Preview preformatted areas with a light yellow background.
    function color_pre_tags() {
        $pattern = '/\<pre class\=\".*\"\>/Usi';
        $replace_string = '<pre style="background-color:#FDFDF7">';
        $this->_html = preg_replace($pattern, $replace_string, $this->_html);
    }

    // Clean links to keep only <a href="link">name</a>
    // Order of cleanning is important.
    function clean_links() {
        // decode ampersands
        $pattern = '/\&amp\;/Umsi';
	  $replace_string = '&';
	  $this->_html = preg_replace($pattern, $replace_string, $this->_html);

        // Clean named-wikiunknown
        $pattern = '/\<span class\=\"named-wikiunknown\"\>\<u\>(.*)\<\/u\>\<a href\=\"(.*)\".*\>\?\<\/a\>\<\/span\>/Umsi';
        $replace_string = '<a href="\2">\1</a>';
        $this->_html = preg_replace($pattern, $replace_string, $this->_html);
	
        // Existing links
        $pattern = '/\<a href\=\"index.php\?pagename\=(\w+)\"([^>])*\>/Umsi';
        $replace_string = '<a href="\1">';
        $this->_html = preg_replace($pattern, $replace_string, $this->_html);
	
        // Non existing links
        $pattern = '/\<a href\=\"index.php\?pagename\=([^"]*)(&amp;action){1}([^>])*\>/Umsi';
        $replace_string = '<a href="\1">';
        $this->_html = preg_replace($pattern, $replace_string, $this->_html);

        // Clean underline 
        $pattern = '/\<u\>(.*)\<\/u\>(\<a href="(.*))[?"]{1}.*\>.*\<\/a\>/Umsi';
        $replace_string = '<span>\2" style="color:blue;">\1</a></span>';
        $this->_html = preg_replace($pattern, $replace_string, $this->_html);

        // Clean Wiki unknown
        $pattern = '/\<span class\=\"wikiunknown\"\>\<span\>\<a href\=\".*\" style\=\".*\"\>(.*)\<\/a\>\<\/span\>\<\/span\>/Umsi';
        $replace_string = '\1';
        $this->_html = preg_replace($pattern, $replace_string, $this->_html);
		
        // This solves a link issue. It removes the style "white-space: nowrap" so that links can
        // be correctly appllied to strings that contain white spaces. Without it, it won't be possible to
        // show target url when trying to chage it using wysiwyg link tool.
        $pattern = '/\<a href\=\"(.*)\" target\=\"\" class\=\"namedurl\"\>\<span style\=\"white-space: nowrap\"\>\<img(.*)\/\>(.*)\<\/span\>(.*)\<\/a\>/';
        $replace_string = '<a href="\1" target="" class="namedurl"><span><img\2/>\3\4</span></a>';
        $this->_html = preg_replace($pattern, $replace_string, $this->_html);
    }

    // Replace \n by <br> only in
    // < ?plugin ? > tag to keep formatting
    function clean_plugin(){
        $pattern = '/(\&lt\;\?plugin.*\?\&gt\;)/Umsei';
        $replace_string = 'preg_replace("/\n/Ums","<br>","\1")';
        $this->_html = preg_replace($pattern, $replace_string, $this->_html);
    }

    function clean_plugin_name(){
        // Remove plugin name converted in a link
        $pattern = '/(\&lt\;\?plugin\s)\<span.*\>\<span\>\<a href=.*\>(\w+)\<\/a\><\/span\><\/span>([^?]*\?\&gt\;)/Umsi';
        $replace_string = '\1 \2 \3';
        $this->_html = preg_replace($pattern, $replace_string, $this->_html);
    } 
}

// This is called to replace the RichTable plugin by an html table
// $matched contains html <p> tags so 
// they are deleted before the conversion.
function replace_rich_table($matched) {
    $plugin = $matched[1];

    // Get plugin options
    if (preg_match('/(.*)\-/Umsi', $plugin, $m)){
        $options = $m[1];
    }

    // Remove bold tag created from asterixes in plugin markup.
    $pattern = '/\<b\>(.*)/Umsi';
    $replace_string = '\1';
    $options = preg_replace($pattern, $replace_string, $options);
	
    //Seperate attributes from values of each table param
    $params = explode(",", $options);
    foreach($params as $param){
        if(preg_match('/(.*)\=(.*)/', $param, $match)) {
            $attr = $match[1];
            if(preg_match('/[*].*/', $attr)) {
                $attr = preg_replace('/[*](.*)/', '\1', $attr);
            }
            $val = $match[2];
        }
        $attributes[$attr] = $val;
    }

    $unknown_options = "/colspan|rowspan/";

    // if the plugin contains one of the options bellow
    // it won't be converted
    if (preg_match($unknown_options,$plugin))
        return $matched[0]."\n";
    else {
        // Clean plugin options in case options string started at a new line and therefore converted to unnumbered list !
        if(preg_match('/\<ul\>/Umsi', $plugin)) {
            $pattern = '/\<li.*\>/Umsi';
            $replace_string = '*';
            $plugin = preg_replace($pattern, $replace_string, $plugin);

            $pattern = '/\<\/li\>/Umsi';
            $replace_string = '';
            $plugin = preg_replace($pattern, $replace_string, $plugin);

            $pattern = '/\<ul\>/Umsi';
            $replace_string = '';
            $plugin = preg_replace($pattern, $replace_string, $plugin);

            $pattern = '/\<\/ul\>/Umsi';
            $replace_string = '';
            $plugin = preg_replace($pattern, $replace_string, $plugin);
        }
	
        //Replace unused <p...>
        $pattern = '/\<p.*\>/Umsi';
        $replace_string = "";
        $plugin = preg_replace($pattern, $replace_string, $plugin);

        //replace unused </p> by \n
        $pattern = '/\<\/p\>/Umsi';
        $replace_string = "\n";
        $plugin = preg_replace($pattern, $replace_string, $plugin);

        // Here we need to clean some stupid </b> tags in order to render cells attributes.
        $pattern = '/\|\<\/b\>/Umsi';
        $replace_string = '|*';
        $plugin = preg_replace($pattern, $replace_string, $plugin);

        $plugin = "<?plugin RichTable ".$plugin." ?>";

        require_once("lib/BlockParser.php");
        $xmlcontent = TransformText($plugin, 2.0, $GLOBALS['request']->getArg('pagename'));
        $html_table = $xmlcontent->AsXML();

        // Construct table attributes string
        $attrs = '';
        foreach($attributes as $key => $val) {
            $attrs .= $key.'='.'"'.$val.'" ';
        }

        $pattern = '/\<ul\>\<li class\=\"tightenable top bottom\"\>(.*)\<\/li\>/Umsi';
        $replace_string = '\1';
        $attrs = preg_replace($pattern, $replace_string, $attrs);

        // Put tables inside a div tag instead of span and add its attributes declared as RichTable plugin params from wikitext.
        $pattern = '/\<span class\=\"plugin.*\" id\=\"(RichTablePlugin.*)\"\>\<table.*\>(.*)\<\/span\>/Umsi';
        $replace_string = '<table '. $attrs .'>\2';
        $html_table = preg_replace($pattern, $replace_string, $html_table);

        // Decode html entities like for links and images.
        $html_table = html_entity_decode($html_table);

        // From here on, the content returned by TransformText() call becomes really a mess
        // when viewed in wysiwyg edition mode. This is because we called twice TransformText()
        // Once for wikitext conversion then another time to convert the table. This lead to extra conversions
        // of links and generates a not well formed HTML.
        // A lot of things need to be cleanned such as links (namedurls, wikipages, labeled urls,
        // inline, images, automagic links, etc.)

        // Fix for external links (namedurls). Urls inside 'href' attribute of  'a' tags
        // are converted with linkicons due to TransformText() prior call.
        $pattern = '/\<a href\=\"\<a href\=.*\<\/a\>\" target\=\"\" class\=\"namedurl\"\>\<span.*\>\<img.*\/\>(\<a href\=.*\<\/span\>\<\/a\>)\<\/span\>\<\/a\>/';
        $replace_string = '\1';
        $html_table = preg_replace($pattern, $replace_string, $html_table);

        // Fix for inline images. Displaying the images fails inside RichTable.
        // Some Raw Html + broken image appears instead of the expected image.
        // This is caused by converting the image uri into a named url by former TransformText()
        // call wich lead to an href tag inside the src attribute of the img tag.
        // The bug only occurs in ST code.
        $pattern = '/\<img src\=\"\<a href\=.*\>\<span.*\>\<img.*\/\>(.*)\<\/span\>\<\/a\>\" (alt\=\".*\" title\=\".*\" class\=\".*\").*\/\>/';
        $replace_string = '<img src="\1"\2>';
        $html_table = preg_replace($pattern, $replace_string, $html_table);

        //Other fix for inline images
		$pattern = '/\<img src\=\"\<a href\=\"([^"]+)\".*\>.*\<img.*\>.*\<\/a\>\"(.*)\/\>/';
		$replace_string = '<img src="\1"\2/>';
		$html_table = preg_replace($pattern, $replace_string, $html_table);

        // Fix for attachments links
        $pattern = '/\<a href\=\"\<a href\=\".*\" target\=\"\" class\=\"namedurl\"\>\<span[^>]+\>\<img.*\/\>([^<]+).*\<\/a\>\" class\=\"interwiki\"\>\<span[^>]+\>(\<img[^>]+\>).*(Upload:|Attach:).*\<\/a> class\=\"wikipage\"\>(.*)\<\/span\>\<\/a\>/';
        $replace_string = '<a href="\1" class="interwiki">\2\3\4</a>';
        $html_table = preg_replace($pattern, $replace_string, $html_table);

        // Clean links patterns that contain spaces�
        $pattern = '/\<a href\=\"\<a href\=\"(.*)\".*\>.*\<img.*\/\>.*\<img.*\/\>(.*)\<\/span\>(.*)\<\/a\>/Umsi';
        $replace_string = '<a href="\1" target="" class="namedurl">\2\3</a>';
        $html_table = preg_replace($pattern, $replace_string, $html_table);

        // Clean Wiki unknown page links
        $pattern = '/\<span class\=\"wikiunknown\">\<u\>(.*)\<\/u\>\<a href.*\>.*\<\/a\>(.*)\<\/span\>/Umsi';
        $replace_string = '\1\2';
        $html_table = preg_replace($pattern, $replace_string, $html_table);

        // Clean Wiki unknown page links
        $pattern = '/\<span class\=\"named-wikiunknown\">\<span\>(\<a href\=.*\>.*\<\/a\>)\<\/span\>\<\/span\>/Umsi';
        $replace_string = '\1';
        $html_table = preg_replace($pattern, $replace_string, $html_table);

        //Clean automagic links.
        $pattern = '/\<a href\=\"(.*goto.*)\" title\=\"(.*)\"\>\<a href.*\>(.*)&lt\<\/a\>;\/a\>/';
        $replace_string = '<a href="\1" title="\2">\3</a>';
        $html_table = preg_replace($pattern, $replace_string, $html_table);

        // Remove blockquotes from cells
        $pattern = '/\<blockquote.*\>/Umsi';
        $replace_string = '';
        $html_table = preg_replace($pattern, $replace_string, $html_table);
		
        // Remove blockquotes from cells
        $pattern = '/\<\/blockquote.*\>/Umsi';
        $replace_string = '';
        $html_table = preg_replace($pattern, $replace_string, $html_table);
		
        //Fix for internal links
        //Remove <a> tags from href attribute of other <a> tags
        //this is due to WikiWords converted to ahrefs by Phpwiki TransformText prior call.
        $pattern = '/\<a href\=\"[^<]+(\<a href\=\".*\" class\=\"wiki\"\>.*\<\/a\>)[^"]*\" class\=\"wiki\"\>.*\<\/a\>\<\/a\>/Umsi';
        $replace_string = '\1';
        $html_table = preg_replace($pattern, $replace_string, $html_table);

        return $html_table;
    }
}

// $Log: WikiToHtml.php,v $
// Revision 1.2  2006/06/05 08:10:19  rurban
// stylistic fixup: clarify request argument
//

?>