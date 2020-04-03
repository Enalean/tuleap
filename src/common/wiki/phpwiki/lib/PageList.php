<?php
/**
 * List a number of pagenames, optionally as table with various columns.
 * This library relieves some work for these plugins:
 *
 * AllPages, BackLinks, LikePages, MostPopular, TitleSearch, WikiAdmin* and more
 *
 * It also allows dynamic expansion of those plugins to include more
 * columns in their output.
 *
 * Column 'info=' arguments:
 *
 * 'pagename' _("Page Name")
 * 'mtime'    _("Last Modified")
 * 'hits'     _("Hits")
 * 'summary'  _("Last Summary")
 * 'version'  _("Version")),
 * 'author'   _("Last Author")),
 * 'locked'   _("Locked"), _("locked")
 * 'minor'    _("Minor Edit"), _("minor")
 * 'markup'   _("Markup")
 * 'size'     _("Size")
 * 'creator'  _("Creator")
 * 'owner'    _("Owner")
 * 'checkbox'  selectable checkbox at the left.
 * 'content'
 *
 * Special, custom columns: Either theme or plugin (WikiAdmin*) specific.
 * 'remove'   _("Remove")
 * 'perm'     _("Permission Mask")
 * 'acl'      _("ACL")
 * 'renamed_pagename'   _("Rename to")
 * 'ratingwidget', ... wikilens theme specific.
 * 'custom'   See plugin/_WikiTranslation
 *
 * Symbolic 'info=' arguments:
 * 'all'       All columns except the special columns
 * 'most'      pagename, mtime, author, size, hits, ...
 * 'some'      pagename, mtime, author
 *
 * FIXME: In this refactoring I (Jeff) have un-implemented _ctime, _cauthor, and
 * number-of-revision.  Note the _ctime and _cauthor as they were implemented
 * were somewhat flawed: revision 1 of a page doesn't have to exist in the
 * database.  If lots of revisions have been made to a page, it's more than likely
 * that some older revisions (include revision 1) have been cleaned (deleted).
 *
 * DONE:
 *   paging support: limit, offset args
 *   check PagePerm "list" access-type,
 *   all columns are sortable (Thanks to the wikilens team).
 *   cols > 1, comma, azhead, ordered (OL lists)
 *   ->supportedArgs() which arguments are supported, so that the plugin
 *                     doesn't explictly need to declare it
 *
 * FIXED:
 *   fix memory exhaustion on large pagelists with old --memory-limit php's only.
 *   Status: improved 2004-06-25 16:19:36 rurban
 *     but needs further testing.
 */
class _PageList_Column_base
{
    public $_tdattr = array();

    public function __construct($default_heading, $align = false)
    {
        $this->_heading = $default_heading;

        if ($align) {
            // align="char" isn't supported by any browsers yet :(
            //if (is_array($align))
            //    $this->_tdattr = $align;
            //else
            $this->_tdattr['align'] = $align;
        }
    }

    public function format($pagelist, $page_handle, &$revision_handle)
    {
        return HTML::td(
            $this->_tdattr,
            HTML::raw('&nbsp;'),
            $this->_getValue($page_handle, $revision_handle),
            HTML::raw('&nbsp;')
        );
    }

    public function getHeading()
    {
        return $this->_heading;
    }

    public function setHeading($heading)
    {
        $this->_heading = $heading;
    }

    // old-style heading
    public function heading()
    {
        // allow sorting?
        if (1 /* or in_array($this->_field, PageList::sortable_columns())*/) {
            // multiple comma-delimited sortby args: "+hits,+pagename"
            // asc or desc: +pagename, -pagename
            $sortby = PageList::sortby($this->_field, 'flip_order');
            //Fixme: pass all also other GET args along. (limit, p[])
            //TODO: support GET and POST
            $s = HTML::a(
                array('href' =>
                               $GLOBALS['request']->GetURLtoSelf(array('sortby' => $sortby,
                                                                       'nocache' => '1')),
                               'class' => 'pagetitle',
                               'title' => sprintf(_("Sort by %s"), $this->_field)),
                HTML::raw('&nbsp;'),
                HTML::u($this->_heading),
                HTML::raw('&nbsp;')
            );
        } else {
            $s = HTML(HTML::raw('&nbsp;'), HTML::u($this->_heading), HTML::raw('&nbsp;'));
        }
        return HTML::th(array('align' => 'center'), $s);
    }

    // new grid-style sortable heading
    // see activeui.js
    public function button_heading($pagelist, $colNum)
    {
        global $WikiTheme, $request;
        // allow sorting?
        if (1 /* or in_array($this->_field, PageList::sortable_columns()) */) {
            // multiple comma-delimited sortby args: "+hits,+pagename"
            $src = false;
            $noimg_src = $WikiTheme->getButtonURL('no_order');
            if ($noimg_src) {
                $noimg = HTML::img(array('src' => $noimg_src,
                                         'width' => '7',
                                         'height' => '7',
                                         'border' => 0,
                                         'alt'    => '.'));
            } else {
                $noimg = HTML::raw('&nbsp;');
            }
            if ($request->getArg('sortby')) {
                if ($pagelist->sortby($colNum, 'check')) { // show icon?
                    $sortby = $pagelist->sortby($request->getArg('sortby'), 'flip_order');
                    //$request->setArg('sortby', $sortby);
                    $desc = (substr($sortby, 0, 1) == '-'); // asc or desc? (+pagename, -pagename)
                    $src = $WikiTheme->getButtonURL($desc ? 'asc_order' : 'desc_order');
                } else {
                    $sortby = $pagelist->sortby($colNum, 'init');
                }
            } else {
                $sortby = $pagelist->sortby($colNum, 'init');
            }
            if (!$src) {
                $img = $noimg;
                //$img->setAttr('alt', _("Click to sort"));
            } else {
                $img = HTML::img(array('src' => $src,
                                       'width' => '7',
                                       'height' => '7',
                                       'border' => 0,
                                       'alt' => _("Click to reverse sort order")));
            }
            $s = HTML::a(
                array('href' =>
                               //Fixme: pass all also other GET args along. (limit is ok, p[])
                               //Fixme: convert to POST submit[sortby]
                               $request->GetURLtoSelf(array('sortby' => $sortby,
                                                            /*'nocache' => '1'*/)),
                               'class' => 'gridbutton',
                               'title' => sprintf(_("Click to sort by %s"), $this->_field)),
                HTML::raw('&nbsp;'),
                $noimg,
                HTML::raw('&nbsp;'),
                $this->_heading,
                HTML::raw('&nbsp;'),
                $img,
                HTML::raw('&nbsp;')
            );
        } else {
            $s = HTML(HTML::raw('&nbsp;'), $this->_heading, HTML::raw('&nbsp;'));
        }
        return HTML::th(array('align' => 'center', 'valign' => 'middle',
                              'class' => 'gridbutton'), $s);
    }

    /**
     * Take two columns of this type and compare them.
     * An undefined value is defined to be < than the smallest defined value.
     * This base class _compare only works if the value is simple (e.g., a number).
     *
     * @param  $colvala  $this->_getValue() of column a
     * @param  $colvalb  $this->_getValue() of column b
     *
     * @return -1 if $a < $b, 1 if $a > $b, 0 otherwise.
     */
    public function _compare($colvala, $colvalb)
    {
        if (is_string($colvala)) {
            return strcmp($colvala, $colvalb);
        }
        $ret = 0;
        if (($colvala === $colvalb) || (!isset($colvala) && !isset($colvalb))) {
        } else {
            $ret = (!isset($colvala) || ($colvala < $colvalb)) ? -1 : 1;
        }
        return $ret;
    }
}

class _PageList_Column extends _PageList_Column_base
{
    public function __construct($field, $default_heading, $align = false)
    {
        parent::__construct($default_heading, $align);

        $this->_need_rev = substr($field, 0, 4) == 'rev:';
        $this->_iscustom = substr($field, 0, 7) == 'custom:';
        if ($this->_iscustom) {
            $this->_field = substr($field, 7);
        } elseif ($this->_need_rev) {
            $this->_field = substr($field, 4);
        } else {
            $this->_field = $field;
        }
    }

    public function _getValue($page_handle, &$revision_handle)
    {
        if ($this->_need_rev) {
            if (!$revision_handle) {
                // columns which need the %content should override this. (size, hi_content)
                $revision_handle = $page_handle->getCurrentRevision(false);
            }
            return $revision_handle->get($this->_field);
        } else {
            return $page_handle->get($this->_field);
        }
    }

    public function _getSortableValue($page_handle, &$revision_handle)
    {
        $val = $this->_getValue($page_handle, $revision_handle);
        if ($this->_field == 'hits') {
            return (int) $val;
        } elseif (is_object($val)) {
            return $val->asString();
        } else {
            return (string) $val;
        }
    }
}

/* overcome a call_user_func limitation by not being able to do:
 * call_user_func_array(array(&$class, $class_name), $params);
 * So we need $class = new $classname($params);
 * And we add a 4th param to get at the parent $pagelist object
 */
class _PageList_Column_custom extends _PageList_Column
{
    public function __construct($params)
    {
        $this->_pagelist = $params[3];
        parent::__construct($params[0], $params[1], $params[2]);
    }
}

class _PageList_Column_size extends _PageList_Column
{
    public function format($pagelist, $page_handle, &$revision_handle)
    {
        return HTML::td(
            $this->_tdattr,
            HTML::raw('&nbsp;'),
            $this->_getValue($page_handle, $revision_handle, $pagelist),
            HTML::raw('&nbsp;')
        );
    }

    public function _getValue($page_handle, &$revision_handle, &$pagelist = [])
    {
        if (
            !$revision_handle or (!$revision_handle->_data['%content']
                                  or $revision_handle->_data['%content'] === true)
        ) {
            $revision_handle = $page_handle->getCurrentRevision(true);
            unset($revision_handle->_data['%pagedata']['_cached_html']);
        }
        $size = $this->_getSize($revision_handle);
        // we can safely purge the content when it is not sortable
        if (empty($pagelist->_sortby[$this->_field])) {
            unset($revision_handle->_data['%content']);
        }
        return $size;
    }

    public function _getSortableValue($page_handle, &$revision_handle)
    {
        if (!$revision_handle) {
            $revision_handle = $page_handle->getCurrentRevision(true);
        }
        return (empty($revision_handle->_data['%content']))
               ? 0 : strlen($revision_handle->_data['%content']);
    }

    public function _getSize($revision_handle)
    {
        $bytes = @strlen($revision_handle->_data['%content']);
        return ByteFormatter($bytes);
    }
}


class _PageList_Column_bool extends _PageList_Column
{
    public function __construct($field, $default_heading, $text = 'yes')
    {
        parent::__construct($field, $default_heading, 'center');
        $this->_textIfTrue = $text;
        $this->_textIfFalse = new RawXml('&#8212;'); //mdash
    }

    public function _getValue($page_handle, &$revision_handle)
    {
        //FIXME: check if $this is available in the parent (->need_rev)
        $val = _PageList_Column::_getValue($page_handle, $revision_handle);
        return $val ? $this->_textIfTrue : $this->_textIfFalse;
    }
}

class _PageList_Column_checkbox extends _PageList_Column
{
    public function __construct($field, $default_heading, $name = 'p')
    {
        $this->_name = $name;
        $heading = HTML::input(array('type'  => 'button',
                                     'title' => _("Click to de-/select all pages"),
                                     //'width' => '100%',
                                     'name'  => $default_heading,
                                     'value' => $default_heading,
                                     'onclick' => "flipAll(this.form)"
                                     ));
        parent::__construct($field, $heading, 'center');
    }
    public function _getValue($page_handle, &$revision_handle, $pagelist = [])
    {
        $pagename = $page_handle->getName();
        $selected = !empty($pagelist->_selected[$pagename]);
        if (strstr($pagename, '[') or strstr($pagename, ']')) {
            $pagename = str_replace(array('[',']'), array('%5B','%5D'), $pagename);
        }
        if ($selected) {
            return HTML::input(array('type' => 'checkbox',
                                     'name' => $this->_name . "[$pagename]",
                                     'value' => 1,
                                     'checked' => 'CHECKED'));
        } else {
            return HTML::input(array('type' => 'checkbox',
                                     'name' => $this->_name . "[$pagename]",
                                     'value' => 1));
        }
    }
    public function format($pagelist, $page_handle, &$revision_handle)
    {
        return HTML::td(
            $this->_tdattr,
            HTML::raw('&nbsp;'),
            $this->_getValue($page_handle, $revision_handle, $pagelist),
            HTML::raw('&nbsp;')
        );
    }
    // don't sort this javascript button
    public function button_heading($pagelist, $colNum)
    {
        $s = HTML(HTML::raw('&nbsp;'), $this->_heading, HTML::raw('&nbsp;'));
        return HTML::th(array('align' => 'center', 'valign' => 'middle',
                              'class' => 'gridbutton'), $s);
    }
}

class _PageList_Column_time extends _PageList_Column
{
    public function __construct($field, $default_heading)
    {
        parent::__construct($field, $default_heading, 'right');
        global $WikiTheme;
        $this->Theme = &$WikiTheme;
    }

    public function _getValue($page_handle, &$revision_handle)
    {
        $time = _PageList_Column::_getValue($page_handle, $revision_handle);
        return $this->Theme->formatDateTime($time);
    }
}

class _PageList_Column_version extends _PageList_Column
{
    public function _getValue($page_handle, &$revision_handle)
    {
        if (!$revision_handle) {
            $revision_handle = $page_handle->getCurrentRevision();
        }
        return $revision_handle->getVersion();
    }
}

// Output is hardcoded to limit of first 50 bytes. Otherwise
// on very large Wikis this will fail if used with AllPages
// (PHP memory limit exceeded)
class _PageList_Column_content extends _PageList_Column
{
    public function __construct($field, $default_heading, $align = false)
    {
        parent::__construct($field, $default_heading, $align);
        $this->bytes = 50;
        if ($field == 'content') {
            $this->_heading .= sprintf(
                _(" ... first %d bytes"),
                $this->bytes
            );
        } elseif ($field == 'hi_content') {
            if (!empty($_POST['admin_replace'])) {
                $search = $_POST['admin_replace']['from'];
                $this->_heading .= sprintf(
                    _(" ... around %s"),
                    '»' . $search . '«'
                );
            }
        }
    }

    public function _getValue($page_handle, &$revision_handle)
    {
        if (
            !$revision_handle or (!$revision_handle->_data['%content']
                                  or $revision_handle->_data['%content'] === true)
        ) {
            $revision_handle = $page_handle->getCurrentRevision(true);
        }
        // Not sure why implode is needed here, I thought
        // getContent() already did this, but it seems necessary.
        $c = implode("\n", $revision_handle->getContent());
        if (empty($pagelist->_sortby[$this->_field])) {
            unset($revision_handle->_data['%content']);
        }
        if ($this->_field == 'hi_content') {
            unset($revision_handle->_data['%pagedata']['_cached_html']);
            $search = $_POST['admin_replace']['from'];
            if ($search and ($i = strpos($c, $search))) {
                $l = strlen($search);
                $j = max(0, $i - ($this->bytes / 2));
                return HTML::div(
                    array('style' => 'font-size:x-small'),
                    HTML::div(
                        array('class' => 'transclusion'),
                        HTML::span(substr($c, $j, ($this->bytes / 2))),
                        HTML::span(array("style" => "background:yellow"), $search),
                        HTML::span(substr($c, $i + $l, ($this->bytes / 2)))
                    )
                );
            } else {
                $c = sprintf(
                    _("%s not found"),
                    '»' . $search . '«'
                );
                return HTML::div(
                    array('style' => 'font-size:x-small','align' => 'center'),
                    $c
                );
            }
        } elseif (($len = strlen($c)) > $this->bytes) {
            $c = substr($c, 0, $this->bytes);
        }
        include_once('lib/BlockParser.php');
        // false --> don't bother processing hrefs for embedded WikiLinks
        $ct = TransformText($c, $revision_handle->get('markup'), false);
        if (empty($pagelist->_sortby[$this->_field])) {
            unset($revision_handle->_data['%pagedata']['_cached_html']);
        }
        return HTML::div(
            array('style' => 'font-size:x-small'),
            HTML::div(array('class' => 'transclusion'), $ct),
            // Don't show bytes here if size column present too
                         ($this->parent->_columns_seen['size'] or !$len) ? "" :
            ByteFormatter($len, /*$longformat = */true)
        );
    }

    public function _getSortableValue($page_handle, &$revision_handle)
    {
        return substr(_PageList_Column::_getValue($page_handle, $revision_handle), 0, 50);
    }
}

class _PageList_Column_author extends _PageList_Column
{
    public function __construct($field, $default_heading, $align = false)
    {
        parent::__construct($field, $default_heading, $align);
        $this->dbi = $GLOBALS['request']->getDbh();
    }

    public function _getValue($page_handle, &$revision_handle)
    {
        $author = _PageList_Column::_getValue($page_handle, $revision_handle);
        if (isWikiWord($author) && $this->dbi->isWikiPage($author)) {
            return WikiLink($author);
        } else {
            return $author;
        }
    }
}

class _PageList_Column_owner extends _PageList_Column_author
{
    public function _getValue($page_handle, &$revision_handle)
    {
        $author = $page_handle->getOwner();
        if (isWikiWord($author) && $this->dbi->isWikiPage($author)) {
            return WikiLink($author);
        } else {
            return $author;
        }
    }
}

class _PageList_Column_creator extends _PageList_Column_author
{
    public function _getValue($page_handle, &$revision_handle)
    {
        $author = $page_handle->getCreator();
        if (isWikiWord($author) && $this->dbi->isWikiPage($author)) {
            return WikiLink($author);
        } else {
            return $author;
        }
    }
}

class _PageList_Column_pagename extends _PageList_Column_base
{
    public $_field = 'pagename';

    public function __construct()
    {
        parent::__construct(_("Page Name"));
        global $request;
        $this->dbi = &$request->getDbh();
    }

    public function _getValue($page_handle, &$revision_handle)
    {
        if ($this->dbi->isWikiPage($page_handle->getName())) {
            return WikiLink($page_handle, 'known');
        } else {
            return WikiLink($page_handle, 'unknown');
        }
    }

    public function _getSortableValue($page_handle, &$revision_handle)
    {
        return $page_handle->getName();
    }

    /**
     * Compare two pagenames for sorting.  See _PageList_Column::_compare.
     **/
    public function _compare($colvala, $colvalb)
    {
        return strcmp($colvala, $colvalb);
    }
}

class PageList
{
    public $_group_rows = 3;
    public $_columns = array();
    public $_columnsMap = array();      // Maps column name to column number.
    public $_excluded_pages = array();
    public $_pages = array();
    public $_caption = "";
    public $_pagename_seen = false;
    public $_types = array();
    public $_options = array();
    public $_selected = array();
    public $_sortby = array();
    public $_maxlen = 0;

    public function __construct($columns = false, $exclude = false, $options = false)
    {
        if ($options) {
            $this->_options = $options;
        }

        // let plugins predefine only certain objects, such its own custom pagelist columns
        if (!empty($this->_options['types'])) {
            $this->_types = $this->_options['types'];
            unset($this->_options['types']);
        }
        $this->_initAvailableColumns();
        $symbolic_columns =
            array(
                  'all' =>  array_diff(
                      array_keys($this->_types), // all but...
                      array('checkbox','remove','renamed_pagename',
                      'content',
                      'hi_content',
                      'perm',
                      'acl')
                  ),
                  'most' => array('pagename','mtime','author','hits'),
                  'some' => array('pagename','mtime','author')
                  );
        if ($columns) {
            if (!is_array($columns)) {
                $columns = explode(',', $columns);
            }
            // expand symbolic columns:
            foreach ($symbolic_columns as $symbol => $cols) {
                if (in_array($symbol, $columns)) { // e.g. 'checkbox,all'
                    $columns = array_diff(array_merge($columns, $cols), array($symbol));
                }
            }
            if (!in_array('pagename', $columns)) {
                $this->_addColumn('pagename');
            }
            foreach ($columns as $col) {
                $this->_addColumn($col);
            }
        }
        // If 'pagename' is already present, _addColumn() will not add it again
        $this->_addColumn('pagename');

        foreach (array('sortby','limit','paging','count','dosort') as $key) {
            if (!empty($options) and !empty($options[$key])) {
                $this->_options[$key] = $options[$key];
            } else {
                $this->_options[$key] = $GLOBALS['request']->getArg($key);
            }
        }
        $this->_options['sortby'] = $this->sortby($this->_options['sortby'], 'init');
        if ($exclude) {
            if (is_string($exclude) and !is_array($exclude)) {
                $exclude = $this->explodePageList(
                    $exclude,
                    false,
                    $this->_options['sortby'],
                    $this->_options['limit']
                );
            }
            $this->_excluded_pages = $exclude;
        }
        $this->_messageIfEmpty = _("<no matches>");
    }

    // Currently PageList takes these arguments:
    // 1: info, 2: exclude, 3: hash of options
    // Here we declare which options are supported, so that
    // the calling plugin may simply merge this with its own default arguments
    public function supportedArgs()
    {
        return array(// Currently supported options:
                     /* what columns, what pages */
                     'info'     => 'pagename',
                     'exclude'  => '',          // also wildcards, comma-seperated lists
                                     // and <!plugin-list !> arrays
                     /* select pages by meta-data: */
                     'author'   => false, // current user by []
                     'owner'    => false, // current user by []
                     'creator'  => false, // current user by []

                     /* for the sort buttons in <th> */
                     'sortby'   => '', // same as for WikiDB::getAllPages
                                    // (unsorted is faster)

                     /* PageList pager options:
                      * These options may also be given to _generate(List|Table) later
                      * But limit and offset might help the query WikiDB::getAllPages()
                      */
                     'limit'    => 0,       // number of rows (pagesize)
                     'paging'   => 'auto',  // 'auto'   top + bottom rows if applicable
                     //                // 'top'    top only if applicable
                     //                // 'bottom' bottom only if applicable
                     //                     // 'none'   don't page at all
                     // (TODO: clarify what if $paging==false ?)

                     /* list-style options (with single pagename column only so far) */
                     'cols'     => 1,       // side-by-side display of list (1-3)
                     'azhead'   => 0,       // 1: group by initials
                                            // 2: provide shortcut links to initials also
                     'comma'    => 0,       // condensed comma-seperated list,
                                     // 1 if without links, 2 if with
                     'commasep' => false,   // Default: ', '
                     'ordered'  => false,   // OL or just UL lists (ignored for comma)
                     );
    }

    public function setCaption($caption_string)
    {
        $this->_caption = $caption_string;
    }

    public function getCaption()
    {
        // put the total into the caption if needed
        if (is_string($this->_caption) && strstr($this->_caption, '%d')) {
            return sprintf($this->_caption, $this->getTotal());
        }
        return $this->_caption;
    }

    public function setMessageIfEmpty($msg)
    {
        $this->_messageIfEmpty = $msg;
    }


    public function getTotal()
    {
        return !empty($this->_options['count'])
               ? (int) $this->_options['count'] : count($this->_pages);
    }

    public function isEmpty()
    {
        return empty($this->_pages);
    }

    public function addPage($page_handle)
    {
        if (!empty($this->_excluded_pages)) {
            if (
                !in_array(
                    (is_string($page_handle) ? $page_handle : $page_handle->getName()),
                    $this->_excluded_pages
                )
            ) {
                $this->_pages[] = $page_handle;
            }
        } else {
            $this->_pages[] = $page_handle;
        }
    }

    public function pageNames()
    {
        $pages = array();
        foreach ($this->_pages as $page_handle) {
            $pages[] = $page_handle->getName();
        }
        return $pages;
    }

    public function _getPageFromHandle($page_handle)
    {
        if (is_string($page_handle)) {
            if (empty($page_handle)) {
                return $page_handle;
            }
            //$dbi = $GLOBALS['request']->getDbh(); // no, safe memory!
            $page_handle = $GLOBALS['request']->_dbi->getPage($page_handle);
        }
        return $page_handle;
    }

    /**
     * Take a PageList_Page object, and return an HTML object to display
     * it in a table or list row.
     */
    public function _renderPageRow(&$page_handle, $i = 0)
    {
        $page_handle = $this->_getPageFromHandle($page_handle);
        //FIXME. only on sf.net
        if (!is_object($page_handle)) {
            trigger_error("PageList: Invalid page_handle $page_handle", E_USER_WARNING);
            return;
        }
        if (
            !isset($page_handle)
            or empty($page_handle)
            or (!empty($this->_excluded_pages)
                and in_array($page_handle->getName(), $this->_excluded_pages))
        ) {
            return; // exclude page.
        }

        // enforce view permission
        if (!mayAccessPage('view', $page_handle->getName())) {
            return;
        }

        $group = (int) ($i / $this->_group_rows);
        $class = ($group % 2) ? 'oddrow' : 'evenrow';
        $revision_handle = false;
        $this->_maxlen = max($this->_maxlen, strlen($page_handle->getName()));

        if (count($this->_columns) > 1) {
            $row = HTML::tr(array('class' => $class));
            foreach ($this->_columns as $col) {
                $row->pushContent($col->format($this, $page_handle, $revision_handle));
            }
        } else {
            $col = $this->_columns[0];
            $row = $col->_getValue($page_handle, $revision_handle);
        }

        return $row;
    }

    public function addPages($page_iter)
    {
        //Todo: if limit check max(strlen(pagename))
        while ($page = $page_iter->next()) {
            $this->addPage($page);
        }
    }

    public function addPageList(&$list)
    {
        if (empty($list)) {
            return;  // Protect reset from a null arg
        }
        foreach ($list as $page) {
            if (is_object($page)) {
                $page = $page->_pagename;
            }
            $this->addPage((string) $page);
        }
    }

    public function maxLen()
    {
        global $request;
        $dbi = $request->getDbh();
        if (isa($dbi, 'WikiDB_SQL')) {
            extract($dbi->_backend->_table_names);
            $res = $dbi->_backend->_dbh->getOne("SELECT max(length(pagename)) FROM $page_tbl");
            if (DB::isError($res) || empty($res)) {
                return false;
            } else {
                return $res;
            }
        } elseif (isa($dbi, 'WikiDB_ADODB')) {
            extract($dbi->_backend->_table_names);
            $row = $dbi->_backend->_dbh->getRow("SELECT max(length(pagename)) FROM $page_tbl");
            return $row ? $row[0] : false;
        } else {
            return false;
        }
    }

    public function getContent()
    {
        // Note that the <caption> element wants inline content.
        $caption = $this->getCaption();

        if ($this->isEmpty()) {
            return $this->_emptyList($caption);
        } elseif (count($this->_columns) == 1) {
            return $this->_generateList($caption);
        } else {
            return $this->_generateTable($caption);
        }
    }

    public function printXML()
    {
        PrintXML($this->getContent());
    }

    public function asXML()
    {
        return AsXML($this->getContent());
    }

    /**
     * Handle sortby requests for the DB iterator and table header links.
     * Prefix the column with + or - like "+pagename","-mtime", ...
     *
     * Supported actions:
     *   'init'       :   unify with predefined order. "pagename" => "+pagename"
     *   'flip_order' :   "mtime" => "+mtime" => "-mtime" ...
     *   'db'         :   "-pagename" => "pagename DESC"
     *   'check'      :
     *
     * Now all columns are sortable. (patch by DanFr)
     * Some columns have native DB backend methods, some not.
     */
    public function sortby($column, $action, $valid_fields = false)
    {
        global $request;

        if (empty($column)) {
            return '';
        }
        if (is_int($column)) {
            $column = $this->_columns[$column - 1]->_field;
            //$column = $col->_field;
        }
        //if (!is_string($column)) return '';
        // support multiple comma-delimited sortby args: "+hits,+pagename"
        // recursive concat
        if (strstr($column, ',')) {
            $result = ($action == 'check') ? true : array();
            foreach (explode(',', $column) as $col) {
                if ($action == 'check') {
                    $result = $result && $this->sortby($col, $action, $valid_fields);
                } else {
                    $result[] = $this->sortby($col, $action, $valid_fields);
                }
            }
            // 'check' returns true/false for every col. return true if all are true.
            // i.e. the unsupported 'every' operator in functional languages.
            if ($action == 'check') {
                return $result;
            } else {
                return join(",", $result);
            }
        }
        if (substr($column, 0, 1) == '+') {
            $order = '+';
            $column = substr($column, 1);
        } elseif (substr($column, 0, 1) == '-') {
            $order = '-';
            $column = substr($column, 1);
        }
        // default initial order: +pagename, -mtime, -hits
        if (empty($order)) {
            if (in_array($column, array('mtime','hits'))) {
                $order = '-';
            } else {
                $order = '+';
            }
        }
        if ($action == 'flip_order') {
            return ($order == '+' ? '-' : '+') . $column;
        } elseif ($action == 'init') {
            $this->_sortby[$column] = $order;
            return $order . $column;
        } elseif ($action == 'check') {
            return (!empty($this->_sortby[$column])
                    or ($request->getArg('sortby')
                        and strstr($request->getArg('sortby'), $column)));
        } elseif ($action == 'db') {
            // Performance enhancement: use native DB sort if possible.
            if (
                ($valid_fields and in_array($column, $valid_fields))
                or (method_exists($request->_dbi->_backend, 'sortable_columns')
                    and (in_array($column, $request->_dbi->_backend->sortable_columns())))
            ) {
                // omit this sort method from the _sortPages call at rendering
                // asc or desc: +pagename, -pagename
                return $column . ($order == '+' ? ' ASC' : ' DESC');
            } else {
                return '';
            }
        }
        return '';
    }

    // echo implode(":",explodeList("Test*",array("xx","Test1","Test2")));
    public function explodePageList(
        $input,
        $include_empty = false,
        $sortby = false,
        $limit = false,
        $exclude = false
    ) {
        if (empty($input)) {
            return array();
        }
        // expand wildcards from list of all pages
        if (preg_match('/[\?\*]/', $input)) {
            include_once("lib/TextSearchQuery.php");
            $search = new TextSearchQuery(str_replace(",", " ", $input), true, 'glob');
            $dbi = $GLOBALS['request']->getDbh();
            $iter = $dbi->titleSearch($search, $sortby, $limit, $exclude);
            $pages = array();
            while ($pagehandle = $iter->next()) {
                $pages[] = $pagehandle->getName();
            }
            return $pages;
            /*
            //TODO: need an SQL optimization here
            $allPagehandles = $dbi->getAllPages($include_empty, $sortby, $limit,
                                                $exclude);
            while ($pagehandle = $allPagehandles->next()) {
                $allPages[] = $pagehandle->getName();
            }
            return explodeList($input, $allPages);
            */
        } else {
            //TODO: do the sorting, normally not needed if used for exclude only
            return explode(',', $input);
        }
    }

    public function allPagesByAuthor(
        $wildcard,
        $include_empty = false,
        $sortby = false,
        $limit = false,
        $exclude = false
    ) {
        $dbi = $GLOBALS['request']->getDbh();
        $allPagehandles = $dbi->getAllPages($include_empty, $sortby, $limit, $exclude);
        $allPages = array();
        if ($wildcard === '[]') {
            $wildcard = $GLOBALS['request']->_user->getAuthenticatedId();
            if (!$wildcard) {
                return $allPages;
            }
        }
        $do_glob = preg_match('/[\?\*]/', $wildcard);
        while ($pagehandle = $allPagehandles->next()) {
            $name = $pagehandle->getName();
            $author = $pagehandle->getAuthor();
            if ($author) {
                if ($do_glob) {
                    if (glob_match($wildcard, $author)) {
                        $allPages[] = $name;
                    }
                } elseif ($wildcard == $author) {
                      $allPages[] = $name;
                }
            }
            // TODO: purge versiondata_cache
        }
        return $allPages;
    }

    public function allPagesByOwner(
        $wildcard,
        $include_empty = false,
        $sortby = false,
        $limit = false,
        $exclude = false
    ) {
        $dbi = $GLOBALS['request']->getDbh();
        $allPagehandles = $dbi->getAllPages($include_empty, $sortby, $limit, $exclude);
        $allPages = array();
        if ($wildcard === '[]') {
            $wildcard = $GLOBALS['request']->_user->getAuthenticatedId();
            if (!$wildcard) {
                return $allPages;
            }
        }
        $do_glob = preg_match('/[\?\*]/', $wildcard);
        while ($pagehandle = $allPagehandles->next()) {
            $name = $pagehandle->getName();
            $owner = $pagehandle->getOwner();
            if ($owner) {
                if ($do_glob) {
                    if (glob_match($wildcard, $owner)) {
                        $allPages[] = $name;
                    }
                } elseif ($wildcard == $owner) {
                      $allPages[] = $name;
                }
            }
        }
        return $allPages;
    }

    public function allPagesByCreator(
        $wildcard,
        $include_empty = false,
        $sortby = false,
        $limit = false,
        $exclude = false
    ) {
        $dbi = $GLOBALS['request']->getDbh();
        $allPagehandles = $dbi->getAllPages($include_empty, $sortby, $limit, $exclude);
        $allPages = array();
        if ($wildcard === '[]') {
            $wildcard = $GLOBALS['request']->_user->getAuthenticatedId();
            if (!$wildcard) {
                return $allPages;
            }
        }
        $do_glob = preg_match('/[\?\*]/', $wildcard);
        while ($pagehandle = $allPagehandles->next()) {
            $name = $pagehandle->getName();
            $creator = $pagehandle->getCreator();
            if ($creator) {
                if ($do_glob) {
                    if (glob_match($wildcard, $creator)) {
                        $allPages[] = $name;
                    }
                } elseif ($wildcard == $creator) {
                      $allPages[] = $name;
                }
            }
        }
        return $allPages;
    }

    ////////////////////
    // private
    ////////////////////
    /** Plugin and theme hooks:
     *  If the pageList is initialized with $options['types'] these types are also initialized,
     *  overriding the standard types.
     */
    public function _initAvailableColumns()
    {
        global $customPageListColumns;
        $standard_types =
            array(
                  'content'
                  => new _PageList_Column_content('rev:content', _("Content")),
                  // new: plugin specific column types initialised by the relevant plugins
                  /*
                  'hi_content' // with highlighted search for SearchReplace
                  => new _PageList_Column_content('rev:hi_content', _("Content")),
                  'remove'
                  => new _PageList_Column_remove('remove', _("Remove")),
                  // initialised by the plugin
                  'renamed_pagename'
                  => new _PageList_Column_renamed_pagename('rename', _("Rename to")),
                  'perm'
                  => new _PageList_Column_perm('perm', _("Permission")),
                  'acl'
                  => new _PageList_Column_acl('acl', _("ACL")),
                  */
                  'checkbox'
                  => new _PageList_Column_checkbox('p', _("Select")),
                  'pagename'
                  => new _PageList_Column_pagename(),
                  'mtime'
                  => new _PageList_Column_time('rev:mtime', _("Last Modified")),
                  'hits'
                  => new _PageList_Column('hits', _("Hits"), 'right'),
                  'size'
                  => new _PageList_Column_size('rev:size', _("Size"), 'right'),
                                              /*array('align' => 'char', 'char' => ' ')*/
                  'summary'
                  => new _PageList_Column('rev:summary', _("Last Summary")),
                  'version'
                  => new _PageList_Column_version(
                      'rev:version',
                      _("Version"),
                      'right'
                  ),
                  'author'
                  => new _PageList_Column_author('rev:author', _("Last Author")),
                  'owner'
                  => new _PageList_Column_owner('author_id', _("Owner")),
                  'creator'
                  => new _PageList_Column_creator('author_id', _("Creator")),
                  /*
                  'group'
                  => new _PageList_Column_author('group', _("Group")),
                  */
                  'locked'
                  => new _PageList_Column_bool(
                      'locked',
                      _("Locked"),
                      _("locked")
                  ),
                  'minor'
                  => new _PageList_Column_bool(
                      'rev:is_minor_edit',
                      _("Minor Edit"),
                      _("minor")
                  ),
                  'markup'
                  => new _PageList_Column('rev:markup', _("Markup")),
                  // 'rating' initialised by the wikilens theme hook: addPageListColumn
                  /*
                  'rating'
                  => new _PageList_Column_rating('rating', _("Rate")),
                  */
                  );
        if (empty($this->_types)) {
            $this->_types = array();
        }
        // add plugin specific pageList columns, initialized by $options['types']
        $this->_types = array_merge($standard_types, $this->_types);
        // add theme custom specific pageList columns:
        //   set the 4th param as the current pagelist object.
        if (!empty($customPageListColumns)) {
            foreach ($customPageListColumns as $column => $params) {
                $class_name = array_shift($params);
                $params[3] = $this;
                $class = new $class_name($params);
                $this->_types[$column] = $class;
            }
        }
    }

    public function getOption($option)
    {
        if (array_key_exists($option, $this->_options)) {
            return $this->_options[$option];
        } else {
            return null;
        }
    }

    /**
     * Add a column to this PageList, given a column name.
     * The name is a type, and optionally has a : and a label. Examples:
     *
     *   pagename
     *   pagename:This page
     *   mtime
     *   mtime:Last modified
     *
     * If this function is called multiple times for the same type, the
     * column will only be added the first time, and ignored the succeeding times.
     * If you wish to add multiple columns of the same type, use addColumnObject().
     *
     * @param column name
     * @return  true if column is added, false otherwise
     */
    public function _addColumn($column)
    {
        if (isset($this->_columns_seen[$column])) {
            return false;       // Already have this one.
        }
        if (!isset($this->_types[$column])) {
            $this->_initAvailableColumns();
        }
        $this->_columns_seen[$column] = true;

        if (strstr($column, ':')) {
            list ($column, $heading) = explode(':', $column, 2);
        }

        // FIXME: these column types have hooks (objects) elsewhere
        // Omitting this warning should be overridable by the extension
        if (!isset($this->_types[$column])) {
            $silently_ignore = array('numbacklinks',
                                     'rating',/*'ratingwidget',*/
                                     'coagreement', 'minmisery',
                                     /*'prediction',*/
                                     'averagerating', 'top3recs');
            if (!in_array($column, $silently_ignore)) {
                trigger_error(sprintf("%s: Bad column", $column), E_USER_NOTICE);
            }
            return false;
        }
        // FIXME: anon users might rate and see ratings also.
        // Defer this logic to the plugin.
        if ($column == 'rating' and !$GLOBALS['request']->_user->isSignedIn()) {
            return false;
        }

        $this->addColumnObject($this->_types[$column]);

        return true;
    }

    /**
     * Add a column to this PageList, given a column object.
     *
     * @param $col object   An object derived from _PageList_Column.
     **/
    public function addColumnObject($col)
    {
        if (is_array($col)) {// custom column object
            $params = $col;
            $class_name = array_shift($params);
            $params[3] = $this;
            $col = new $class_name($params);
        }
        $heading = $col->getHeading();
        if (!empty($heading)) {
            $col->setHeading($heading);
        }

        $this->_columns[] = $col;
        $this->_columnsMap[$col->_field] = count($this->_columns); // start with 1
    }

    /**
     * Compare _PageList_Page objects.
     **/
    public function _pageCompare(&$a, &$b)
    {
        if (empty($this->_sortby) or count($this->_sortby) == 0) {
            // No columns to sort by
            return 0;
        } else {
            $pagea = $this->_getPageFromHandle($a);  // If a string, convert to page
            assert(isa($pagea, 'WikiDB_Page'));
            $pageb = $this->_getPageFromHandle($b);  // If a string, convert to page
            assert(isa($pageb, 'WikiDB_Page'));
            foreach ($this->_sortby as $colNum => $direction) {
                if (!is_int($colNum)) { // or column fieldname
                    $colNum = $this->_columnsMap[$colNum];
                }
                $col = $this->_columns[$colNum - 1];

                assert(isset($col));
                $revision_handle = false;
                $aval = $col->_getSortableValue($pagea, $revision_handle);
                $bval = $col->_getSortableValue($pageb, $revision_handle);

                $cmp = $col->_compare($aval, $bval);
                if ($direction === "-") {  // Reverse the sense of the comparison
                    $cmp *= -1;
                }

                if ($cmp !== 0) {
                    // This is the first comparison that is not equal-- go with it
                    return $cmp;
                }
            }
            return 0;
        }
    }

    /**
     * Put pages in order according to the sortby arg, if given
     * If the sortby cols are already sorted by the DB call, don't do usort.
     * TODO: optimize for multiple sortable cols
     */
    public function _sortPages()
    {
        if (count($this->_sortby) > 0) {
            $need_sort = $this->_options['dosort'];
            foreach ($this->_sortby as $col => $dir) {
                if (! $this->sortby($col, 'db')) {
                    $need_sort = true;
                }
            }
            if ($need_sort) { // There are some columns to sort by
                usort($this->_pages, array($this, '_pageCompare'));
            }
        }
        //unset($GLOBALS['PhpWiki_pagelist']);
    }

    public function limit($limit)
    {
        if (is_array($limit)) {
            return $limit;
        }
        if (strstr($limit, ',')) {
            return preg_split('/,/D', $limit);
        } else {
            return array(0, $limit);
        }
    }

    public function pagingTokens($numrows = false, $ncolumns = false, $limit = false)
    {
        if ($numrows === false) {
            $numrows = $this->getTotal();
        }
        if ($limit === false) {
            $limit = $this->_options['limit'];
        }
        if ($ncolumns === false) {
            $ncolumns = count($this->_columns);
        }

        list($offset, $pagesize) = $this->limit($limit);
        if (
            !$pagesize or
            (!$offset and $numrows <= $pagesize) or
            ($offset + $pagesize < 0)
        ) {
            return false;
        }

        $request = &$GLOBALS['request'];
        $pagename = $request->getArg('pagename');
        $defargs = $request->args;
        if (USE_PATH_INFO) {
            unset($defargs['pagename']);
        }
        if ($defargs['action'] == 'browse') {
            unset($defargs['action']);
        }
        $prev = $defargs;

        $tokens = array();
        $tokens['PREV'] = false;
        $tokens['PREV_LINK'] = "";
        $tokens['COLS'] = count($this->_columns);
        $tokens['COUNT'] = $numrows;
        $tokens['OFFSET'] = $offset;
        $tokens['SIZE'] = $pagesize;
        $tokens['NUMPAGES'] = (int) ($numrows / $pagesize) + 1;
        $tokens['ACTPAGE'] = (int) (($offset + 1) / $pagesize) + 1;
        if ($offset > 0) {
            $prev['limit'] = max(0, $offset - $pagesize) . ",$pagesize";
            $prev['count'] = $numrows;
            $tokens['LIMIT'] = $prev['limit'];
            $tokens['PREV'] = true;
            $tokens['PREV_LINK'] = WikiURL($pagename, $prev);
            $prev['limit'] = "0,$pagesize";
            $tokens['FIRST_LINK'] = WikiURL($pagename, $prev);
        }
        $next = $defargs;
        $tokens['NEXT'] = false;
        $tokens['NEXT_LINK'] = "";
        if ($offset + $pagesize < $numrows) {
            $next['limit'] = min($offset + $pagesize, $numrows - $pagesize) . ",$pagesize";
            $next['count'] = $numrows;
            $tokens['LIMIT'] = $next['limit'];
            $tokens['NEXT'] = true;
            $tokens['NEXT_LINK'] = WikiURL($pagename, $next);
            $next['limit'] = $numrows - $pagesize . ",$pagesize";
            $tokens['LAST_LINK'] = WikiURL($pagename, $next);
        }
        return $tokens;
    }

    // make a table given the caption
    public function _generateTable($caption)
    {
        if (count($this->_sortby) > 0) {
            $this->_sortPages();
        }

        $rows = array();
        $i = 0;
        foreach ($this->_pages as $pagenum => $page) {
            $rows[] = $this->_renderPageRow($page, $i++);
        }

        $table = HTML::table(array('cellpadding' => 0,
                                   'cellspacing' => 1,
                                   'border'      => 0,
                                   'class'       => 'pagelist'));
        if ($caption) {
            $table->pushContent(HTML::caption(array('align' => 'top'), $caption));
        }

        //Warning: This is quite fragile. It depends solely on a private variable
        //         in ->_addColumn()
        if (!empty($this->_columns_seen['checkbox'])) {
            $table->pushContent($this->_jsFlipAll());
        }
        $do_paging = ( isset($this->_options['paging'])
                   and !empty($this->_options['limit'])
                   and $this->getTotal()
                   and $this->_options['paging'] != 'none' );
        $row = HTML::tr();
        $table_summary = array();
        $i = 1; // start with 1!
        foreach ($this->_columns as $col) {
            $heading = $col->button_heading($this, $i);
            if (
                $do_paging
                 and isset($col->_field)
                 and $col->_field == 'pagename'
                 and ($maxlen = $this->maxLen())
            ) {
                $heading->setAttr('width', $maxlen * 7);
            }
            $row->pushContent($heading);
            if (is_string($col->getHeading())) {
                $table_summary[] = $col->getHeading();
            }
            $i++;
        }
        // Table summary for non-visual browsers.
        $table->setAttr('summary', sprintf(
            _("Columns: %s."),
            join(", ", $table_summary)
        ));
        $table->pushContent(HTML::colgroup(array('span' => count($this->_columns))));
        if ($do_paging) {
            $tokens = $this->pagingTokens(
                $this->getTotal(),
                count($this->_columns),
                $this->_options['limit']
            );
            if ($tokens === false) {
                $table->pushContent(
                    HTML::thead($row),
                    HTML::tbody(false, $rows)
                );
                return $table;
            }

            $paging = Template("pagelink", $tokens);
            if ($this->_options['paging'] != 'bottom') {
                $table->pushContent(HTML::thead($paging));
            }
            $table->pushContent(HTML::tbody(false, HTML($row, $rows)));
            if ($this->_options['paging'] != 'top') {
                $table->pushContent(HTML::tfoot($paging));
            }
            return $table;
        } else {
            $table->pushContent(
                HTML::thead($row),
                HTML::tbody(false, $rows)
            );
            return $table;
        }
    }

    public function _jsFlipAll()
    {
        return JavaScript("
function flipAll(formObj) {
  var isFirstSet = -1;
  for (var i=0; i < formObj.length; i++) {
      fldObj = formObj.elements[i];
      if ((fldObj.type == 'checkbox') && (fldObj.name.substring(0,2) == 'p[')) {
         if (isFirstSet == -1)
           isFirstSet = (fldObj.checked) ? true : false;
         fldObj.checked = (isFirstSet) ? false : true;
       }
   }
}");
    }

    /* recursive stack for private sublist options (azhead, cols) */
    public function _saveOptions($opts)
    {
        $stack = array('pages' => $this->_pages);
        foreach ($opts as $k => $v) {
            $stack[$k] = $this->_options[$k];
            $this->_options[$k] = $v;
        }
        if (empty($this->_stack)) {
            $this->_stack = new Stack();
        }
        $this->_stack->push($stack);
    }
    public function _restoreOptions()
    {
        assert($this->_stack);
        $stack = $this->_stack->pop();
        $this->_pages = $stack['pages'];
        unset($stack['pages']);
        foreach ($stack as $k => $v) {
            $this->_options[$k] = $v;
        }
    }

    // 'cols'   - split into several columns
    // 'azhead' - support <h3> grouping into initials
    // 'ordered' - OL or UL list (not yet inherited to all plugins)
    // 'comma'  - condensed comma-list only, 1: no links, >1: with links
    public function _generateList($caption = '')
    {
        if (empty($this->_pages)) {
            return; // stop recursion
        }
        $out = HTML();
        if ($caption) {
            $out->pushContent(HTML::p($caption));
        }

        // need a recursive switch here for the azhead and cols grouping.
        if (!empty($this->_options['cols']) and $this->_options['cols'] > 1) {
            $count = count($this->_pages);
            $length = $count / $this->_options['cols'];
            $width = sprintf("%d", 100 / $this->_options['cols']) . '%';
            $cols = HTML::tr(array('valign' => 'top'));
            for ($i = 0; $i < $count; $i += $length) {
                $this->_saveOptions(array('cols' => 0));
                $this->_pages = array_slice($this->_pages, $i, $length);
                $cols->pushContent(HTML::td(/*array('width' => $width),*/
                    $this->_generateList()
                ));
                $this->_restoreOptions();
            }
            // speed up table rendering by defining colgroups
            $out->pushContent(HTML::table(
                HTML::colgroup(array('span' => $this->_options['cols'],
                                           'width' => $width)),
                $cols
            ));
            return $out;
        }

        // Ignore azhead if not sorted by pagename
        if (
            !empty($this->_options['azhead'])
            and strstr($this->sortby($this->_options['sortby'], 'init'), "pagename")
        ) {
            $cur_h = substr($this->_pages[0]->getName(), 0, 1);
            $out->pushContent(HTML::h3($cur_h));
            // group those pages together with same $h
            $j = 0;
            for ($i = 0; $i < count($this->_pages); $i++) {
                $page = $this->_pages[$i];
                $h = substr($page->getName(), 0, 1);
                if ($h != $cur_h and $i > $j) {
                    $this->_saveOptions(array('cols' => 0, 'azhead' => 0));
                    $this->_pages = array_slice($this->_pages, $j, $i - $j);
                    $out->pushContent($this->_generateList());
                    $this->_restoreOptions();
                    $j = $i;
                    $out->pushContent(HTML::h3($h));
                    $cur_h = $h;
                }
            }
            if ($i > $j) { // flush the rest
                $this->_saveOptions(array('cols' => 0, 'azhead' => 0));
                $this->_pages = array_slice($this->_pages, $j, $i - $j);
                $out->pushContent($this->_generateList());
                $this->_restoreOptions();
            }
            return $out;
        }

        if (!empty($this->_options['comma'])) {
            if ($this->_options['comma'] == 1) {
                $out->pushContent($this->_generateCommaListAsString());
            } else {
                $out->pushContent($this->_generateCommaList($this->_options['comma']));
            }
            return $out;
        }

        $do_paging = ( isset($this->_options['paging'])
                   and !empty($this->_options['limit'])
                   and $this->getTotal()
                   and $this->_options['paging'] != 'none' );
        if ($do_paging) {
            $tokens = $this->pagingTokens(
                $this->getTotal(),
                count($this->_columns),
                $this->_options['limit']
            );
            if ($tokens) {
                $paging = Template("pagelink", $tokens);
                $out->pushContent(HTML::table($paging));
            }
        }
        if (!empty($this->_options['ordered'])) {
            $list = HTML::ol(array('class' => 'pagelist'));
        } else {
            $list = HTML::ul(array('class' => 'pagelist'));
        }
        $i = 0;
        //TODO: currently we ignore limit here and hope tha the backend didn't ignore it. (BackLinks)
        if (!empty($this->_options['limit'])) {
            list($offset, $pagesize) = $this->limit($this->_options['limit']);
        } else {
            $pagesize = 0;
        }
        foreach ($this->_pages as $pagenum => $page) {
            $pagehtml = $this->_renderPageRow($page);
            $group = ($i++ / $this->_group_rows);
            //TODO: here we switch every row, in tables every third.
            //      unification or parametrized?
            $class = ($group % 2) ? 'oddrow' : 'evenrow';
            $list->pushContent(HTML::li(array('class' => $class), $pagehtml));
            if ($pagesize and $i > $pagesize) {
                break;
            }
        }
        $out->pushContent($list);
        if ($do_paging and $tokens) {
            $out->pushContent(HTML::table($paging));
        }
        return $out;
    }

    // comma=1
    // Condense list without a href links: "Page1, Page2, ..."
    // Alternative $seperator = HTML::Raw(' &middot; ')
    public function _generateCommaListAsString()
    {
        if (defined($this->_options['commasep'])) {
            $seperator = $this->_options['commasep'];
        } else {
            $seperator = ', ';
        }
        $pages = array();
        foreach ($this->_pages as $pagenum => $page) {
            if ($s = $this->_renderPageRow($page)) { // some pages are not viewable
                $pages[] = is_string($s) ? $s : $s->asString();
            }
        }
        return HTML(join($seperator, $pages));
    }

    // comma=2
    // Normal WikiLink list.
    // Future: 1 = reserved for plain string (see above)
    //         2 and more => HTML link specialization?
    public function _generateCommaList($style = false)
    {
        if (defined($this->_options['commasep'])) {
            $seperator = HTLM::Raw($this->_options['commasep']);
        } else {
            $seperator = ', ';
        }
        $html = HTML();
        $html->pushContent($this->_renderPageRow($this->_pages[0]));
        next($this->_pages);
        foreach ($this->_pages as $pagenum => $page) {
            if ($s = $this->_renderPageRow($page)) { // some pages are not viewable
                $html->pushContent($seperator, $s);
            }
        }
        return $html;
    }

    public function _emptyList($caption)
    {
        $html = HTML();
        if ($caption) {
            $html->pushContent(HTML::p($caption));
        }
        if ($this->_messageIfEmpty) {
            $html->pushContent(HTML::blockquote(HTML::p($this->_messageIfEmpty)));
        }
        return $html;
    }
}

/* List pages with checkboxes to select from.
 * The [Select] button toggles via _jsFlipAll
 */

class PageList_Selectable extends PageList
{

    public function __construct($columns = false, $exclude = false, $options = false)
    {
        if ($columns) {
            if (!is_array($columns)) {
                $columns = explode(',', $columns);
            }
            if (!in_array('checkbox', $columns)) {
                array_unshift($columns, 'checkbox');
            }
        } else {
            $columns = array('checkbox','pagename');
        }
        parent::__construct($columns, $exclude, $options);
    }

    public function addPageList(&$array)
    {
        foreach ($array as $pagename => $selected) {
            if ($selected) {
                $this->addPageSelected((string) $pagename);
            }
            $this->addPage((string) $pagename);
        }
    }

    public function addPageSelected($pagename)
    {
        $this->_selected[$pagename] = 1;
    }
}

// $Log: PageList.php,v $
// Revision 1.135  2005/09/14 05:59:03  rurban
// optimized explodePageList to use SQL when available
//   (titleSearch instead of getAllPages)
//
// Revision 1.134  2005/09/11 14:55:05  rurban
// implement fulltext stoplist
//
// Revision 1.133  2005/08/27 09:41:37  rurban
// new helper method
//
// Revision 1.132  2005/04/09 09:16:15  rurban
// fix recursive PageList azhead+cols listing
//
// Revision 1.131  2005/02/04 10:48:06  rurban
// fix usort ref warning. Thanks to Charles Corrigan
//
// Revision 1.130  2005/01/28 12:07:36  rurban
// reformatting
//
// Revision 1.129  2005/01/25 06:58:21  rurban
// reformatting
//
// Revision 1.128  2004/12/26 17:31:35  rurban
// fixed prev link logic
//
// Revision 1.127  2004/12/26 17:19:28  rurban
// dont break sideeffecting sortby flips on paging urls (MostPopular)
//
// Revision 1.126  2004/12/16 18:26:57  rurban
// Avoid double calculation
//
// Revision 1.125  2004/11/25 17:20:49  rurban
// and again a couple of more native db args: backlinks
//
// Revision 1.124  2004/11/23 15:17:14  rurban
// better support for case_exact search (not caseexact for consistency),
// plugin args simplification:
//   handle and explode exclude and pages argument in WikiPlugin::getArgs
//     and exclude in advance (at the sql level if possible)
//   handle sortby and limit from request override in WikiPlugin::getArgs
// ListSubpages: renamed pages to maxpages
//
// Revision 1.123  2004/11/23 13:35:31  rurban
// add case_exact search
//
// Revision 1.122  2004/11/21 11:59:15  rurban
// remove final \n to be ob_cache independent
//
// Revision 1.121  2004/11/20 17:35:47  rurban
// improved WantedPages SQL backends
// PageList::sortby new 3rd arg valid_fields (override db fields)
// WantedPages sql pager inexact for performance reasons:
//   assume 3 wantedfrom per page, to be correct, no getTotal()
// support exclude argument for get_all_pages, new _sql_set()
//
// Revision 1.120  2004/11/20 11:28:49  rurban
// fix a yet unused PageList customPageListColumns bug (merge class not decl to _types)
// change WantedPages to use PageList
// change WantedPages to print the list of referenced pages, not just the count.
//   the old version was renamed to WantedPagesOld
//   fix and add handling of most standard PageList arguments (limit, exclude, ...)
// TODO: pagename sorting, dumb/WantedPagesIter and SQL optimization
//
// Revision 1.119  2004/11/11 14:34:11  rurban
// minor clarifications
//
// Revision 1.118  2004/11/01 10:43:55  rurban
// seperate PassUser methods into seperate dir (memory usage)
// fix WikiUser (old) overlarge data session
// remove wikidb arg from various page class methods, use global ->_dbi instead
// ...
//
// Revision 1.117  2004/10/14 21:06:01  rurban
// fix dumphtml with USE_PATH_INFO (again). fix some PageList refs
//
// Revision 1.116  2004/10/14 19:19:33  rurban
// loadsave: check if the dumped file will be accessible from outside.
// and some other minor fixes. (cvsclient native not yet ready)
//
// Revision 1.115  2004/10/14 17:15:05  rurban
// remove class _PageList_Page, fix sortby=0 (start with 1, use strings), fix _PageList_Column_content for old phps, hits as int
//
// Revision 1.114  2004/10/12 13:13:19  rurban
// php5 compatibility (5.0.1 ok)
//
// Revision 1.113  2004/10/05 17:00:03  rurban
// support paging for simple lists
// fix RatingDb sql backend.
// remove pages from AllPages (this is ListPages then)
//
// Revision 1.112  2004/10/04 23:39:58  rurban
// list of page objects
//
// Revision 1.111  2004/09/24 18:50:45  rurban
// fix paging of SqlResult
//
// Revision 1.110  2004/09/17 14:43:31  rurban
// typo
//
// Revision 1.109  2004/09/17 14:22:10  rurban
// update comments
//
// Revision 1.108  2004/09/17 12:46:22  rurban
// seperate pagingTokens()
// support new default args: comma (1 and 2), commasep, ordered, cols,
//                           azhead (1 only)
//
// Revision 1.107  2004/09/14 10:29:08  rurban
// exclude pages already in addPages to simplify plugins
//
// Revision 1.106  2004/09/06 10:22:14  rurban
// oops, forgot global request
//
// Revision 1.105  2004/09/06 08:38:30  rurban
// modularize paging helper (for SqlResult)
//
// Revision 1.104  2004/08/18 11:01:55  rurban
// fixed checkbox list Select button:
//   no GET request on click,
//   only select the list checkbox entries, no other options.
//
// Revision 1.103  2004/07/09 10:06:49  rurban
// Use backend specific sortby and sortable_columns method, to be able to
// select between native (Db backend) and custom (PageList) sorting.
// Fixed PageList::AddPageList (missed the first)
// Added the author/creator.. name to AllPagesBy...
//   display no pages if none matched.
// Improved dba and file sortby().
// Use &$request reference
//
// Revision 1.102  2004/07/08 21:32:35  rurban
// Prevent from more warnings, minor db and sort optimizations
//
// Revision 1.101  2004/07/08 19:04:41  rurban
// more unittest fixes (file backend, metadata RatingsDb)
//
// Revision 1.100  2004/07/07 15:02:26  dfrankow
// Take out if that prevents column sorting
//
// Revision 1.99  2004/07/02 18:49:02  dfrankow
// Change one line so that if addPageList() is passed null, it is still
// okay.  The unit tests do this (ask to list AllUsers where there are no
// users, or something like that).
//
// Revision 1.98  2004/07/01 08:51:22  rurban
// dumphtml: added exclude, print pagename before processing
//
// Revision 1.97  2004/06/29 09:11:10  rurban
// More memory optimization:
//   don't cache unneeded _cached_html and %content for content and size columns
//   (only if sortable, which will fail for too many pages)
//
// Revision 1.96  2004/06/29 08:47:42  rurban
// Memory optimization (reference to parent, smart bool %content)
// Fixed class grouping in table
//
// Revision 1.95  2004/06/28 19:00:01  rurban
// removed non-portable LIMIT 1 (it's getOne anyway)
// removed size from info=most: needs to much memory
//
// Revision 1.94  2004/06/27 10:26:02  rurban
// oci8 patch by Philippe Vanhaesendonck + some ADODB notes+fixes
//
// Revision 1.93  2004/06/25 14:29:17  rurban
// WikiGroup refactoring:
//   global group attached to user, code for not_current user.
//   improved helpers for special groups (avoid double invocations)
// new experimental config option ENABLE_XHTML_XML (fails with IE, and document.write())
// fixed a XHTML validation error on userprefs.tmpl
//
// Revision 1.92  2004/06/21 17:01:39  rurban
// fix typo and rating method call
//
// Revision 1.91  2004/06/21 16:22:29  rurban
// add DEFAULT_DUMP_DIR and HTML_DUMP_DIR constants, for easier cmdline dumps,
// fixed dumping buttons locally (images/buttons/),
// support pages arg for dumphtml,
// optional directory arg for dumpserial + dumphtml,
// fix a AllPages warning,
// show dump warnings/errors on DEBUG,
// don't warn just ignore on wikilens pagelist columns, if not loaded.
// RateIt pagelist column is called "rating", not "ratingwidget" (Dan?)
//
// Revision 1.90  2004/06/18 14:38:21  rurban
// adopt new PageList style
//
// Revision 1.89  2004/06/17 13:16:08  rurban
// apply wikilens work to PageList: all columns are sortable (slightly fixed)
//
// Revision 1.88  2004/06/14 11:31:35  rurban
// renamed global $Theme to $WikiTheme (gforge nameclash)
// inherit PageList default options from PageList
//   default sortby=pagename
// use options in PageList_Selectable (limit, sortby, ...)
// added action revert, with button at action=diff
// added option regex to WikiAdminSearchReplace
//
// Revision 1.87  2004/06/13 16:02:12  rurban
// empty list of pages if user=[] and not authenticated.
//
// Revision 1.86  2004/06/13 15:51:37  rurban
// Support pagelist filter for current author,owner,creator by []
//
// Revision 1.85  2004/06/13 15:33:19  rurban
// new support for arguments owner, author, creator in most relevant
// PageList plugins. in WikiAdmin* via preSelectS()
//
// Revision 1.84  2004/06/08 13:51:56  rurban
// some comments only
//
// Revision 1.83  2004/05/18 13:35:39  rurban
//  improve Pagelist layout by equal pagename width for limited lists
//
// Revision 1.82  2004/05/16 22:07:35  rurban
// check more config-default and predefined constants
// various PagePerm fixes:
//   fix default PagePerms, esp. edit and view for Bogo and Password users
//   implemented Creator and Owner
//   BOGOUSERS renamed to BOGOUSER
// fixed syntax errors in signin.tmpl
//
// Revision 1.81  2004/05/13 12:30:35  rurban
// fix for MacOSX border CSS attr, and if sort buttons are not found
//
// Revision 1.80  2004/04/20 00:56:00  rurban
// more paging support and paging fix for shorter lists
//
// Revision 1.79  2004/04/20 00:34:16  rurban
// more paging support
//
// Revision 1.78  2004/04/20 00:06:03  rurban
// themable paging support
//
// Revision 1.77  2004/04/18 01:11:51  rurban
// more numeric pagename fixes.
// fixed action=upload with merge conflict warnings.
// charset changed from constant to global (dynamic utf-8 switching)
// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
