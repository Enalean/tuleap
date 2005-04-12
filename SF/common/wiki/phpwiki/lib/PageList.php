<?php rcs_id('$Id$');

/**
 * List a number of pagenames, optionally as table with various columns.
 * This library relieves some work for these plugins:
 *
 * AllPages, BackLinks, LikePages, Mostpopular, TitleSearch and more
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
 * 'owner'    _("Owner"),  //todo: implement this again for PagePerm
 * 'group'    _("Group"),  //todo: implement this for PagePerm
 * 'checkbox'  A selectable checkbox appears at the left.
 *             Todo: move this admin action away, not really an info column
 * 'content'  
 *
 * Special, custom columns: Either theme or plugin specific.
 * 'remove'   _("Remove")     
 * 'perm'     _("Permission Mask")
 * 'acl'      _("ACL")
 * 'renamed_pagename'   _("Rename to")
 * 'rating'   wikilens theme specific.
 * 'custom'   See plugin/WikiTranslation
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
 *   check PagePerm "list" access-type
 *
 * TODO: 
 *   limit, offset, rows arguments for multiple pages/multiple rows.
 *
 *   ->supportedArgs() which arguments are supported, so that the plugin 
 *                     doesn't explictly need to declare it
 *   new method:
 *     list not as <ul> or table, but as simple comma-seperated list
 */
class _PageList_Column_base {
    var $_tdattr = array();

    function _PageList_Column_base ($default_heading, $align = false) {
        $this->_heading = $default_heading;

        if ($align) {
            // align="char" isn't supported by any browsers yet :(
            //if (is_array($align))
            //    $this->_tdattr = $align;
            //else
            $this->_tdattr['align'] = $align;
        }
    }

    function format ($pagelist, $page_handle, &$revision_handle) {
        return HTML::td($this->_tdattr,
                        HTML::raw('&nbsp;'),
                        $this->_getValue($page_handle, $revision_handle),
                        HTML::raw('&nbsp;'));
    }

    function setHeading ($heading) {
        $this->_heading = $heading;
    }

    function heading () {
        // allow sorting?
        if (in_array($this->_field,PageList::sortable_columns())) {
            // multiple comma-delimited sortby args: "+hits,+pagename"
            // asc or desc: +pagename, -pagename
            $sortby = PageList::sortby($this->_field,'flip_order');
            //Fixme: pass all also other GET args along. (limit, p[])
            $s = HTML::a(array('href' => 
                               $GLOBALS['request']->GetURLtoSelf(array('sortby' => $sortby,
                                                                       'nopurge' => '1')),
                               'class' => 'pagetitle',
                               'title' => sprintf(_("Sort by %s"),$this->_field)), 
                         HTML::raw('&nbsp;'), HTML::u($this->_heading), HTML::raw('&nbsp;'));
        } else {
            $s = HTML(HTML::raw('&nbsp;'), HTML::u($this->_heading), HTML::raw('&nbsp;'));
        }
        return HTML::th(array('align' => 'center'),$s);
    }

    // new grid-style
    // see activeui.js 
    function button_heading () {
        global $Theme, $request;
        // allow sorting?
        if (in_array($this->_field,PageList::sortable_columns())) {
            // multiple comma-delimited sortby args: "+hits,+pagename"
            $src = false; 
            $noimg_src = $Theme->getButtonURL('no_order');
            if ($noimg_src)
                $noimg = HTML::img(array('src' => $noimg_src,
                                         'width' => '7', 
                                         'height' => '7',
                                         'border' => 0,
                                         'alt'    => '.'));
            else $noimg = HTML::raw('&nbsp;');
            if ($request->getArg('sortby')) {
                if (PageList::sortby($this->_field,'check')) { // show icon?
                    $sortby = PageList::sortby($request->getArg('sortby'),'flip_order');
                    $request->setArg('sortby',$sortby);
                    $desc = (substr($sortby,0,1) == '-');      // asc or desc? (+pagename, -pagename)
                    $src = $Theme->getButtonURL($desc ? 'asc_order' : 'desc_order');
                } else {
                    $sortby = PageList::sortby($this->_field,'init');
                }
            } else {
                $sortby = PageList::sortby($this->_field,'init');
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
            $s = HTML::a(array('href' => 
                               //Fixme: pass all also other GET args along. (limit, p[])
                               //Fixme: convert to POST submit[sortby]
                               $request->GetURLtoSelf(array('sortby' => $sortby,
                                                            'nopurge' => '1')),
                               'class' => 'gridbutton', 
                               'title' => sprintf(_("Click to sort by %s"),$this->_field)),
                         HTML::raw('&nbsp;'),
                         $noimg,
                         HTML::raw('&nbsp;'),
                         $this->_heading,
                         HTML::raw('&nbsp;'),
                         $img,
                         HTML::raw('&nbsp;'));
        } else {
            $s = HTML(HTML::raw('&nbsp;'), $this->_heading, HTML::raw('&nbsp;'));
        }
        return HTML::th(array('align' => 'center', 'valign' => 'middle', 
                              'class' => 'gridbutton'), $s);
    }
};

class _PageList_Column extends _PageList_Column_base {
    function _PageList_Column ($field, $default_heading, $align = false) {
        $this->_PageList_Column_base($default_heading, $align);

        $this->_need_rev = substr($field, 0, 4) == 'rev:';
        $this->_iscustom = substr($field, 0, 7) == 'custom:';
        if ($this->_iscustom)
            $this->_field = substr($field, 7);
        elseif ($this->_need_rev)
            $this->_field = substr($field, 4);
        else
            $this->_field = $field;
    }

    function _getValue ($page_handle, &$revision_handle) {
        if ($this->_need_rev) {
            if (!$revision_handle)
                $revision_handle = $page_handle->getCurrentRevision();
            return $revision_handle->get($this->_field);
        }
        else {
            return $page_handle->get($this->_field);
        }
    }
};

class _PageList_Column_size extends _PageList_Column {
    function _getValue ($page_handle, &$revision_handle) {
        if (!$revision_handle)
            $revision_handle = $page_handle->getCurrentRevision();
        return $this->_getSize($revision_handle);
    }

    function _getSize($revision_handle) {
        $bytes = @strlen($revision_handle->_data['%content']);
        return ByteFormatter($bytes);
    }
}


class _PageList_Column_bool extends _PageList_Column {
    function _PageList_Column_bool ($field, $default_heading, $text = 'yes') {
        $this->_PageList_Column($field, $default_heading, 'center');
        $this->_textIfTrue = $text;
        $this->_textIfFalse = new RawXml('&#8212;'); //mdash
    }

    function _getValue ($page_handle, &$revision_handle) {
        $val = _PageList_Column::_getValue($page_handle, $revision_handle);
        return $val ? $this->_textIfTrue : $this->_textIfFalse;
    }
};

class _PageList_Column_checkbox extends _PageList_Column {
    function _PageList_Column_checkbox ($field, $default_heading, $name='p') {
        $this->_name = $name;
        $heading = HTML::input(array('type'  => 'button',
                                     'title' => _("Click to de-/select all pages"),
                                     //'width' => '100%',
                                     'name'  => $default_heading,
                                     'value' => $default_heading,
                                     'onclick' => "flipAll(this.form)"
                                     ));
        $this->_PageList_Column($field, $heading, 'center');
    }
    function _getValue ($pagelist, $page_handle, &$revision_handle) {
        $pagename = $page_handle->getName();
        $selected = !empty($pagelist->_selected[$pagename]);
        if (strstr($pagename,'[') or strstr($pagename,']')) {
            $pagename = str_replace(array('[',']'),array('%5B','%5D'),$pagename);
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
    function format ($pagelist, $page_handle, &$revision_handle) {
        return HTML::td($this->_tdattr,
                        HTML::raw('&nbsp;'),
                        $this->_getValue($pagelist, $page_handle, $revision_handle),
                        HTML::raw('&nbsp;'));
    }
};

class _PageList_Column_time extends _PageList_Column {
    function _PageList_Column_time ($field, $default_heading) {
        $this->_PageList_Column($field, $default_heading, 'right');
        global $Theme;
        $this->Theme = &$Theme;
    }

    function _getValue ($page_handle, &$revision_handle) {
        $time = _PageList_Column::_getValue($page_handle, $revision_handle);
        return $this->Theme->formatDateTime($time);
    }
};

class _PageList_Column_version extends _PageList_Column {
    function _getValue ($page_handle, &$revision_handle) {
        if (!$revision_handle)
            $revision_handle = $page_handle->getCurrentRevision();
        return $revision_handle->getVersion();
    }
};

// Output is hardcoded to limit of first 50 bytes. Otherwise
// on very large Wikis this will fail if used with AllPages
// (PHP memory limit exceeded)
// FIXME: old PHP without superglobals
class _PageList_Column_content extends _PageList_Column {
    function _PageList_Column_content ($field, $default_heading, $align = false) {
        _PageList_Column::_PageList_Column($field, $default_heading, $align);
        $this->bytes = 50;
        if ($field == 'content') {
            $this->_heading .= sprintf(_(" ... first %d bytes"),
                                       $this->bytes);
        } elseif ($field == 'hi_content') {
            if (!empty($_POST['admin_replace'])) {
                $search = $_POST['admin_replace']['from'];
                $this->_heading .= sprintf(_(" ... around %s"),
                                           '»'.$search.'«');
            }
        }
    }
    function _getValue ($page_handle, &$revision_handle) {
        if (!$revision_handle)
            $revision_handle = $page_handle->getCurrentRevision();
        // Not sure why implode is needed here, I thought
        // getContent() already did this, but it seems necessary.
        $c = implode("\n", $revision_handle->getContent());
        if ($this->_field == 'hi_content') {
            $search = $_POST['admin_replace']['from'];
            if ($search and ($i = strpos($c,$search))) {
                $l = strlen($search);
                $j = max(0,$i - ($this->bytes / 2));
                return HTML::div(array('style' => 'font-size:x-small'),
                                 HTML::div(array('class' => 'transclusion'),
                                           HTML::span(substr($c, $j, ($this->bytes / 2))),
                                           HTML::span(array("style"=>"background:yellow"),$search),
                                           HTML::span(substr($c, $i+$l, ($this->bytes / 2))))
                                 );
            } else {
                $c = sprintf(_("%s not found"),
                             '»'.$search.'«');
                return HTML::div(array('style' => 'font-size:x-small','align'=>'center'),
                                 $c);
            }
        } elseif (($len = strlen($c)) > $this->bytes) {
            $c = substr($c, 0, $this->bytes);
        }
        include_once('lib/BlockParser.php');
        // false --> don't bother processing hrefs for embedded WikiLinks
        $ct = TransformText($c, $revision_handle->get('markup'), false);
        return HTML::div(array('style' => 'font-size:x-small'),
                         HTML::div(array('class' => 'transclusion'), $ct),
                         // Don't show bytes here if size column present too
                         ($this->parent->_columns_seen['size'] or !$len) ? "" :
                           ByteFormatter($len, /*$longformat = */true));
    }
};

class _PageList_Column_author extends _PageList_Column {
    function _PageList_Column_author ($field, $default_heading, $align = false) {
        _PageList_Column::_PageList_Column($field, $default_heading, $align);
        global $WikiNameRegexp, $request;
        $this->WikiNameRegexp = $WikiNameRegexp;
        $this->dbi = &$request->getDbh();
    }

    function _getValue ($page_handle, &$revision_handle) {
        $author = _PageList_Column::_getValue($page_handle, $revision_handle);
        if (preg_match("/^$this->WikiNameRegexp\$/", $author) && $this->dbi->isWikiPage($author))
            return WikiLink($author);
        else
            return $author;
    }
};

// DONE: only if RateIt is used
// class _PageList_Column_rating extends _PageList_Column
// moved to theme/wikilens/themeinfo.php

class _PageList_Column_pagename extends _PageList_Column_base {
    var $_field = 'pagename';

    function _PageList_Column_pagename () {
        $this->_PageList_Column_base(_("Page Name"));
        global $request;
        $this->dbi = &$request->getDbh();
    }

    function _getValue ($page_handle, &$revision_handle) {
        if ($this->dbi->isWikiPage($page_handle->getName()))
            return WikiLink($page_handle);
        else
            return WikiLink($page_handle, 'unknown');
    }
};



class PageList {
    var $_group_rows = 3;
    var $_columns = array();
    var $_excluded_pages = array();
    var $_rows = array();
    var $_caption = "";
    var $_pagename_seen = false;
    var $_types = array();
    var $_options = array();
    var $_selected = array();
    var $_sortby = array();

    function PageList ($columns = false, $exclude = false, $options = false) {
        // let plugins predefine only certain objects, such its own custom pagelist columns
        if (!empty($options['types'])) {
            $this->_types = $options['types'];
            unset($options['types']);
        }
        $this->_initAvailableColumns();
        $symbolic_columns = 
            array(
                  'all' =>  array_diff(array_keys($this->_types), // all but...
                                       array('checkbox','remove','renamed_pagename',
                                             'content','hi_content','perm','acl')),
                  'most' => array('pagename','mtime','author','size','hits'),
                  'some' => array('pagename','mtime','author')
                  );
        if ($columns) {
            if (!is_array($columns))
                $columns = explode(',', $columns);
            // expand symbolic columns:
            foreach ($symbolic_columns as $symbol => $cols) {
                if (in_array($symbol,$columns)) { // e.g. 'checkbox,all'
                    $columns = array_diff(array_merge($columns,$cols),array($symbol));
                }
            }
            if (!in_array('pagename',$columns))
                $this->_addColumn('pagename');
            foreach ($columns as $col) {
                $this->_addColumn($col);
            }
        }
        $this->_addColumn('pagename');

        $this->_options = $options;
        foreach (array('sortby','limit','paging','count') as $key) {
          if (!empty($options) and !empty($options[$key])) {
            $this->_options[$key] = $options[$key];
          } else {
            $this->_options[$key] = $GLOBALS['request']->getArg($key);
          }
        }
        $this->_options['sortby'] = $this->sortby($this->_options['sortby'], 'init');
        if ($exclude) {
            if (!is_array($exclude))
                $exclude = $this->explodePageList($exclude,false,
                                                  $this->_options['sortby'],
                                                  $this->_options['limit']);
            $this->_excluded_pages = $exclude;
        }
        $this->_messageIfEmpty = _("<no matches>");
    }

    // Currently PageList takes these arguments:
    // 1: info, 2: exclude, 3: hash of options
    // Here we declare which options are supported, so that 
    // the calling plugin may simply merge this with its own default arguments 
    function supportedArgs () {
        return array(//Currently supported options:
                     'info'              => 'pagename',
                     'exclude'           => '',          // also wildcards and comma-seperated lists

                     // for the sort buttons in <th>
                     'sortby'            => '',   // same as for WikiDB::getAllPages

                     //PageList pager options:
                     // These options may also be given to _generate(List|Table) later
                     // But limit and offset might help the query WikiDB::getAllPages()
                     'cols'     => 1,       // side-by-side display of list (1-3)
                     'limit'    => 50,      // number of rows
                     'paging'   => 'auto',  // 'auto'  normal paging mode
                     //			    // 'smart' drop 'info' columns and enhance rows 
                     //                     //         when the list becomes large
                     //                     // 'none'  don't page at all
                     //'azhead' => 0        // provide shortcut links to pages starting with different letters
                     );
    }

    function setCaption ($caption_string) {
        $this->_caption = $caption_string;
    }

    function getCaption () {
        // put the total into the caption if needed
        if (is_string($this->_caption) && strstr($this->_caption, '%d'))
            return sprintf($this->_caption, $this->getTotal());
        return $this->_caption;
    }

    function setMessageIfEmpty ($msg) {
        $this->_messageIfEmpty = $msg;
    }


    function getTotal () {
    	return !empty($this->_options['count'])
    	       ? $this->_options['count'] : count($this->_rows);
    }

    function isEmpty () {
        return empty($this->_rows);
    }

    function addPage ($page_handle) {
        if (is_string($page_handle)) {
            if ($page_handle == '') return;
	    if (in_array($page_handle, $this->_excluded_pages))
        	return;             // exclude page.
            $dbi = $GLOBALS['request']->getDbh();
            $page_handle = $dbi->getPage($page_handle);
        } elseif (is_object($page_handle)) {
          if (in_array($page_handle->getName(), $this->_excluded_pages))
            return;             // exclude page.
        }
        //FIXME. only on sf.net
        if (!is_object($page_handle)) {
            trigger_error("PageList: Invalid page_handle $page_handle", E_USER_WARNING);
            return;
        }
        // enforce view permission
        if (!mayAccessPage('view',$page_handle->getName()))
            return;

        $group = (int)(count($this->_rows) / $this->_group_rows);
        $class = ($group % 2) ? 'oddrow' : 'evenrow';
        $revision_handle = false;

        if (count($this->_columns) > 1) {
            $row = HTML::tr(array('class' => $class));
            foreach ($this->_columns as $col)
                $row->pushContent($col->format($this, $page_handle, $revision_handle));
        }
        else {
            $col = $this->_columns[0];
            $row = $col->_getValue($page_handle, $revision_handle);
        }

        $this->_rows[] = $row;
    }

    function addPages ($page_iter) {
        while ($page = $page_iter->next())
            $this->addPage($page);
    }

    function addPageList (&$list) {
        reset ($list);
        while ($page = next($list))
            $this->addPage((string)$page);
    }

    function getContent() {
        // Note that the <caption> element wants inline content.
        $caption = $this->getCaption();

        if ($this->isEmpty())
            return $this->_emptyList($caption);
        elseif (count($this->_columns) == 1)
            return $this->_generateList($caption);
        else
            return $this->_generateTable($caption);
    }

    function printXML() {
        PrintXML($this->getContent());
    }

    function asXML() {
        return AsXML($this->getContent());
    }

    function sortable_columns() {
        return array('pagename','mtime','hits');
    }

    /** 
     * Handle sortby requests for the DB iterator and table header links.
     * Prefix the column with + or - like "+pagename","-mtime", ...
     * db supported columns: 'pagename','mtime','hits'
     * supported actions: 'flip_order' "mtime" => "+mtime" => "-mtime" ...
     *                    'db'         "-pagename" => "pagename DESC"
     * which other column types should be sortable?
     */
    function sortby ($column, $action) {
        if (empty($column)) return;
        //support multiple comma-delimited sortby args: "+hits,+pagename"
        if (strstr($column,',')) {
            $result = array();
            foreach (explode(',',$column) as $col) {
                $result[] = $this->sortby($col,$action);
            }
            return join(",",$result);
        }
        if (substr($column,0,1) == '+') {
            $order = '+'; $column = substr($column,1);
        } elseif (substr($column,0,1) == '-') {
            $order = '-'; $column = substr($column,1);
        }
        if (in_array($column,PageList::sortable_columns())) {
            // default order: +pagename, -mtime, -hits
            if (empty($order))
                if (in_array($column,array('mtime','hits')))
                    $order = '-';
                else
                    $order = '+';
            if ($action == 'flip_order') {
                return ($order == '+' ? '-' : '+') . $column;
            } elseif ($action == 'init') {
                $this->_sortby[$column] = $order;
                return $order . $column;
            } elseif ($action == 'check') {
                return (!empty($this->_sortby[$column]) or 
                        ($GLOBALS['request']->getArg('sortby') and 
                         strstr($GLOBALS['request']->getArg('sortby'),$column)));
            } elseif ($action == 'db') {
                // asc or desc: +pagename, -pagename
                return $column . ($order == '+' ? ' ASC' : ' DESC');
            }
        }
        return '';
    }

    // echo implode(":",explodeList("Test*",array("xx","Test1","Test2")));
    function explodePageList($input, $perm = false, $sortby=false, $limit=false) {
        // expand wildcards from list of all pages
        if (preg_match('/[\?\*]/',$input)) {
            $dbi = $GLOBALS['request']->getDbh();
            $allPagehandles = $dbi->getAllPages($perm,$sortby,$limit);
            while ($pagehandle = $allPagehandles->next()) {
                $allPages[] = $pagehandle->getName();
            }
            return explodeList($input, $allPages);
        } else {
            //TODO: do the sorting, normally not needed if used for exclude only
            return explode(',',$input);
        }
    }


    ////////////////////
    // private
    ////////////////////
    /** Plugin and theme hooks: 
     *  If the pageList is initialized with $options['types'] these types are also initialized, 
     *  overriding the standard types.
     */
    function _initAvailableColumns() {
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
                  => new _PageList_Column_pagename,
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
                  => new _PageList_Column_version('rev:version', _("Version"),
                                                 'right'),
                  'author'
                  => new _PageList_Column_author('rev:author', _("Last Author")),
                  'owner'
                  => new _PageList_Column_author('owner', _("Owner")),
                  'group'
                  => new _PageList_Column_author('group', _("Group")),
                  'locked'
                  => new _PageList_Column_bool('locked', _("Locked"),
                                               _("locked")),
                  'minor'
                  => new _PageList_Column_bool('rev:is_minor_edit',
                                               _("Minor Edit"), _("minor")),
                  'markup'
                  => new _PageList_Column('rev:markup', _("Markup")),
                  // 'rating' initialised by the wikilens theme hook: addPageListColumn
                  /*
                  'rating'
                  => new _PageList_Column_rating('rating', _("Rate")),
                  */
                  );
        if (empty($this->_types))
            $this->_types = array();
        // add plugin specific pageList columns, initialized by $options['types']
        $this->_types = array_merge($standard_types, $this->_types);
        // add theme specific pageList columns
        if (!empty($customPageListColumns))
            $this->_types = array_merge($this->_types, $customPageListColumns);
    }

    function _addColumn ($column) {
    	
        if (isset($this->_columns_seen[$column]))
            return false;       // Already have this one.
	if (!isset($this->_types[$column]))
            $this->_initAvailableColumns();
        $this->_columns_seen[$column] = true;

        if (strstr($column, ':'))
            list ($column, $heading) = explode(':', $column, 2);

        if (!isset($this->_types[$column])) {
            //trigger_error(sprintf("%s: Bad column", $column), E_USER_NOTICE);
            return false;
        }
        if ($column == 'rating' and !$GLOBALS['request']->_user->isSignedIn())
            return;

        $col = $this->_types[$column];
        if (!empty($heading))
            $col->setHeading($heading);

        $this->_columns[] = $col;

        return true;
    }

    function limit($limit) {
        if (strstr($limit,','))
            return split(',',$limit);
        else
            return array(0,$limit);
    }
    
    // make a table given the caption
    function _generateTable($caption) {
        $table = HTML::table(array('cellpadding' => 0,
                                   'cellspacing' => 1,
                                   'border'      => 0,
                                   'class'       => 'pagelist'));
        if ($caption)
            $table->pushContent(HTML::caption(array('align'=>'top'), $caption));

        //Warning: This is quite fragile. It depends solely on a private variable
        //         in ->_addColumn()
        if (!empty($this->_columns_seen['checkbox'])) {
            $table->pushContent($this->_jsFlipAll());
        }
        $row = HTML::tr();
        $table_summary = array();
        foreach ($this->_columns as $col) {
            $row->pushContent($col->button_heading());
            if (is_string($col->_heading))
                $table_summary[] = $col->_heading;
        }
        // Table summary for non-visual browsers.
        $table->setAttr('summary', sprintf(_("Columns: %s."), 
                                           implode(", ", $table_summary)));

        if ( isset($this->_options['paging']) and 
             !empty($this->_options['limit']) and $this->getTotal() and
             $this->_options['paging'] != 'none')
        {
            // if there are more pages than the limit, show a table-header, -footer
            list($offset,$pagesize) = $this->limit($this->_options['limit']);
            $numrows = $this->getTotal();
            if (!$pagesize or
                (!$offset and $numrows <= $pagesize) or
                ($offset + $pagesize < 0)) 
            {
                $table->pushContent(HTML::thead($row),
                                    HTML::tbody(false, $this->_rows));
                return $table;
            }
            global $request;
            include_once('lib/Template.php');

            $tokens = array();
            $pagename = $request->getArg('pagename');
            $defargs = $request->args;
            unset($defargs['pagename']); unset($defargs['action']);
            //$defargs['nocache'] = 1;
            $prev = $defargs;
            $tokens['PREV'] = false; $tokens['PREV_LINK'] = "";
            $tokens['COLS'] = count($this->_columns);
            $tokens['COUNT'] = $numrows; 
            $tokens['OFFSET'] = $offset; 
            $tokens['SIZE'] = $pagesize;
            $tokens['NUMPAGES'] = (int)($numrows / $pagesize)+1;
            $tokens['ACTPAGE'] = (int) (($offset+1) / $pagesize)+1;
            if ($offset > 0) {
            	$prev['limit'] = min(0,$offset - $pagesize) . ",$pagesize";
            	$prev['count'] = $numrows;
            	$tokens['LIMIT'] = $prev['limit'];
                $tokens['PREV'] = true;
                $tokens['PREV_LINK'] = WikiURL($pagename,$prev);
                $prev['limit'] = "0,$pagesize";
                $tokens['FIRST_LINK'] = WikiURL($pagename,$prev);
            }
            $next = $defargs;
            $tokens['NEXT'] = false; $tokens['NEXT_LINK'] = "";
            if ($offset + $pagesize < $numrows) {
                $next['limit'] = min($offset + $pagesize,$numrows - $pagesize) . ",$pagesize";
            	$next['count'] = $numrows;
            	$tokens['LIMIT'] = $next['limit'];
                $tokens['NEXT'] = true;
                $tokens['NEXT_LINK'] = WikiURL($pagename,$next);
                $next['limit'] = $numrows - $pagesize . ",$pagesize";
                $tokens['LAST_LINK'] = WikiURL($pagename,$next);
            }
            $paging = new Template("pagelink", $request, $tokens);
            $table->pushContent(HTML::thead($paging),
                                HTML::tbody(false,HTML($row,$this->_rows)),
                                HTML::tfoot($paging));
            return $table;
        }
        $table->pushContent(HTML::thead($row),
                            HTML::tbody(false, $this->_rows));
        return $table;
    }

    function _jsFlipAll() {
      return JavaScript("
function flipAll(formObj) {
  var isFirstSet = -1;
  for (var i=0;i < formObj.length;i++) {
      fldObj = formObj.elements[i];
      if (fldObj.type == 'checkbox') { 
         if (isFirstSet == -1)
           isFirstSet = (fldObj.checked) ? true : false;
         fldObj.checked = (isFirstSet) ? false : true;
       }
   }
}");
    }

    function _generateList($caption) {
        $list = HTML::ul(array('class' => 'pagelist'));
        $i = 0;
        foreach ($this->_rows as $page) {
            $group = ($i++ / $this->_group_rows);
            $class = ($group % 2) ? 'oddrow' : 'evenrow';
            $list->pushContent(HTML::li(array('class' => $class),$page));
        }
        $out = HTML();
        //Warning: This is quite fragile. It depends solely on a private variable
        //         in ->_addColumn()
        // Questionable if its of use here anyway. This is a one-col pagename list only.
        //if (!empty($this->_columns_seen['checkbox'])) $out->pushContent($this->_jsFlipAll());
        if ($caption)
            $out->pushContent(HTML::p($caption));
        $out->pushContent($list);
        return $out;
    }

    function _emptyList($caption) {
        $html = HTML();
        if ($caption)
            $html->pushContent(HTML::p($caption));
        if ($this->_messageIfEmpty)
            $html->pushContent(HTML::blockquote(HTML::p($this->_messageIfEmpty)));
        return $html;
    }

    // Condense list: "Page1, Page2, ..." 
    // Alternative $seperator = HTML::Raw(' &middot; ')
    function _generateCommaList($seperator = ', ') {
        return HTML(join($seperator, $list));
    }

};

/* List pages with checkboxes to select from.
 * The [Select] button toggles via _jsFlipAll
 */

class PageList_Selectable
extends PageList {

    function PageList_Selectable ($columns=false, $exclude=false) {
        if ($columns) {
            if (!is_array($columns))
                $columns = explode(',', $columns);
            if (!in_array('checkbox',$columns))
                array_unshift($columns,'checkbox');
        } else {
            $columns = array('checkbox','pagename');
        }
        PageList::PageList($columns,$exclude);
    }

    function addPageList ($array) {
        while (list($pagename,$selected) = each($array)) {
            if ($selected) $this->addPageSelected((string)$pagename);
            $this->addPage((string)$pagename);
        }
    }

    function addPageSelected ($pagename) {
        $this->_selected[$pagename] = 1;
    }
    //Todo:
    //insert javascript when clicked on Selected Select/Deselect all
}

// $Log$
// Revision 1.1  2005/04/12 13:33:28  guerin
// First commit for wiki integration.
// Added Manuel's code as of revision 13 on Partners.
// Very little modification at the moment:
// - removed use of DOCUMENT_ROOT and SF_LOCAL_INC_PREFIX
// - simplified require syntax
// - removed ST-specific code (for test phase)
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
//

// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>
