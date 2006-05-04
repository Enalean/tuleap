<?php //-*-php-*-
rcs_id('$Id: Template.php 2691 2006-03-02 15:31:51Z guerin $');

require_once("lib/ErrorManager.php");


/** An HTML template.
 */
class Template
{
    /**
     *
     */
    function Template ($name, &$request, $args = false) {
        global $Theme;

        $this->_request = &$request;
        $this->_name = $name;
        $this->_basepage = $request->getArg('pagename');
        
        $file = $Theme->findTemplate($name);
        if (!$file) {
            trigger_error("no template for $name found", E_USER_WARNING);
            return;
        }
        $fp = fopen($file, "rb");
        if (!$fp) {
            trigger_error("$file not found", E_USER_WARNING);
            return;
        }
        $this->_tmpl = fread($fp, filesize($file));
        fclose($fp);
        //$userid = $request->_user->_userid;
        if (is_array($args))
            $this->_locals = $args;
        elseif ($args)
            $this->_locals = array('CONTENT' => $args);
        else
            $this->_locals = array();
    }

    function _munge_input($template) {

        // Convert < ?plugin expr ? > to < ?php $this->_printPluginPI("expr"); ? >
        $orig[] = '/<\?plugin.*?\?>/se';
        $repl[] = "\$this->_mungePlugin('\\0')";
        
        // Convert < ?= expr ? > to < ?php $this->_print(expr); ? >
        $orig[] = '/<\?=(.*?)\?>/s';
        $repl[] = '<?php $this->_print(\1);?>';
        
        return preg_replace($orig, $repl, $template);
    }

    function _mungePlugin($pi) {
        // HACK ALERT: PHP's preg_replace, with the /e option seems to
        // escape both single and double quotes with backslashes.
        // So we need to unescape the double quotes here...

        $pi = preg_replace('/(?!<\\\\)\\\\"/x', '"', $pi);
        return sprintf('<?php $this->_printPlugin(%s); ?>',
                       "'" . str_replace("'", "\'", $pi) . "'");
    }
    
    function _printPlugin ($pi) {
	include_once("lib/WikiPlugin.php");
	static $loader;

        if (empty($loader))
            $loader = new WikiPluginLoader;
        
        $this->_print($loader->expandPI($pi, $this->_request, $this, $this->_basepage));
    }
    
    function _print ($val) {
        if (isa($val, 'Template'))
            $this->_expandSubtemplate($val);
        else
            PrintXML($val);
    }

    function _expandSubtemplate (&$template) {
        // FIXME: big hack!        
        if (!$template->_request)
            $template->_request = &$this->_request;
        if (defined('DEBUG') and DEBUG) {
            echo "<!-- Begin $template->_name -->\n";
        }
        // Expand sub-template with defaults from this template.
        $template->printExpansion($this->_vars);
        if (defined('DEBUG') and DEBUG) {
            echo "<!-- End $template->_name -->\n";
        }
    }
        
    /**
     * Substitute HTML replacement text for tokens in template. 
     *
     * Constructs a new WikiTemplate based upon the named template.
     *
     * @access public
     *
     * @param $token string Name of token to substitute for.
     *
     * @param $replacement string Replacement HTML text.
     */
    function replace($varname, $value) {
        $this->_locals[$varname] = $value;
    }

    
    function printExpansion ($defaults = false) {
        if (!is_array($defaults))
            $defaults = array('CONTENT' => $defaults);
        $this->_vars = array_merge($defaults, $this->_locals);
        extract($this->_vars);

        $request = &$this->_request;
        if (!isset($user))
            $user = &$request->getUser();
        if (!isset($page))
            $page = &$request->getPage();
        
        global $Theme, $RCS_IDS, $charset;
        
        //$this->_dump_template();

        global $ErrorManager;
        $ErrorManager->pushErrorHandler(new WikiMethodCb($this, '_errorHandler'));

        eval('?>' . $this->_munge_input($this->_tmpl));

        $ErrorManager->popErrorHandler();
    }

    function getExpansion ($defaults = false) {
        ob_start();
        $this->printExpansion($defaults);
        $xml = ob_get_contents();
        ob_end_clean();
        return $xml;
    }

    function printXML () {
        $this->printExpansion();
    }

    function asXML () {
        return $this->getExpansion();
    }
    
            
    // Debugging:
    function _dump_template () {
        $lines = explode("\n", $this->_munge_input($this->_tmpl));
        $pre = HTML::pre();
        $n = 1;
        foreach ($lines as $line)
            $pre->pushContent(fmt("%4d  %s\n", $n++, $line));
        $pre->printXML();
    }

    function _errorHandler($error) {
        //if (!preg_match('/: eval\(\)\'d code$/', $error->errfile))
	//    return false;

        if (preg_match('/: eval\(\)\'d code$/', $error->errfile)) {
            $error->errfile = "In template '$this->_name'";
            // Hack alert: Ignore 'undefined variable' messages for variables
            //  whose names are ALL_CAPS.
            if (preg_match('/Undefined variable:\s*[_A-Z]+\s*$/', $error->errstr))
                return true;
        }
        else
            $error->errfile .= " (In template '$this->_name')";
        
	$lines = explode("\n", $this->_tmpl);
	if (isset($lines[$error->errline - 1]))
	    $error->errstr .= ":\n\t" . $lines[$error->errline - 1];
	return $error;
    }
};

/**
 * Get a templates
 *
 * This is a convenience function and is equivalent to:
 * <pre>
 *   new Template(...)
 * </pre>
 */
function Template($name, $args = false) {
    global $request;
    return new Template($name, $request, $args);
}

/**
 * Make and expand the top-level template. 
 *
 *
 * @param $content mixed html content to put into the page
 * @param $title string page title
 * @param $page_revision object A WikiDB_PageRevision object
 * @param $args hash Extract args for top-level template
 *
 * @return string HTML expansion of template.
 */
function GeneratePage($content, $title, $page_revision = false, $args = false) {
    global $request;
    
    if (!is_array($args))
        $args = array();

    $args['CONTENT'] = $content;
    $args['TITLE'] = $title;
    $args['revision'] = $page_revision;
    
    if (!isset($args['HEADER']))
        $args['HEADER'] = $title;
    
    printXML(new Template('html', $request, $args));
}


/**
 * For dumping pages as html to a file.
 */
function GeneratePageasXML($content, $title, $page_revision = false, $args = false) {
    global $request;
    
    if (!is_array($args))
        $args = array();

    $content->_basepage = $title;
    $args['CONTENT'] = $content;
    $args['TITLE'] = split_pagename($title);
    $args['revision'] = $page_revision;
    
    if (!isset($args['HEADER']))
        $args['HEADER'] = split_pagename($title);
    
    global $HIDE_TOOLBARS, $NO_BASEHREF, $HTML_DUMP;
    $HIDE_TOOLBARS = true;
    $HTML_DUMP = true;

    $html = asXML(new Template('htmldump', $request, $args));

    $HIDE_TOOLBARS = false;
    $HTML_DUMP = false;
    return $html;
}

// $Log$
// Revision 1.57  2004/05/01 18:20:05  rurban
// Add $charset to template locals (instead of constant CHARSET)
//
// Revision 1.56  2004/04/12 13:04:50  rurban
// added auth_create: self-registering Db users
// fixed IMAP auth
// removed rating recommendations
// ziplib reformatting
//
// Revision 1.55  2004/04/02 15:06:55  rurban
// fixed a nasty ADODB_mysql session update bug
// improved UserPreferences layout (tabled hints)
// fixed UserPreferences auth handling
// improved auth stability
// improved old cookie handling: fixed deletion of old cookies with paths
//
// Revision 1.54  2004/03/02 18:11:39  rurban
// CreateToc support: Pass the preparsed markup to each plugin as $dbi->_markup
// to be able to know about its context, and even let the plugin change it.
// (see CreateToc)
//
// Revision 1.53  2004/02/22 23:20:31  rurban
// fixed DumpHtmlToDir,
// enhanced sortby handling in PageList
//   new button_heading th style (enabled),
// added sortby and limit support to the db backends and plugins
//   for paging support (<<prev, next>> links on long lists)
//
// Revision 1.52  2003/12/20 23:59:19  carstenklapp
// Internal change: Added rcs Log tag & emacs php mode tag (sorry, forgot
// this in the last commit).
//

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:   
?>
