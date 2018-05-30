<?php // -*-php-*-
rcs_id('$Id: PageType.php,v 1.45 2005/05/06 16:48:41 rurban Exp $');
/*
 Copyright 1999,2000,2001,2002,2003,2004,2005 $ThePhpWikiProgrammingTeam

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

require_once('lib/CachedMarkup.php');

/** A cacheable formatted wiki page.
 */
class TransformedText extends CacheableMarkup {
    /** Constructor.
     *
     * @param WikiDB_Page $page
     * @param string $text  The packed page revision content.
     * @param hash $meta    The version meta-data.
     * @param string $type_override  For markup of page using a different
     *        pagetype than that specified in its version meta-data.
     */
    function TransformedText($page, $text, $meta, $type_override=false) {
    	$pagetype = false;
        if ($type_override)
            $pagetype = $type_override;
        elseif (isset($meta['pagetype']))
            $pagetype = $meta['pagetype'];
	$this->_type = PageType::GetPageType($pagetype);
	$this->CacheableMarkup($this->_type->transform($page, $text, $meta),
                               $page->getName());
    }

    function getType() {
	return $this->_type;
    }
}

/**
 * A page type descriptor.
 *
 * Encapsulate information about page types.
 *
 * Currently the only information encapsulated is how to format
 * the specific page type.  In the future or capabilities may be
 * added, e.g. the abilities to edit different page types (differently.)
 * e.g. Support for the javascript htmlarea editor, which can only edit 
 * pure HTML.
 *
 * IMPORTANT NOTE: Since the whole PageType class gets stored (serialized)
 * as of the cached marked-up page, it is important that the PageType classes
 * not have large amounts of class data.  (No class data is even better.)
 */
class PageType {
    /**
     * Get a page type descriptor.
     *
     * This is a static member function.
     *
     * @param string $pagetype  Name of the page type.
     * @return PageType  An object which is a subclass of PageType.
     */
    function GetPageType ($name=false) {
        if (!$name)
            $name = 'wikitext';
        $class = "PageType_" . (string)$name;
        if (class_exists($class))
            return new $class;
        trigger_error(sprintf("PageType '%s' unknown", (string)$name),
                      E_USER_WARNING);
        return new PageType_wikitext;
    }

    /**
     * Get the name of this page type.
     *
     * @return string  Page type name.
     */
    function getName() {
	if (!preg_match('/^PageType_(.+)$/i', get_class($this), $m))
	    trigger_error("Bad class name for formatter(?)", E_USER_ERROR);
	return $m[1];
    }

    /**
     * Transform page text.
     *
     * @param WikiDB_Page $page
     * @param string $text
     * @param hash $meta Version meta-data
     * @return XmlContent The transformed page text.
     */
    function transform($page, &$text, $meta) {
        $fmt_class = 'PageFormatter_' . $this->getName();
        $formatter = new $fmt_class($page, $meta);
        return $formatter->format($text);
    }
}

class PageType_wikitext extends PageType {}
class PageType_html extends PageType {}
class PageType_pdf extends PageType {}

class PageType_wikiblog extends PageType {}
class PageType_comment extends PageType {}
class PageType_wikiforum extends PageType {}

/* To prevent from PHP5 Fatal error: Using $this when not in object context */
function getInterwikiMap ($pagetext = false) {
    static $map;
    if (empty($map))
        $map = new PageType_interwikimap($pagetext);
    return $map;
}

class PageType_interwikimap extends PageType
{
    function PageType_interwikimap($pagetext = false) {
        if (!$pagetext) {
            $dbi = $GLOBALS['request']->getDbh();
            $page = $dbi->getPage(_("InterWikiMap"));
            if ($page->get('locked')) {
                $current = $page->getCurrentRevision();
                $pagetext = $current->getPackedContent();
                $intermap = $this->_getMapFromWikiText($pagetext);
            } elseif ($page->exists()) {
                trigger_error(_("WARNING: InterWikiMap page is unlocked, so not using those links."));
                $intermap = false;
            }
            else 
                $intermap = false;
        } else {
            $intermap = $this->_getMapFromWikiText($pagetext);
        }
        if (!$intermap && defined('INTERWIKI_MAP_FILE'))
            $intermap = $this->_getMapFromFile(INTERWIKI_MAP_FILE);

        $this->_map = $this->_parseMap($intermap);
        $this->_regexp = $this->_getRegexp();
    }

    function GetMap ($pagetext = false) {
    	/*PHP5 Fatal error: Using $this when not in object context */
        if (empty($this->_map)) {
            $map = new PageType_interwikimap($pagetext);
            return $map;
        } else {
            return $this;
        }
    }

    function getRegexp() {
        return $this->_regexp;
    }

    function link ($link, $linktext = false) {
        list ($moniker, $page) = preg_split ("/:/D", $link, 2);
        
        if (!isset($this->_map[$moniker])) {
            return HTML::span(array('class' => 'bad-interwiki'),
                              $linktext ? $linktext : $link);
        }

        $url = $this->_map[$moniker];
        
        // Urlencode page only if it's a query arg.
        // FIXME: this is a somewhat broken heuristic.
        if($moniker == 'Attach' || $moniker == 'Upload') {
            if(preg_match('/^([0-9]+)\/(.*)$/', $page, $matches)) {
                $page_enc = $matches[1].'/'.rawurlencode($matches[2]);
            }
            else {
                $page_enc = rawurlencode($page);
            }
        }
        else {
        $page_enc = strstr($url, '?') ? rawurlencode($page) : $page;
        }

        if (strstr($url, '%s'))
            $url = sprintf($url, $page_enc);
        else
            $url .= $page_enc;

        $link = HTML::a(array('href' => $url));

        if (!$linktext) {
            $link->pushContent(PossiblyGlueIconToText('interwiki', "$moniker:"),
                               HTML::span(array('class' => 'wikipage'), $page));
            $link->setAttr('class', 'interwiki');
        }
        else {
            $link->pushContent(PossiblyGlueIconToText('interwiki', $linktext));
            $link->setAttr('class', 'named-interwiki');
        }
        
        return $link;
    }


    function _parseMap ($text) {
        if (!preg_match_all("/^\s*(\S+)\s+(.+)$/m",
                            $text, $matches, PREG_SET_ORDER))
            return false;

        foreach ($matches as $m) {
            $map[$m[1]] = $m[2];
        }

        // Add virtual monikers Upload: Talk: User:
        // and expand special variables %u, %b, %d

        // Upload: Should be expanded later to user-specific upload dirs. 
        // In the Upload plugin, not here: Upload:ReiniUrban/uploaded-file.png
        if (empty($map['Upload'])) {
            $map['Upload'] = getUploadDataPath();
        }
        if (empty($map['Attach'])) {
            $map['Attach'] = getUploadDataPath();
        }
        // User:ReiniUrban => ReiniUrban or Users/ReiniUrban
        // Can be easily overriden by a customized InterWikiMap: 
        //   User Users/%s
        if (empty($map["User"])) {
            $map["User"] = "%s";
        }
        // Talk:PageName => PageName/Discussion as default, which might be overridden
        if (empty($map["Talk"])) {
            $pagename = $GLOBALS['request']->getArg('pagename');
            // against PageName/Discussion/Discussion
            if (string_ends_with($pagename, SUBPAGE_SEPARATOR._("Discussion")))
                $map["Talk"] = "%s";
            else
                $map["Talk"] = "%s".SUBPAGE_SEPARATOR._("Discussion");
        }

        foreach (array('Upload','User','Talk') as $special) {
            // Expand special variables:
            //   %u => username
            //   %b => wikibaseurl
            //   %d => iso8601 DateTime
            // %s is expanded later to the pagename
            if (strstr($map[$special], '%u'))
                $map[$special] = str_replace($map[$special],
                                             '%u', 
                                             $GLOBALS['request']->_user->_userid);
            if (strstr($map[$special], '%b'))
                $map[$special] = str_replace($map[$special],
                                             '%b', 
                                             PHPWIKI_BASE_URL);
            if (strstr($map[$special], '%d'))
                $map[$special] = str_replace($map[$special],
                                             '%d', 
                                             // such as 2003-01-11T14:03:02+00:00
                                             Iso8601DateTime());
        }

        // Maybe add other monikers also - SemanticWeb link predicates
        // Should they be defined in a RDF? (strict mode)
        // Or should the SemanticWeb lib add it by itself? 
        // (adding only a subset dependent on the context = model)
        return $map;
    }

    function _getMapFromWikiText ($pagetext) {
        if (preg_match('|^<verbatim>\n(.*)^</verbatim>|ms', $pagetext, $m)) {
            return $m[1];
        }
        return false;
    }

    function _getMapFromFile ($filename) {
        if (defined('WARN_NONPUBLIC_INTERWIKIMAP') and WARN_NONPUBLIC_INTERWIKIMAP) {
            $error_html = sprintf(_("Loading InterWikiMap from external file %s."), 
                                  $filename);
            trigger_error( $error_html, E_USER_NOTICE );
        }
        if (!file_exists($filename)) {
            $finder = new FileFinder();
            $filename = $finder->findFile(INTERWIKI_MAP_FILE);
        }
        @$fd = fopen ($filename, "rb");
        @$data = fread ($fd, filesize($filename));
        @fclose ($fd);

        return $data;
    }

    function _getRegexp () {
        if (!$this->_map)
            return '(?:(?!a)a)'; //  Never matches.
        
        foreach (array_keys($this->_map) as $moniker)
            $qkeys[] = preg_quote($moniker, '/');
        return "(?:" . join("|", $qkeys) . ")";
    }
}


/** How to transform text.
 */
class PageFormatter {
    /** Constructor.
     *
     * @param WikiDB_Page $page
     * @param hash $meta Version meta-data.
     */
    function PageFormatter(&$page, $meta) {
        $this->_page = $page;
	$this->_meta = $meta;
	if (!empty($meta['markup']))
	    $this->_markup = $meta['markup'];
	else
	    $this->_markup = 2; // dump used old-markup as empty. 
        // FIXME: To be able to restore old plain-backups we should keep markup 1 as default.
        // New policy: default = new markup (old crashes quite often)
    }

    function _transform($text) {
	include_once('lib/BlockParser.php');
	return TransformText($text, $this->_markup);
    }

    /** Transform the page text.
     *
     * @param string $text  The raw page content (e.g. wiki-text).
     * @return XmlContent   Transformed content.
     */
    function format($text) {
        trigger_error("pure virtual", E_USER_ERROR);
    }
}

class PageFormatter_wikitext extends PageFormatter 
{
    function format(&$text) {
	return HTML::div(array('class' => 'wikitext'),
			 $this->_transform($text));
    }
}

class PageFormatter_interwikimap extends PageFormatter
{
    function format($text) {
        return HTML::div(array('class' => 'wikitext'), 
                         $this->_transform($this->_getHeader($text)),
                         $this->_formatMap($text),
                         $this->_transform($this->_getFooter($text)));
    }

    function _getHeader($text) {
	return preg_replace('/<verbatim>.*/s', '', $text);
    }

    function _getFooter($text) {
	return preg_replace('@.*?(</verbatim>|\Z)@s', '', $text, 1);
    }
    
    function _getMap($pagetext) {
        $map = getInterwikiMap($pagetext);
        return $map->_map;
    }
    
    function _formatMap($pagetext) {
	$map = $this->_getMap($pagetext);
	if (!$map)
	    return HTML::p("<No interwiki map found>"); // Shouldn't happen.

        $mon_attr = array('class' => 'interwiki-moniker');
        $url_attr = array('class' => 'interwiki-url');
        
        $thead = HTML::thead(HTML::tr(HTML::th($mon_attr, _("Moniker")),
				      HTML::th($url_attr, _("InterWiki Address"))));
	foreach ($map as $moniker => $interurl) {
	    $rows[] = HTML::tr(HTML::td($mon_attr, new Cached_WikiLinkIfKnown($moniker)),
			       HTML::td($url_attr, HTML::tt($interurl)));
        }
	
	return HTML::table(array('class' => 'interwiki-map'),
			   $thead,
			   HTML::tbody(false, $rows));
    }
}

class FakePageRevision {
    function FakePageRevision($meta) {
        $this->_meta = $meta;
    }

    function get($key) {
        if (empty($this->_meta[$key]))
            return false;
        return $this->_meta[$key];
    }
}

// abstract base class
class PageFormatter_attach extends PageFormatter
{
    var $type, $prefix;
    
    // Display templated contents for wikiblog, comment and wikiforum
    function format($text) {
    	if (empty($this->type))
    	    trigger_error('PageFormatter_attach->format: $type missing');
        include_once('lib/Template.php');
        global $request;
        $tokens['CONTENT'] = $this->_transform($text);
        $tokens['page'] = $this->_page;
        $tokens['rev'] = new FakePageRevision($this->_meta);

        $name = new WikiPageName($this->_page->getName());
        $tokens[$this->prefix."_PARENT"] = $name->getParent();

        $meta = $this->_meta[$this->type];
        foreach(array('ctime', 'creator', 'creator_id') as $key)
            $tokens[$this->prefix . "_" . strtoupper($key)] = $meta[$key];
        
        return new Template($this->type, $request, $tokens);
    }
}

class PageFormatter_wikiblog extends PageFormatter_attach {
    var $type = 'wikiblog', $prefix = "BLOG";
}
class PageFormatter_comment extends PageFormatter_attach {
    var $type = 'comment', $prefix = "COMMENT";
}
class PageFormatter_wikiforum extends PageFormatter_attach {
    var $type = 'wikiforum', $prefix = "FORUM";
}

/** wikiabuse for htmlarea editing. not yet used.  
 *
 * Warning! Once a page is edited with a htmlarea like control it is
 * stored in HTML and cannot be converted back to WikiText as long as
 * we have no HTML => WikiText or any other interim format (WikiExchangeFormat e.g. XML) 
 * converter. See lib/HtmlParser.php for ongoing work on that. 
 * So it has a viral effect and certain plugins will not work anymore.
 * But a lot of wikiusers seem to like it.
 */
class PageFormatter_html extends PageFormatter
{
    function _transform($text) {
	return $text;
    }
    function format($text) {
    	return $text;
    }
}

/**
 *  FIXME. not yet used
 */
class PageFormatter_pdf extends PageFormatter
{

    function _transform($text) {
	include_once('lib/BlockParser.php');
	return TransformText($text, $this->_markup);
    }

    // one page or set of pages?
    // here we try to format only a single page
    function format($text) {
        include_once('lib/Template.php');
        global $request;
        $tokens['page']    = $this->_page;
        $tokens['CONTENT'] = $this->_transform($text);
        $pagename = $this->_page->getName();

        // This is a XmlElement tree, which must be converted to PDF

        // We can make use of several pdf extensions. This one - fpdf
        // - is pure php and very easy, but looks quite ugly and has a
        // terrible interface, as terrible as most of the othes. 
        // The closest to HTML is htmldoc which needs an external cgi
        // binary.
        // We use a custom HTML->PDF class converter from PHPWebthings
        // to be able to use templates for PDF.
        require_once('lib/fpdf.php');
        require_once('lib/pdf.php');

        $pdf = new PDF();
        $pdf->SetTitle($pagename);
        $pdf->SetAuthor($this->_page->get('author'));
        $pdf->SetCreator(WikiURL($pagename,false,1));
        $pdf->AliasNbPages();
        $pdf->AddPage();
        //TODO: define fonts
        $pdf->SetFont('Times','',12);
        //$pdf->SetFont('Arial','B',16);

        // PDF pagelayout from a special template
        $template = new Template('pdf', $request, $tokens);
        $pdf->ConvertFromHTML($template);

        // specify filename, destination
        $pdf->Output($pagename.".pdf",'I'); // I for stdin or D for download

        // Output([string name [, string dest]])
        return $pdf;
    }
}
// $Log: PageType.php,v $
// Revision 1.47  2005/08/07 09:14:38  rurban
// fix comments
//
// Revision 1.46  2005/08/06 13:09:33  rurban
// allow spaces in interwiki paths, even implicitly. fixes bug #1218733
//
// Revision 1.45  2005/05/06 16:48:41  rurban
// support %u, %b, %d expansion for Upload: User: and Talk: interwiki monikers
//
// Revision 1.44  2005/04/23 11:07:34  rurban
// cache map
//
// Revision 1.43  2005/02/02 20:40:12  rurban
// fix Talk: and User: names and links
//
// Revision 1.42  2005/02/02 19:36:56  rurban
// more plans
//
// Revision 1.41  2005/02/02 19:34:09  rurban
// more maps: Talk, User
//
// Revision 1.40  2005/01/31 12:15:08  rurban
// avoid some cornercase intermap warning. Thanks to Stefan <sonstiges@bayern-mail.de>
//
// Revision 1.39  2005/01/25 06:59:35  rurban
// fix bogus InterWikiMap warning
//
// Revision 1.38  2004/12/26 17:10:44  rurban
// just docs or whitespace
//
// Revision 1.37  2004/12/06 19:49:55  rurban
// enable action=remove which is undoable and seeable in RecentChanges: ADODB ony for now.
// renamed delete_page to purge_page.
// enable action=edit&version=-1 to force creation of a new version.
// added BABYCART_PATH config
// fixed magiqc in adodb.inc.php
// and some more docs
//

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>