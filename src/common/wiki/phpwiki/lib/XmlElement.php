<?php
/**
 * Code for writing XML.
 * @author: Jeff Dairiki,
 *          Reini Urban (php5 tricks)
 *
 * WARNING: This module is very php5 sensitive.
 *        Fixed for 1.3.9 and 1.3.11.
 *        With allow_call_time_pass_reference clean fixes.
 */

/**
 * A sequence of (zero or more) XmlElements (possibly interspersed with
 * plain strings (CDATA).
 */
class XmlContent
{
    public function __construct(/* ... */)
    {
        $this->_content = array();
        $this->_pushContent_array(func_get_args());
    }

    public function pushContent($arg /*, ...*/)
    {
        if (func_num_args() > 1) {
            $this->_pushContent_array(func_get_args());
        } elseif (is_array($arg)) {
            $this->_pushContent_array($arg);
        } else {
            $this->_pushContent($arg);
        }
    }

    public function _pushContent_array($array)
    {
        foreach ($array as $item) {
            if (is_array($item)) {
                $this->_pushContent_array($item);
            } else {
                $this->_pushContent($item);
            }
        }
    }

    public function _pushContent($item)
    {
        if (is_object($item) && strtolower(get_class($item)) == 'xmlcontent') {
            array_splice(
                $this->_content,
                count($this->_content),
                0,
                $item->_content
            );
        } else {
            $this->_content[] = $item;
        }
    }

    public function unshiftContent($arg /*, ...*/)
    {
        if (func_num_args() > 1) {
            $this->_unshiftContent_array(func_get_args());
        } elseif (is_array($arg)) {
            $this->_unshiftContent_array($arg);
        } else {
            $this->_unshiftContent($arg);
        }
    }

    public function _unshiftContent_array($array)
    {
        foreach (array_reverse($array) as $item) {
            if (is_array($item)) {
                $this->_unshiftContent_array($item);
            } else {
                $this->_unshiftContent($item);
            }
        }
    }

    public function _unshiftContent($item)
    {
        if (is_object($item) && strtolower(get_class($item)) == 'xmlcontent') {
            array_splice($this->_content, 0, 0, $item->_content);
        } else {
            array_unshift($this->_content, $item);
        }
    }

    public function getContent()
    {
        return $this->_content;
    }

    public function setContent($arg /* , ... */)
    {
        $this->_content = array();
        $this->_pushContent_array(func_get_args());
    }

    public function printXML()
    {
        foreach ($this->_content as $item) {
            if (is_object($item)) {
                if (method_exists($item, 'printXML')) {
                    $item->printXML();
                } elseif (method_exists($item, 'asXML')) {
                    echo $item->asXML();
                } elseif (method_exists($item, 'asString')) {
                    echo $this->_quote($item->asString());
                } else {
                    printf("==Object(%s)==", get_class($item));
                }
            } else {
                echo $this->_quote((string) $item);
            }
        }
    }

    public function asXML()
    {
        $xml = '';
        foreach ($this->_content as $item) {
            if (is_object($item)) {
                if (method_exists($item, 'asXML')) {
                    $xml .= $item->asXML();
                } elseif (method_exists($item, 'asString')) {
                    $xml .= $this->_quote($item->asString());
                } else {
                    $xml .= sprintf("==Object(%s)==", get_class($item));
                }
            } else {
                $xml .= $this->_quote((string) $item);
            }
        }
        return $xml;
    }

    public function asString()
    {
        $val = '';
        foreach ($this->_content as $item) {
            if (is_object($item)) {
                if (method_exists($item, 'asString')) {
                    $val .= $item->asString();
                } else {
                    $val .= sprintf("==Object(%s)==", get_class($item));
                }
            } else {
                $val .= (string) $item;
            }
        }
        return trim($val);
    }


    /**
     * See if element is empty.
     *
     * Empty means it has no content.
     * @return bool True if empty.
     */
    public function isEmpty()
    {
        if (empty($this->_content)) {
            return true;
        }
        foreach ($this->_content as $x) {
            if (is_string($x) ? strlen($x) : !empty($x)) {
                return false;
            }
        }
        return true;
    }

    public function _quote($string)
    {
        if (!$string) {
            return $string;
        }
        if (isset($GLOBALS['charset'])) {
            return htmlspecialchars($string, ENT_COMPAT, $GLOBALS['charset']);
        }
        return htmlspecialchars($string);
    }
}

/**
 * An XML element.
 *
 * @param $tagname string Tag of html element.
 */
class XmlElement extends XmlContent
{
    public function __construct($tagname /* , $attr_or_content , ...*/)
    {
        //FIXME: php5 incompatible
        parent::__construct();
        $this->_init(func_get_args());
    }

    public function _init($args)
    {
        $initial_args = func_get_args();
        if (! is_array($args)) {
            $args = $initial_args;
        }

        assert(count($args) >= 1);
        //assert(is_string($args[0]));
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
    }

    /** Methods only needed for XmlParser,
     *  to be fully compatible to perl Html::Element
     */
    // doesn't yet work with php5 as __destruct()
    public function _destruct()
    {
        if ($this->hasChildren()) {
            foreach ($this->getChildren() as $node) {
                $node->_destruct();
            }
        }
        unset($this->_tag);
        unset($this->_attr);
        unset($this->_content);
    }

    public function getChildren()
    {
        return $this->_children;
    }

    public function hasChildren()
    {
        return !empty($this->_children);
    }
    /* End XmlParser Methods
     */

    public function getTag()
    {
        return $this->_tag;
    }

    public function setAttr($attr, $value = false)
    {
        if (is_array($attr)) {
            assert($value === false);
            foreach ($attr as $a => $v) {
                $this->_attr[strtolower($a)] = $v;
        //$this->set($a, $v);
            }
            return;
        }

        assert(is_string($attr));

        if ($value === false) {
            unset($this->_attr[$attr]);
        } else {
            if (is_bool($value)) {
                $value = $attr;
            }
            $this->_attr[$attr] = (string) $value;
        }

        if ($attr == 'class') {
            unset($this->_classes);
        }
    }

    public function getAttr($attr)
    {
        if ($attr == 'class') {
            $this->_setClasses();
        }

        if (isset($this->_attr[strtolower($attr)])) {
            return $this->_attr[strtolower($attr)];
        } else {
            return false;
        }
    }

    public function _getClasses()
    {
        if (!isset($this->_classes)) {
            $this->_classes = array();
            if (isset($this->_attr['class'])) {
                $classes = explode(' ', (string) $this->_attr['class']);
                foreach ($classes as $class) {
                    $class = trim($class);
                    if ($class) {
                        $this->_classes[$class] = $class;
                    }
                }
            }
        }
        return $this->_classes;
    }

    public function _setClasses()
    {
        if (isset($this->_classes)) {
            if ($this->_classes) {
                $this->_attr['class'] = join(' ', $this->_classes);
            } else {
                unset($this->_attr['class']);
            }
        }
    }

    /**
     * Manipulate the elements CSS class membership.
     *
     * This adds or remove an elements membership
     * in a give CSS class.
     *
     * @param $class string
     *
     * @param $in_class bool
     *   If true (the default) the element is added to class $class.
     *   If false, the element is removed from the class.
     */
    public function setInClass($class, $in_class = true)
    {
        $this->_getClasses();
        $class = trim($class);
        if ($in_class) {
            $this->_classes[$class] = $class;
        } else {
            unset($this->_classes[$class]);
        }
    }

    /**
     * Is element in a given (CSS) class?
     *
     * This checks for the presence of a particular class in the
     * elements 'class' attribute.
     *
     * @param $class string  The class to check for.
     * @return bool True if the element is a member of $class.
     */
    public function inClass($class)
    {
        $this->_parseClasses();
        return isset($this->_classes[trim($class)]);
    }

    public function startTag()
    {
        $start = "<" . $this->_tag;
        $this->_setClasses();
        foreach ($this->_attr as $attr => $val) {
            if (is_bool($val)) {
                if (!$val) {
                    continue;
                }
                $val = $attr;
            }
            $qval = str_replace("\"", '&quot;', $this->_quote((string) $val));
            $start .= " $attr=\"$qval\"";
        }
        $start .= ">";
        return $start;
    }

    public function emptyTag()
    {
        return substr($this->startTag(), 0, -1) . "/>";
    }


    public function endTag()
    {
        return "</$this->_tag>";
    }


    public function printXML()
    {
        if ($this->isEmpty()) {
            echo $this->emptyTag();
        } else {
            echo $this->startTag();
            // FIXME: The next two lines could be removed for efficiency
            if (!$this->hasInlineContent()) {
                echo "\n";
            }
            XmlContent::printXML();
            echo "</$this->_tag>";
        }
        if (!$this->isInlineElement()) {
            echo "\n";
        }
    }

    public function asXML()
    {
        if ($this->isEmpty()) {
            $xml = $this->emptyTag();
        } else {
            $xml = $this->startTag();
            // FIXME: The next two lines could be removed for efficiency
            if (!$this->hasInlineContent()) {
                $xml .= "\n";
            }
            $xml .= XmlContent::asXML();
            $xml .= "</$this->_tag>";
        }
        if (!$this->isInlineElement()) {
            $xml .= "\n";
        }
        return $xml;
    }

    /**
     * Can this element have inline content?
     *
     * This is a hack, but is probably the best one can do without
     * knowledge of the DTD...
     */
    public function hasInlineContent()
    {
        // This is a hack.
        if (empty($this->_content)) {
            return true;
        }
        if (is_object($this->_content[0])) {
            return false;
        }
        return true;
    }

    /**
     * Is this element part of inline content?
     *
     * This is a hack, but is probably the best one can do without
     * knowledge of the DTD...
     */
    public function isInlineElement()
    {
        return false;
    }
}

class RawXml
{
    public function __construct($xml_text)
    {
        $this->_xml = $xml_text;
    }

    public function printXML()
    {
        echo $this->_xml;
    }

    public function asXML()
    {
        return $this->_xml;
    }

    public function isEmpty()
    {
        return empty($this->_xml);
    }
}

class FormattedText
{
    public function __construct($fs /* , ... */)
    {
        $args = func_get_args();
        if ($fs !== false) {
            $this->_init($args);
        }
    }

    public function _init($args)
    {
        $this->_fs = array_shift($args);

        // PHP's sprintf doesn't support variable width specifiers,
        // like sprintf("%*s", 10, "x"); --- so we won't either.
        $m = array();
        if (! preg_match_all('/(?<!%)%(\d+)\$/x', $this->_fs, $m)) {
            $this->_args  = $args;
        } else {
            // Format string has '%2$s' style argument reordering.
            // PHP doesn't support this.
            if (preg_match('/(?<!%)%[- ]?\d*[^- \d$]/x', $this->_fs)) { // $fmt
                // literal variable name substitution only to keep locale
                // strings uncluttered
                trigger_error(sprintf(
                    _("Can't mix '%s' with '%s' type format strings"),
                    '%1\$s',
                    '%s'
                ), E_USER_WARNING);
            }

            $this->_fs = preg_replace('/(?<!%)%\d+\$/x', '%', $this->_fs);

            $this->_args = array();
            foreach ($m[1] as $argnum) {
                if ($argnum < 1 || $argnum > count($args)) {
                    trigger_error(sprintf(
                        "%s: argument index out of range",
                        $argnum
                    ), E_USER_WARNING);
                }
                $this->_args[] = $args[$argnum - 1];
            }
        }
    }

    public function asXML()
    {
        // Not all PHP's have vsprintf, so...
        $args[] = XmlElement::_quote((string) $this->_fs);
        foreach ($this->_args as $arg) {
            $args[] = AsXML($arg);
        }
        return call_user_func_array('sprintf', $args);
    }

    public function printXML()
    {
        // Not all PHP's have vsprintf, so...
        $args[] = XmlElement::_quote((string) $this->_fs);
        foreach ($this->_args as $arg) {
            $args[] = AsXML($arg);
        }
        call_user_func_array('printf', $args);
    }

    public function asString()
    {
        $args[] = $this->_fs;
        foreach ($this->_args as $arg) {
            $args[] = AsString($arg);
        }
        return call_user_func_array('sprintf', $args);
    }
}

/**
 * PHP5 compatibility
 * Error[2048]: Non-static method XmlContent::_quote() should not be called statically
 */
function XmlContent_quote($string)
{
    if (!$string) {
        return $string;
    }
    if (isset($GLOBALS['charset'])) {
        return htmlspecialchars($string, ENT_COMPAT, $GLOBALS['charset']);
    }
    return htmlspecialchars($string);
}

function PrintXML($val /* , ... */)
{
    if (func_num_args() > 1) {
        foreach (func_get_args() as $arg) {
            PrintXML($arg);
        }
    } elseif (is_object($val)) {
        if (method_exists($val, 'printXML')) {
            $val->printXML();
        } elseif (method_exists($val, 'asXML')) {
            echo $val->asXML();
        } elseif (method_exists($val, 'asString')) {
            echo XmlContent_quote($val->asString());
        } else {
            printf("==Object(%s)==", get_class($val));
        }
    } elseif (is_array($val)) {
        // DEPRECATED:
        // Use XmlContent objects instead of arrays for collections of XmlElements.
        trigger_error(
            "Passing arrays to PrintXML() is deprecated: (" . AsXML($val, true) . ")",
            E_USER_NOTICE
        );
        foreach ($val as $x) {
            PrintXML($x);
        }
    } else {
        echo (string) XmlContent_quote((string) $val);
    }
}

function AsXML($val /* , ... */)
{
    static $nowarn;

    if (func_num_args() > 1) {
        $xml = '';
        foreach (func_get_args() as $arg) {
            $xml .= AsXML($arg);
        }
        return $xml;
    } elseif (is_object($val)) {
        if (method_exists($val, 'asXML')) {
            return $val->asXML();
        } elseif (method_exists($val, 'asString')) {
            return XmlContent_quote($val->asString());
        } else {
            return sprintf("==Object(%s)==", get_class($val));
        }
    } elseif (is_array($val)) {
        // DEPRECATED:
        // Use XmlContent objects instead of arrays for collections of XmlElements.
        if (empty($nowarn)) {
            $nowarn = true;
            trigger_error(
                "Passing arrays to AsXML() is deprecated: (" . AsXML($val) . ")",
                E_USER_NOTICE
            );
            unset($nowarn);
        }
        $xml = '';
        foreach ($val as $x) {
            $xml .= AsXML($x);
        }
        return $xml;
    } else {
        return XmlContent_quote((string) $val);
    }
}

function AsString($val)
{
    if (func_num_args() > 1) {
        $str = '';
        foreach (func_get_args() as $arg) {
            $str .= AsString($arg);
        }
        return $str;
    } elseif (is_object($val)) {
        if (method_exists($val, 'asString')) {
            return $val->asString();
        } else {
            return sprintf("==Object(%s)==", get_class($val));
        }
    } elseif (is_array($val)) {
        // DEPRECATED:
        // Use XmlContent objects instead of arrays for collections of XmlElements.
        trigger_error("Passing arrays to AsString() is deprecated", E_USER_NOTICE);
        $str = '';
        foreach ($val as $x) {
            $str .= AsString($x);
        }
        return $str;
    }

    return (string) $val;
}


function fmt($fs /* , ... */)
{
    $s = new FormattedText(false);

    $args = func_get_args();
    $args[0] = _($args[0]);
    $s->_init($args);
    return $s;
}

// $Log: XmlElement.php,v $
// Revision 1.38  2005/10/10 19:36:09  rurban
// fix comment
//
// Revision 1.37  2005/01/25 07:04:27  rurban
// case-sensitive for php5
//
// Revision 1.36  2004/12/06 19:49:56  rurban
// enable action=remove which is undoable and seeable in RecentChanges: ADODB ony for now.
// renamed delete_page to purge_page.
// enable action=edit&version=-1 to force creation of a new version.
// added BABYCART_PATH config
// fixed magiqc in adodb.inc.php
// and some more docs
//
// Revision 1.35  2004/11/21 11:59:18  rurban
// remove final \n to be ob_cache independent
//
// Revision 1.34  2004/10/12 13:13:19  rurban
// php5 compatibility (5.0.1 ok)
//
// Revision 1.33  2004/07/02 09:55:58  rurban
// more stability fixes: new DISABLE_GETIMAGESIZE if your php crashes when loading LinkIcons: failing getimagesize in old phps; blockparser stabilized
//
// Revision 1.32  2004/06/20 15:30:05  rurban
// get_class case-sensitivity issues
//
// Revision 1.31  2004/06/20 14:42:54  rurban
// various php5 fixes (still broken at blockparser)
// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
