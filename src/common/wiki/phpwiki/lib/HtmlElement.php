<?php
/**
 * Code for writing the HTML subset of XML.
 * @author: Jeff Dairiki
 *
 * This code is now php5 compatible. --2004-04-19 23:51:43 rurban
 */
if (!class_exists("XmlElement")) {
    require_once(dirname(__FILE__) . "/XmlElement.php");
}
if (class_exists("HtmlElement")) {
    return;
}

/**
 * An XML element.
 */
//apd_set_session_trace(35);

class HtmlElement extends XmlElement
{
    public function __construct($tagname /* , $attr_or_content , ...*/)
    {
        $this->_init(func_get_args());
        $this->_properties = HTML::getTagProperties($tagname);
    }

    public function _init($args)
    {
        $initial_args = func_get_args();
        if (!is_array($args)) {
            $args = $initial_args;
        }

        assert(count($args) >= 1);
        assert(is_string($args[0]));
        $this->_tag = array_shift($args);

        if ($args && is_array($args[0])) {
            $this->_attr = array_shift($args);
        } else {
            $this->_attr = array();
            if ($args && $args[0] === false) {
                array_shift($args);
            }
        }
        $this->setContent($args);
        $this->_properties = HTML::getTagProperties($this->_tag);
    }

    /**
     * @access protected
     * This is used by the static factory methods is class HTML.
     */
    public function _init2($args)
    {
        if ($args) {
            if (is_array($args[0])) {
                $this->_attr = array_shift($args);
            } elseif ($args[0] === false) {
                array_shift($args);
            }
        }

        if (count($args) == 1 && is_array($args[0])) {
            $args = $args[0];
        }
        $this->_content = $args;
        return $this;
    }

    /** Add a "tooltip" to an element.
     *
     * @param $tooltip_text string The tooltip text.
     */
    public function addTooltip($tooltip_text)
    {
        $this->setAttr('title', $tooltip_text);

        // FIXME: this should be initialized from title by an onLoad() function.
        //        (though, that may not be possible.)
        $qtooltip = str_replace("'", "\\'", $tooltip_text);
        $this->setAttr(
            'onmouseover',
            sprintf(
                'window.status="%s"; return true;',
                addslashes($tooltip_text)
            )
        );
        $this->setAttr('onmouseout', "window.status='';return true;");
    }

    public function emptyTag()
    {
        if (($this->_properties & HTMLTAG_EMPTY) == 0) {
            return $this->startTag() . "</$this->_tag>";
        }

        return substr($this->startTag(), 0, -1) . " />";
    }

    public function hasInlineContent()
    {
        return ($this->_properties & HTMLTAG_ACCEPTS_INLINE) != 0;
    }

    public function isInlineElement()
    {
        return ($this->_properties & HTMLTAG_INLINE) != 0;
    }
}

function HTML(/* $content, ... */)
{
    return new XmlContent(func_get_args());
}

class HTML extends HtmlElement
{
    public function raw($html_text)
    {
        return new RawXml($html_text);
    }

    public function getTagProperties($tag)
    {
        $props = &$GLOBALS['HTML_TagProperties'];
        return isset($props[$tag]) ? $props[$tag] : 0;
    }

    public function _setTagProperty($prop_flag, $tags)
    {
        $props = &$GLOBALS['HTML_TagProperties'];
        if (is_string($tags)) {
            $tags = preg_split('/\s+/', $tags);
        }
        foreach ($tags as $tag) {
            $tag = trim($tag);
            if ($tag) {
                if (isset($props[$tag])) {
                    $props[$tag] |= $prop_flag;
                } else {
                    $props[$tag] = $prop_flag;
                }
            }
        }
    }

    // Shell script to generate the following static methods:
    //
    // #!/bin/sh
    // function mkfuncs () {
    //     for tag in "$@"
    //     do
    //         echo "    function $tag (/*...*/) {"
    //         echo "        \$el = new HtmlElement('$tag');"
    //         echo "        return \$el->_init2(func_get_args());"
    //         echo "    }"
    //     done
    // }
    // d='
    //     /****************************************/'
    // mkfuncs link meta style script noscript
    // echo "$d"
    // mkfuncs a img br span
    // echo "$d"
    // mkfuncs h1 h2 h3 h4 h5 h6
    // echo "$d"
    // mkfuncs hr div p pre blockquote
    // echo "$d"
    // mkfuncs em strong small
    // echo "$d"
    // mkfuncs tt u sup sub
    // echo "$d"
    // mkfuncs ul ol dl li dt dd
    // echo "$d"
    // mkfuncs table caption thead tbody tfoot tr td th colgroup col
    // echo "$d"
    // mkfuncs form input option select textarea
    // echo "$d"
    // mkfuncs area map frame frameset iframe nobody

    public function link(/*...*/)
    {
        $el = new HtmlElement('link');
        return $el->_init2(func_get_args());
    }
    public function meta(/*...*/)
    {
        $el = new HtmlElement('meta');
        return $el->_init2(func_get_args());
    }
    public function style(/*...*/)
    {
        $el = new HtmlElement('style');
        return $el->_init2(func_get_args());
    }
    public static function script(/*...*/)
    {
        $el = new HtmlElement('script');
        return $el->_init2(func_get_args());
    }
    public function noscript(/*...*/)
    {
        $el = new HtmlElement('noscript');
        return $el->_init2(func_get_args());
    }

    public static function a(/*...*/)
    {
        $el = new HtmlElement('a');
        return $el->_init2(func_get_args());
    }
    public static function img(/*...*/)
    {
        $el = new HtmlElement('img');
        return $el->_init2(func_get_args());
    }
    public static function br(/*...*/)
    {
        $el = new HtmlElement('br');
        return $el->_init2(func_get_args());
    }
    public function span(/*...*/)
    {
        $el = new HtmlElement('span');
        return $el->_init2(func_get_args());
    }

    public function h1(/*...*/)
    {
        $el = new HtmlElement('h1');
        return $el->_init2(func_get_args());
    }
    public function h2(/*...*/)
    {
        $el = new HtmlElement('h2');
        return $el->_init2(func_get_args());
    }
    public static function h3(/*...*/)
    {
        $el = new HtmlElement('h3');
        return $el->_init2(func_get_args());
    }
    public function h4(/*...*/)
    {
        $el = new HtmlElement('h4');
        return $el->_init2(func_get_args());
    }
    public function h5(/*...*/)
    {
        $el = new HtmlElement('h5');
        return $el->_init2(func_get_args());
    }
    public function h6(/*...*/)
    {
        $el = new HtmlElement('h6');
        return $el->_init2(func_get_args());
    }

    public function hr(/*...*/)
    {
        $el = new HtmlElement('hr');
        return $el->_init2(func_get_args());
    }
    public static function div(/*...*/)
    {
        $el = new HtmlElement('div');
        return $el->_init2(func_get_args());
    }
    public function p(/*...*/)
    {
        $el = new HtmlElement('p');
        return $el->_init2(func_get_args());
    }
    public function pre(/*...*/)
    {
        $el = new HtmlElement('pre');
        return $el->_init2(func_get_args());
    }
    public function blockquote(/*...*/)
    {
        $el = new HtmlElement('blockquote');
        return $el->_init2(func_get_args());
    }

    public function em(/*...*/)
    {
        $el = new HtmlElement('em');
        return $el->_init2(func_get_args());
    }
    public static function strong(/*...*/)
    {
        $el = new HtmlElement('strong');
        return $el->_init2(func_get_args());
    }
    public function small(/*...*/)
    {
        $el = new HtmlElement('small');
        return $el->_init2(func_get_args());
    }

    public function tt(/*...*/)
    {
        $el = new HtmlElement('tt');
        return $el->_init2(func_get_args());
    }
    public function u(/*...*/)
    {
        $el = new HtmlElement('u');
        return $el->_init2(func_get_args());
    }
    public function sup(/*...*/)
    {
        $el = new HtmlElement('sup');
        return $el->_init2(func_get_args());
    }
    public function sub(/*...*/)
    {
        $el = new HtmlElement('sub');
        return $el->_init2(func_get_args());
    }

    public function ul(/*...*/)
    {
        $el = new HtmlElement('ul');
        return $el->_init2(func_get_args());
    }
    public function ol(/*...*/)
    {
        $el = new HtmlElement('ol');
        return $el->_init2(func_get_args());
    }
    public function dl(/*...*/)
    {
        $el = new HtmlElement('dl');
        return $el->_init2(func_get_args());
    }
    public function li(/*...*/)
    {
        $el = new HtmlElement('li');
        return $el->_init2(func_get_args());
    }
    public function dt(/*...*/)
    {
        $el = new HtmlElement('dt');
        return $el->_init2(func_get_args());
    }
    public function dd(/*...*/)
    {
        $el = new HtmlElement('dd');
        return $el->_init2(func_get_args());
    }

    public function table(/*...*/)
    {
        $el = new HtmlElement('table');
        return $el->_init2(func_get_args());
    }
    public function caption(/*...*/)
    {
        $el = new HtmlElement('caption');
        return $el->_init2(func_get_args());
    }
    public function thead(/*...*/)
    {
        $el = new HtmlElement('thead');
        return $el->_init2(func_get_args());
    }
    public function tbody(/*...*/)
    {
        $el = new HtmlElement('tbody');
        return $el->_init2(func_get_args());
    }
    public function tfoot(/*...*/)
    {
        $el = new HtmlElement('tfoot');
        return $el->_init2(func_get_args());
    }
    public function tr(/*...*/)
    {
        $el = new HtmlElement('tr');
        return $el->_init2(func_get_args());
    }
    public function td(/*...*/)
    {
        $el = new HtmlElement('td');
        return $el->_init2(func_get_args());
    }
    public function th(/*...*/)
    {
        $el = new HtmlElement('th');
        return $el->_init2(func_get_args());
    }
    public function colgroup(/*...*/)
    {
        $el = new HtmlElement('colgroup');
        return $el->_init2(func_get_args());
    }
    public function col(/*...*/)
    {
        $el = new HtmlElement('col');
        return $el->_init2(func_get_args());
    }

    public function form(/*...*/)
    {
        $el = new HtmlElement('form');
        return $el->_init2(func_get_args());
    }
    public function input(/*...*/)
    {
        $el = new HtmlElement('input');
        return $el->_init2(func_get_args());
    }
    public function button(/*...*/)
    {
        $el = new HtmlElement('button');
        return $el->_init2(func_get_args());
    }
    public function option(/*...*/)
    {
        $el = new HtmlElement('option');
        return $el->_init2(func_get_args());
    }
    public function select(/*...*/)
    {
        $el = new HtmlElement('select');
        return $el->_init2(func_get_args());
    }
    public function textarea(/*...*/)
    {
        $el = new HtmlElement('textarea');
        return $el->_init2(func_get_args());
    }
    public function label(/*...*/)
    {
        $el = new HtmlElement('label');
        return $el->_init2(func_get_args());
    }

    public function area(/*...*/)
    {
        $el = new HtmlElement('area');
        return $el->_init2(func_get_args());
    }
    public function map(/*...*/)
    {
        $el = new HtmlElement('map');
        return $el->_init2(func_get_args());
    }
    public function frame(/*...*/)
    {
        $el = new HtmlElement('frame');
        return $el->_init2(func_get_args());
    }
    public function frameset(/*...*/)
    {
        $el = new HtmlElement('frameset');
        return $el->_init2(func_get_args());
    }
    public function iframe(/*...*/)
    {
        $el = new HtmlElement('iframe');
        return $el->_init2(func_get_args());
    }
    public function nobody(/*...*/)
    {
        $el = new HtmlElement('nobody');
        return $el->_init2(func_get_args());
    }
    public function object(/*...*/)
    {
        $el = new HtmlElement('object');
        return $el->_init2(func_get_args());
    }
    public function embed(/*...*/)
    {
        $el = new HtmlElement('embed');
        return $el->_init2(func_get_args());
    }
    public static function fieldset(/*...*/)
    {
        $el = new HtmlElement('fieldset');
        return $el->_init2(func_get_args());
    }
    public static function legend(/*...*/)
    {
        $el = new HtmlElement('legend');
        return $el->_init2(func_get_args());
    }
}

define('HTMLTAG_EMPTY', 1);
define('HTMLTAG_INLINE', 2);
define('HTMLTAG_ACCEPTS_INLINE', 4);


HTML::_setTagProperty(
    HTMLTAG_EMPTY,
    'area base basefont br col frame hr img input isindex link meta param'
);
HTML::_setTagProperty(
    HTMLTAG_ACCEPTS_INLINE,
    // %inline elements:
    'b big i small tt ' // %fontstyle
    . 's strike u ' // (deprecated)
    . 'abbr acronym cite code dfn em kbd samp strong var ' //%phrase
    . 'a img object embed br script map q sub sup span bdo '//%special
    . 'button input label option select textarea label ' //%formctl

    // %block elements which contain inline content
    . 'address h1 h2 h3 h4 h5 h6 p pre '
    // %block elements which contain either block or inline content
    . 'div fieldset frameset'

    // other with inline content
    . 'caption dt label legend '
    // other with either inline or block
    . 'dd del ins li td th colgroup'
);

HTML::_setTagProperty(
    HTMLTAG_INLINE,
    // %inline elements:
    'b big i small tt ' // %fontstyle
    . 's strike u ' // (deprecated)
    . 'abbr acronym cite code dfn em kbd samp strong var ' //%phrase
    . 'a img object br script map q sub sup span bdo '//%special
    . 'button input label option select textarea ' //%formctl
    . 'nobody iframe'
);

/**
 * Generate hidden form input fields.
 *
 * @param $query_args hash  A hash mapping names to values for the hidden inputs.
 * Values in the hash can themselves be hashes.  The will result in hidden inputs
 * which will reconstruct the nested structure in the resulting query args as
 * processed by PHP.
 *
 * Example:
 *
 * $args = array('x' => '2',
 *               'y' => array('a' => 'aval', 'b' => 'bval'));
 * $inputs = HiddenInputs($args);
 *
 * Will result in:
 *
 *  <input type="hidden" name="x" value = "2" />
 *  <input type="hidden" name="y[a]" value = "aval" />
 *  <input type="hidden" name="y[b]" value = "bval" />
 *
 * @return object An XmlContent object containing the inputs.
 */
function HiddenInputs($query_args, $pfx = false, $exclude = array())
{
    $inputs = HTML();

    foreach ($query_args as $key => $val) {
        if (in_array($key, $exclude)) {
            continue;
        }
        $name = $pfx ? $pfx . "[$key]" : $key;
        if (is_array($val)) {
            $inputs->pushContent(HiddenInputs($val, $name));
        } else {
            $inputs->pushContent(HTML::input(array('type' => 'hidden',
                                                   'name' => $name,
                                                   'value' => $val)));
        }
    }
    return $inputs;
}


/** Generate a <script> tag containing javascript.
 *
 * @param string $js  The javascript.
 * @param string $script_args  (optional) hash of script tags options
 *                             e.g. to provide another version or the defer attr
 * @return HtmlElement A <script> element.
 */
function JavaScript($js, $script_args = false)
{
    $default_script_args = array(//'version' => 'JavaScript', // not xhtml conformant
                                 'type' => 'text/javascript');
    $script_args = $script_args ? array_merge($default_script_args, $script_args)
                                : $default_script_args;
    if (empty($js)) {
        return HTML(HTML::script($script_args), "\n");
    } else { // see http://devedge.netscape.com/viewsource/2003/xhtml-style-script/
        return HTML(HTML::script(
            $script_args,
            new RawXml((ENABLE_XHTML_XML ? "\n//<![CDATA[" : "\n<!--//")
                                       . "\n" . trim($js) . "\n"
            . (ENABLE_XHTML_XML ? "//]]>\n" : "// -->"))
        ), "\n");
    }
}

/** Conditionally display content based of whether javascript is supported.
 *
 * This conditionally (on the client side) displays one of two alternate
 * contents depending on whether the client supports javascript.
 *
 * NOTE:
 * The content you pass as arguments to this function must be block-level.
 * (This is because the <noscript> tag is block-level.)
 *
 * @param mixed $if_content Content to display if the browser supports
 * javascript.
 *
 * @param mixed $else_content Content to display if the browser does
 * not support javascript.
 *
 * @return XmlContent
 */
function IfJavaScript($if_content = false, $else_content = false)
{
    $html = array();
    if ($if_content) {
        $xml = AsXML($if_content);
        $js = sprintf(
            'document.write("%s");',
            addcslashes($xml, "\0..\37!@\\\177..\377")
        );
        $html[] = JavaScript($js);
    }
    if ($else_content) {
        $html[] = HTML::noscript(false, $else_content);
    }
    return HTML($html);
}

/**
 $Log: HtmlElement.php,v $
 Revision 1.47  2005/08/06 12:53:36  rurban
 beautify SCRIPT lines

 Revision 1.46  2005/01/25 06:50:33  rurban
 added label

 Revision 1.45  2005/01/10 18:05:56  rurban
 php5 case-sensitivity

 Revision 1.44  2005/01/08 20:58:19  rurban
 ending space after colgroup breaks _setTagProperty

 Revision 1.43  2004/11/21 11:59:14  rurban
 remove final \n to be ob_cache independent

 Revision 1.42  2004/09/26 17:09:23  rurban
 add SVG support for Ploticus (and hopefully all WikiPluginCached types)
 SWF not yet.

 Revision 1.41  2004/08/05 17:31:50  rurban
 more xhtml conformance fixes

 Revision 1.40  2004/06/25 14:29:17  rurban
 WikiGroup refactoring:
   global group attached to user, code for not_current user.
   improved helpers for special groups (avoid double invocations)
 new experimental config option ENABLE_XHTML_XML (fails with IE, and document.write())
 fixed a XHTML validation error on userprefs.tmpl

 Revision 1.39  2004/05/17 13:36:49  rurban
 Apply RFE #952323 "ExternalSearchPlugin improvement", but
   with <button><img></button>

 Revision 1.38  2004/05/12 10:49:54  rurban
 require_once fix for those libs which are loaded before FileFinder and
   its automatic include_path fix, and where require_once doesn't grok
   dirname(__FILE__) != './lib'
 upgrade fix with PearDB
 navbar.tmpl: remove spaces for IE &nbsp; button alignment

 Revision 1.37  2004/04/26 20:44:34  rurban
 locking table specific for better databases

 Revision 1.36  2004/04/19 21:51:41  rurban
 php5 compatibility: it works!

 Revision 1.35  2004/04/19 18:27:45  rurban
 Prevent from some PHP5 warnings (ref args, no :: object init)
   php5 runs now through, just one wrong XmlElement object init missing
 Removed unneccesary UpgradeUser lines
 Changed WikiLink to omit version if current (RecentChanges)

 Revision 1.34  2004/03/24 19:39:02  rurban
 php5 workaround code (plus some interim debugging code in XmlElement)
   php5 doesn't work yet with the current XmlElement class constructors,
   WikiUserNew does work better than php4.
 rewrote WikiUserNew user upgrading to ease php5 update
 fixed pref handling in WikiUserNew
 added Email Notification
 added simple Email verification
 removed emailVerify userpref subclass: just a email property
 changed pref binary storage layout: numarray => hash of non default values
 print optimize message only if really done.
 forced new cookie policy: delete pref cookies, use only WIKI_ID as plain string.
   prefs should be stored in db or homepage, besides the current session.

 Revision 1.33  2004/03/18 22:32:33  rurban
 work to make it php5 compatible

 Revision 1.32  2004/02/15 21:34:37  rurban
 PageList enhanced and improved.
 fixed new WikiAdmin... plugins
 editpage, Theme with exp. htmlarea framework
   (htmlarea yet committed, this is really questionable)
 WikiUser... code with better session handling for prefs
 enhanced UserPreferences (again)
 RecentChanges for show_deleted: how should pages be deleted then?

 Revision 1.31  2003/02/27 22:47:26  dairiki
 New functions in HtmlElement:

 JavaScript($js)
    Helper for generating javascript.

 IfJavaScript($if_content, $else_content)
    Helper for generating
       <script>document.write('...')</script><noscript>...</noscript>
    constructs.

 Revision 1.30  2003/02/17 06:02:25  dairiki
 Remove functions HiddenGets() and HiddenPosts().

 These functions were evil.  They didn't check the request method,
 so they often resulted in GET args being converted to POST args,
 etc...

 One of these is still used in lib/plugin/WikiAdminSelect.php,
 but, so far as I can tell, that code is both broken _and_ it
 doesn't do anything.

 Revision 1.29  2003/02/15 01:54:19  dairiki
 Added HTML::meta() for <meta> tag.

 Revision 1.28  2003/01/04 02:32:30  carstenklapp
 Added 'col' and 'colgroup' table elements used by PluginManager.

 */

// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
