<?php
//-*-php-*-
rcs_id('$Id: WikiPlugin.php,v 1.61 2005/10/31 17:20:40 rurban Exp $');

class WikiPlugin
{
    public function getDefaultArguments()
    {
        return array('description' => $this->getDescription());
    }

    /** Does the plugin manage its own HTTP validators?
     *
     * This should be overwritten by (some) individual plugins.
     *
     * If the output of the plugin is static, depending only
     * on the plugin arguments, query arguments and contents
     * of the current page, this can (and should) return true.
     *
     * If the plugin can deduce a modification time, or equivalent
     * sort of tag for it's content, then the plugin should
     * call $request->appendValidators() with appropriate arguments,
     * and should override this method to return true.
     *
     * When in doubt, the safe answer here is false.
     * Unfortunately, returning false here will most likely make
     * any page which invokes the plugin uncacheable (by HTTP proxies
     * or browsers).
     */
    public function managesValidators()
    {
        return false;
    }

    // FIXME: args?
    public function run($dbi, $argstr, &$request, $basepage)
    {
        trigger_error(
            "WikiPlugin::run: pure virtual function",
            E_USER_ERROR
        );
    }

    /** Get wiki-pages linked to by plugin invocation.
     *
     * A plugin may override this method to add pages to the
     * link database for the invoking page.
     *
     * For example, the IncludePage plugin should override this so
     * that the including page shows up in the backlinks list for the
     * included page.
     *
     * Not all plugins which generate links to wiki-pages need list
     * those pages here.
     *
     * Note also that currently the links are calculated at page save
     * time, so only static page links (e.g. those dependent on the PI
     * args, not the rest of the wikidb state or any request query args)
     * will work correctly here.
     *
     * @param string $argstr The plugin argument string.
     * @param string $basepage The pagename the plugin is invoked from.
     * @return array List of pagenames linked to (or false).
     */
    public function getWikiPageLinks($argstr, $basepage)
    {
        return false;
    }

    /**
     * Get name of plugin.
     *
     * This is used (by default) by getDefaultLinkArguments and
     * getDefaultFormArguments to compute the default link/form
     * targets.
     *
     * If you want to gettextify the name (probably a good idea),
     * override this method in your plugin class, like:
     * <pre>
     *   function getName() { return _("MyPlugin"); }
     * </pre>
     *
     * @return string plugin name/target.
     */
    public function getName()
    {
        return preg_replace('/^.*_/', '', static::class);
    }

    public function getDescription()
    {
        return $this->getName();
    }

    // plugins should override this with the commented-out code
    public function getVersion()
    {
        return _("n/a");
        //return preg_replace("/[Revision: $]/", '',
        //                    "\$Revision: 1.61 $");
    }

    public function getArgs($argstr, $request = false, $defaults = false)
    {
        if ($defaults === false) {
            $defaults = $this->getDefaultArguments();
        }
        //Fixme: on POST argstr is empty
        list ($argstr_args, $argstr_defaults) = $this->parseArgStr($argstr);
        $args = array();
        if (!empty($defaults)) {
            foreach ($defaults as $arg => $default_val) {
                if (isset($argstr_args[$arg])) {
                    $args[$arg] = $argstr_args[$arg];
                } elseif ($request and ($argval = $request->getArg($arg)) !== false) {
                    $args[$arg] = $argval;
                } elseif (isset($argstr_defaults[$arg])) {
                    $args[$arg] = (string) $argstr_defaults[$arg];
                } else {
                    $args[$arg] = $default_val;
                }
                // expand [arg]
                if ($request and is_string($args[$arg]) and strstr($args[$arg], "[")) {
                    $args[$arg] = $this->expandArg($args[$arg], $request);
                }

                unset($argstr_args[$arg]);
                unset($argstr_defaults[$arg]);
            }
        }

        foreach (array_merge($argstr_args, $argstr_defaults) as $arg => $val) {
            if ($request and $request->getArg('pagename') == _("PhpWikiAdministration")
                and $arg == 'overwrite') { // silence this warning
            } else {
                trigger_error(sprintf(
                    _("Argument '%s' not declared by plugin."),
                    $arg
                ), E_USER_NOTICE);
            }
        }

        // add special handling of pages and exclude args to accept <! plugin-list !>
        // and split explodePageList($args['exclude']) => array()
        // TODO : handle p[] pagehash
        foreach (array('pages', 'exclude') as $key) {
            if (!empty($args[$key]) and array_key_exists($key, $defaults)) {
                $args[$key] = is_string($args[$key])
                    ? explodePageList($args[$key])
                    : $args[$key]; // <! plugin-list !>
            }
        }

        // always override sortby,limit from the REQUEST. ignore defaults if defined as such.
        foreach (array('sortby', 'limit') as $key) {
            if (array_key_exists($key, $defaults)) {
                if ($val = $GLOBALS['request']->getArg($key)) {
                    $args[$key] = $val;
                } elseif (!empty($args[$key])) {
                    $GLOBALS['request']->setArg($key, $args[$key]);
                }
            }
        }
        return $args;
    }

    // Patch by Dan F:
    // Expand [arg] to $request->getArg("arg") unless preceded by ~
    public function expandArg($argval, &$request)
    {
        // Replace the arg unless it is preceded by a ~
        $ret = preg_replace_callback(
            '/([^~]|^)\[(\w[\w\d]*)\]/',
            function (array $matches) use ($request) {
                return $matches[1] . $request->getArg($matches[2]);
            },
            $argval
        );
        // Ditch the ~ so later versions can be expanded if desired
        return preg_replace('/~(\[\w[\w\d]*\])/', '$1', $ret);
    }

    public function parseArgStr($argstr)
    {
        $args = array();
        $defaults = array();
        if (empty($argstr)) {
            return array($args,$defaults);
        }

        $arg_p = '\w+';
        $op_p = '(?:\|\|)?=';
        $word_p = '\S+';
        $opt_ws = '\s*';
        $qq_p = '" ( (?:[^"\\\\]|\\\\.)* ) "';
        //"<--kludge for brain-dead syntax coloring
        $q_p  = "' ( (?:[^'\\\\]|\\\\.)* ) '";
        $gt_p = "_\\( $opt_ws $qq_p $opt_ws \\)";
        $argspec_p = "($arg_p) $opt_ws ($op_p) $opt_ws (?: $qq_p|$q_p|$gt_p|($word_p))";

        // handle plugin-list arguments seperately
        $plugin_p = '<!plugin-list\s+\w+.*?!>';
        while (preg_match("/^($arg_p) $opt_ws ($op_p) $opt_ws ($plugin_p) $opt_ws/x", $argstr, $m)) {
            @ list(,$arg, $op, $plugin_val) = $m;
            $argstr = substr($argstr, strlen($m[0]));
            $loader = new WikiPluginLoader();
            $markup = null;
            $basepage = null;
            $plugin_val = preg_replace(array("/^<!/","/!>$/"), array("<?","?>"), $plugin_val);
            $val = $loader->expandPI($plugin_val, $GLOBALS['request'], $markup, $basepage);
            if ($op == '=') {
                $args[$arg] = $val; // comma delimited pagenames or array()?
            } else {
                assert($op == '||=');
                $defaults[$arg] = $val;
            }
        }
        while (preg_match("/^$opt_ws $argspec_p $opt_ws/x", $argstr, $m)) {
            @ list(,$arg,$op,$qq_val,$q_val,$gt_val,$word_val) = $m;
            $argstr = substr($argstr, strlen($m[0]));

            // Remove quotes from string values.
            if ($qq_val) {
                $val = stripslashes($qq_val);
            } elseif ($q_val) {
                $val = stripslashes($q_val);
            } elseif ($gt_val) {
                $val = _(stripslashes($gt_val));
            } else {
                $val = $word_val;
            }

            if ($op == '=') {
                $args[$arg] = $val;
            } else {
                // NOTE: This does work for multiple args. Use the
                // separator character defined in your webserver
                // configuration, usually & or &amp; (See
                // http://www.htmlhelp.com/faq/cgifaq.4.html)
                // e.g. <plugin RecentChanges days||=1 show_all||=0 show_minor||=0>
                // url: RecentChanges?days=1&show_all=1&show_minor=0
                assert($op == '||=');
                $defaults[$arg] = $val;
            }
        }

        if ($argstr) {
            $this->handle_plugin_args_cruft($argstr, $args);
        }

        return array($args, $defaults);
    }

    /* A plugin can override this function to define how any remaining text is handled */
    public function handle_plugin_args_cruft($argstr, $args)
    {
        trigger_error(sprintf(
            _("trailing cruft in plugin args: '%s'"),
            $argstr
        ), E_USER_NOTICE);
    }

    /* handle plugin-list argument: use run(). */
    public function makeList($plugin_args, $request, $basepage)
    {
        $dbi = $request->getDbh();
        $pagelist = $this->run($dbi, $plugin_args, $request, $basepage);
        $list = array();
        if (is_object($pagelist) and isa($pagelist, 'PageList')) {
            // table or list?
            foreach ($pagelist->_pages as $page) {
                $list[] = $page->getName();
            }
        }
        return $list;
    }

    public function getDefaultLinkArguments()
    {
        return array('targetpage'  => $this->getName(),
                     'linktext'    => $this->getName(),
                     'description' => $this->getDescription(),
                     'class'       => 'wikiaction');
    }

    public function makeLink($argstr, $request)
    {
        $defaults = $this->getDefaultArguments();
        $link_defaults = $this->getDefaultLinkArguments();
        $defaults = array_merge($defaults, $link_defaults);

        $args = $this->getArgs($argstr, $request, $defaults);
        $plugin = $this->getName();

        $query_args = array();
        foreach ($args as $arg => $val) {
            if (isset($link_defaults[$arg])) {
                continue;
            }
            if ($val != $defaults[$arg]) {
                $query_args[$arg] = $val;
            }
        }

        $link = Button($query_args, $args['linktext'], $args['targetpage']);
        if (!empty($args['description'])) {
            $link->addTooltip($args['description']);
        }

        return $link;
    }

    public function getDefaultFormArguments()
    {
        return array('targetpage' => $this->getName(),
                     'buttontext' => $this->getName(),
                     'class'      => 'wikiaction',
                     'method'     => 'get',
                     'textinput'  => 's',
                     'description' => $this->getDescription(),
                     'formsize'   => 30);
    }

    public function makeForm($argstr, $request)
    {
        $form_defaults = $this->getDefaultFormArguments();
        $defaults = array_merge(
            $form_defaults,
            array('start_debug' => $request->getArg('start_debug')),
            $this->getDefaultArguments()
        );

        $args = $this->getArgs($argstr, $request, $defaults);
        $plugin = $this->getName();
        $textinput = $args['textinput'];
        assert(!empty($textinput) && isset($args['textinput']));

        $form = HTML::form(array('action' => WikiURL($args['targetpage']),
                                 'method' => $args['method'],
                                 'class'  => $args['class'],
                                 'accept-charset' => $GLOBALS['charset']));
        $form->pushContent(HTML::input(array('type' => 'hidden',
                                             'name' => 'group_id',
                                             'value' => GROUP_ID)));
        if (! USE_PATH_INFO) {
            $pagename = $request->get('pagename');
            $form->pushContent(HTML::input(array('type' => 'hidden',
                                                 'name' => 'pagename',
                                                 'value' => $args['targetpage'])));
        }
        if ($args['targetpage'] != $this->getName()) {
            $form->pushContent(HTML::input(array('type' => 'hidden',
                                                 'name' => 'action',
                                                 'value' => $this->getName())));
        }
        $contents = HTML::div();
        $contents->setAttr('class', $args['class']);

        foreach ($args as $arg => $val) {
            if (isset($form_defaults[$arg])) {
                continue;
            }
            if ($arg != $textinput && $val == $defaults[$arg]) {
                continue;
            }

            $i = HTML::input(array('name' => $arg, 'value' => $val));

            if ($arg == $textinput) {
                //if ($inputs[$arg] == 'file')
                //    $attr['type'] = 'file';
                //else
                $i->setAttr('type', 'text');
                $i->setAttr('size', $args['formsize']);
                if ($args['description']) {
                    $i->addTooltip($args['description']);
                }
            } else {
                $i->setAttr('type', 'hidden');
            }
            $contents->pushContent($i);

            // FIXME: hackage
            if ($i->getAttr('type') == 'file') {
                $form->setAttr('enctype', 'multipart/form-data');
                $form->setAttr('method', 'post');
                $contents->pushContent(HTML::input(array('name' => 'MAX_FILE_SIZE',
                                                         'value' => MAX_UPLOAD_SIZE,
                                                         'type' => 'hidden')));
            }
        }

        if (!empty($args['buttontext'])) {
            $contents->pushContent(HTML::input(array('type' => 'submit',
                                                     'class' => 'button',
                                                     'value' => $args['buttontext'])));
        }
        $form->pushContent($contents);
        return $form;
    }

    // box is used to display a fixed-width, narrow version with common header
    public function box($args = false, $request = false, $basepage = false)
    {
        if (!$request) {
            $request = $GLOBALS['request'];
        }
        $dbi = $request->getDbh();
        return $this->makeBox('', $this->run($dbi, $args, $request, $basepage));
    }

    public function makeBox($title, $body)
    {
        if (!$title) {
            $title = $this->getName();
        }
        return HTML::div(
            array('class' => 'box'),
            HTML::div(array('class' => 'box-title'), $title),
            HTML::div(array('class' => 'box-data'), $body)
        );
    }

    public function error($message)
    {
        return HTML::div(
            array('class' => 'errors'),
            HTML::strong(fmt("Plugin %s failed.", $this->getName())),
            ' ',
            $message
        );
    }

    public function disabled($message = '')
    {
        $html[] = HTML::div(
            array('class' => 'title'),
            fmt("Plugin %s disabled.", $this->getName()),
            ' ',
            $message
        );
        $html[] = HTML::pre($this->_pi);
        return HTML::div(array('class' => 'disabled-plugin'), $html);
    }

    // TODO: Not really needed, since our plugins generally initialize their own
    // PageList object, which accepts options['types'].
    // Register custom PageList types for special plugins, like
    // 'hi_content' for WikiAdminSearcheplace, 'renamed_pagename' for WikiAdminRename, ...
    public function addPageListColumn($array)
    {
        global $customPageListColumns;
        if (empty($customPageListColumns)) {
            $customPageListColumns = array();
        }
        foreach ($array as $column => $obj) {
            $customPageListColumns[$column] = $obj;
        }
    }

    // provide a sample usage text for automatic edit-toolbar insertion
    public function getUsage()
    {
        $args = $this->getDefaultArguments();
        $string = '<' . '?plugin ' . $this->getName() . ' ';
        if ($args) {
            foreach ($args as $key => $value) {
                $string .= ($key . "||=" . (string) $value . " ");
            }
        }
        return $string . '?' . '>';
    }

    public function getArgumentsDescription()
    {
        $arguments = HTML();
        foreach ($this->getDefaultArguments() as $arg => $default) {
            // Work around UserPreferences plugin to avoid error
            if ((is_array($default))) {
                $default = '(array)';
                // This is a bit flawed with UserPreferences object
                //$default = sprintf("array('%s')",
                //                   implode("', '", array_keys($default)));
            } elseif (stristr($default, ' ')) {
                    $default = "'$default'";
            }
            $arguments->pushcontent("$arg=$default", HTML::br());
        }
        return $arguments;
    }
}

class WikiPluginLoader
{
    public $_errors;

    public function expandPI($pi, &$request, &$markup, $basepage = false)
    {
        if (!($ppi = $this->parsePi($pi))) {
            return false;
        }
        list($pi_name, $plugin, $plugin_args) = $ppi;

        if (!is_object($plugin)) {
            return new HtmlElement(
                $pi_name == 'plugin-link' ? 'span' : 'p',
                array('class' => 'plugin-error'),
                $this->getErrorDetail()
            );
        }
        switch ($pi_name) {
            case 'plugin':
                // FIXME: change API for run() (no $dbi needed).
                $dbi = $request->getDbh();
                // pass the parsed CachedMarkup context in dbi to the plugin
                // to be able to know about itself, or even to change the markup XmlTree (CreateToc)
                $dbi->_markup = &$markup;
                // FIXME: could do better here...
                if (! $plugin->managesValidators()) {
                    // Output of plugin (potentially) depends on
                    // the state of the WikiDB (other than the current
                    // page.)

                    // Lacking other information, we'll assume things
                    // changed last time the wikidb was touched.

                    // As an additional hack, mark the ETag weak, since,
                    // for all we know, the page might depend
                    // on things other than the WikiDB (e.g. PhpWeather,
                    // Calendar...)

                    $timestamp = $dbi->getTimestamp();
                    $request->appendValidators(array('dbi_timestamp' => $timestamp,
                                                     '%mtime' => (int) $timestamp,
                                                     '%weak' => true));
                }
                return $plugin->run($dbi, $plugin_args, $request, $basepage);
            case 'plugin-list':
                return $plugin->makeList($plugin_args, $request, $basepage);
            case 'plugin-link':
                return $plugin->makeLink($plugin_args, $request);
            case 'plugin-form':
                return $plugin->makeForm($plugin_args, $request);
        }
    }

    public function getWikiPageLinks($pi, $basepage)
    {
        if (!($ppi = $this->parsePi($pi))) {
            return false;
        }
        list($pi_name, $plugin, $plugin_args) = $ppi;
        if (!is_object($plugin)) {
            return false;
        }
        if ($pi_name != 'plugin') {
            return false;
        }
        return $plugin->getWikiPageLinks($plugin_args, $basepage);
    }

    public function parsePI($pi)
    {
        if (!preg_match('/^\s*<\?(plugin(?:-form|-link|-list)?)\s+(\w+)\s*(.*?)\s*\?>\s*$/s', $pi, $m)) {
            return $this->_error(sprintf("Bad %s", 'PI'));
        }

        list(, $pi_name, $plugin_name, $plugin_args) = $m;
        $plugin = $this->getPlugin($plugin_name, $pi);

        return array($pi_name, $plugin, $plugin_args);
    }

    public function getPlugin($plugin_name, $pi = false)
    {
        global $ErrorManager;

    //Changes by Sabri LABBENE
    //Some plugins were removed since we don't use them any more
    //the following array contains the removed plugins names. References
    //to these plugins will never be processed.
        $removed_plugins = array("RawHtml", "RateIt", "PhpWeather", "AnalyseAccessLogSql", "FoafViewer", "ModeratePage", "Ploticus", "AllUsers");
        if (in_array($plugin_name, $removed_plugins)) {
              return $this->_error(sprintf(_("The '%s' plugin is blocked by administrator. Sorry for the inconvenience"), _($plugin_name)));
        }
    // Note that there seems to be no way to trap parse errors
        // from this include.  (At least not via set_error_handler().)
        $plugin_source = "lib/plugin/$plugin_name.php";

        $ErrorManager->pushErrorHandler(new WikiMethodCb($this, '_plugin_error_filter'));
        $plugin_class = "WikiPlugin_$plugin_name";
        if (!class_exists($plugin_class)) {
            // $include_failed = !@include_once("lib/plugin/$plugin_name.php");
            $include_failed = !include_once("lib/plugin/$plugin_name.php");
            $ErrorManager->popErrorHandler();

            if (!class_exists($plugin_class)) {
                if ($include_failed) {
                    return $this->_error(sprintf(
                        _("Include of '%s' failed."),
                        $plugin_source
                    ));
                }
                return $this->_error(sprintf(_("%s: no such class"), $plugin_class));
            }
        }
        $ErrorManager->popErrorHandler();
        $plugin = new $plugin_class;
        if (!is_subclass_of($plugin, "WikiPlugin")) {
            return $this->_error(sprintf(
                _("%s: not a subclass of WikiPlugin."),
                $plugin_class
            ));
        }

        $plugin->_pi = $pi;
        return $plugin;
    }

    public function _plugin_error_filter($err)
    {
        if (preg_match("/Failed opening '.*' for inclusion/", $err->errstr)) {
            return true;        // Ignore this error --- it's expected.
        }
        return false;
    }

    public function getErrorDetail()
    {
        return $this->_errors;
    }

    public function _error($message)
    {
        $this->_errors = $message;
        return false;
    }
}

// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
