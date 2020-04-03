<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

if (isset($GLOBALS['ErrorManager'])) {
    return;
}

// php5: ignore E_STRICT (var warnings)
/*
if (defined('E_STRICT')
    and (E_ALL & E_STRICT)
    and (error_reporting() & E_STRICT)) {
    echo " errormgr: error_reporting=", error_reporting();
    echo "\nplease fix that in your php.ini!";
    error_reporting(E_ALL & ~E_STRICT);
}
*/
define('EM_FATAL_ERRORS', E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | ~2048);
define(
    'EM_WARNING_ERRORS',
    E_WARNING | E_CORE_WARNING | E_COMPILE_WARNING | E_USER_WARNING
);
if (defined('E_DEPRECATED')) {
    define('EM_NOTICE_ERRORS', E_NOTICE | E_USER_NOTICE | E_DEPRECATED);
} else {
    define('EM_NOTICE_ERRORS', E_NOTICE | E_USER_NOTICE);
}

/* It is recommended to leave assertions on.
   You can simply comment the two lines below to leave them on.
   Only where absolute speed is necessary you might want to turn
   them off.
*/
if (1 or (defined('DEBUG') and DEBUG)) {
    assert_options(ASSERT_ACTIVE, 1);
} else {
    assert_options(ASSERT_ACTIVE, 0);
}
assert_options(ASSERT_CALLBACK, 'wiki_assert_handler');

function wiki_assert_handler($file, $line, $code)
{
    ErrorManager_errorHandler($code, sprintf("<br />%s:%s: %s: Assertion failed <br />", $file, $line, $code), $file, $line);
}

/**
 * A class which allows custom handling of PHP errors.
 *
 * This is a singleton class. There should only be one instance
 * of it --- you can access the one instance via $GLOBALS['ErrorManager'].
 *
 * FIXME: more docs.
 */
class ErrorManager
{
    /**
     *
     *
     * As this is a singleton class, you should never call this.
     * @access private
     */
    public function __construct()
    {
        $this->_handlers = array();
        $this->_fatal_handler = false;
        $this->_postpone_mask = 0;
        $this->_postponed_errors = array();

        set_error_handler('ErrorManager_errorHandler');
    }

    /**
     * Get mask indicating which errors are currently being postponed.
     * @access public
     * @return int The current postponed error mask.
     */
    public function getPostponedErrorMask()
    {
        return $this->_postpone_mask;
    }

    /**
     * Set mask indicating which errors to postpone.
     *
     * The default value of the postpone mask is zero (no errors postponed.)
     *
     * When you set this mask, any queue errors which do not match the new
     * mask are reported.
     *
     * @access public
     * @param $newmask int The new value for the mask.
     */
    public function setPostponedErrorMask($newmask)
    {
        $this->_postpone_mask = $newmask;
        if (function_exists('PrintXML')) {
            PrintXML($this->_flush_errors($newmask));
        } else {
            echo($this->_flush_errors($newmask));
        }
    }

    /**
     * Report any queued error messages.
     * @access public
     */
    public function flushPostponedErrors()
    {
        if (function_exists('PrintXML')) {
            PrintXML($this->_flush_errors());
        } else {
            echo $this->_flush_errors();
        }
    }

    /**
     * Get postponed errors, formatted as HTML.
     *
     * This also flushes the postponed error queue.
     *
     * @return object HTML describing any queued errors (or false, if none).
     */
    public function getPostponedErrorsAsHTML()
    {
        $flushed = $this->_flush_errors();
        if (!$flushed) {
            return false;
        }
        if ($flushed->isEmpty()) {
            return false;
        }
        // format it with the worst class (error, warning, notice)
        $worst_err = $flushed->_content[0];
        foreach ($flushed->_content as $err) {
            if ($err and isa($err, 'PhpError') and $err->errno > $worst_err->errno) {
                $worst_err = $err;
            }
        }
        if ($worst_err->isNotice()) {
            return $flushed;
        }
        $class = $worst_err->getHtmlClass();
        $html = HTML::div(
            array('style' => 'border: none', 'class' => $class),
            HTML::h4(
                array('class' => 'errors'),
                "PHP " . $worst_err->getDescription()
            )
        );
        $html->pushContent($flushed);
        return $html;
    }

    /**
     * Push a custom error handler on the handler stack.
     *
     * Sometimes one is performing an operation where one expects
     * certain errors or warnings. In this case, one might not want
     * these errors reported in the normal manner. Installing a custom
     * error handler via this method allows one to intercept such
     * errors.
     *
     * An error handler installed via this method should be either a
     * function or an object method taking one argument: a PhpError
     * object.
     *
     * The error handler should return either:
     * <dl>
     * <dt> False <dd> If it has not handled the error. In this case,
     *                 error processing will proceed as if the handler
     *                 had never been called: the error will be passed
     *                 to the next handler in the stack, or the
     *                 default handler, if there are no more handlers
     *                 in the stack.
     *
     * <dt> True <dd> If the handler has handled the error. If the
     *                error was a non-fatal one, no further processing
     *                will be done. If it was a fatal error, the
     *                ErrorManager will still terminate the PHP
     *                process (see setFatalHandler.)
     *
     * <dt> A PhpError object <dd> The error is not considered
     *                             handled, and will be passed on to
     *                             the next handler(s) in the stack
     *                             (or the default handler). The
     *                             returned PhpError need not be the
     *                             same as the one passed to the
     *                             handler. This allows the handler to
     *                             "adjust" the error message.
     * </dl>
     * @access public
     * @param $handler WikiCallback  Handler to call.
     */
    public function pushErrorHandler($handler)
    {
        array_unshift($this->_handlers, $handler);
    }

    /**
     * Pop an error handler off the handler stack.
     * @access public
     */
    public function popErrorHandler()
    {
        return array_shift($this->_handlers);
    }

    /**
     * Set a termination handler.
     *
     * This handler will be called upon fatal errors. The handler
     * gets passed one argument: a PhpError object describing the
     * fatal error.
     *
     * @access public
     * @param $handler WikiCallback  Callback to call on fatal errors.
     */
    public function setFatalHandler($handler)
    {
        $this->_fatal_handler = $handler;
    }

    /**
     * Handle an error.
     *
     * The error is passed through any registered error handlers, and
     * then either reported or postponed.
     *
     * @access public
     * @param $error object A PhpError object.
     */
    public function handleError($error)
    {
        static $in_handler;

        if (!empty($in_handler)) {
            $msg = $error->_getDetail();
            $msg->unshiftContent(HTML::h2(fmt(
                "%s: error while handling error:",
                "ErrorManager"
            )));
            $msg->printXML();
            return;
        }

        // template which flushed the pending errors already handled,
        // so display now all errors directly.
        if (!empty($GLOBALS['request']->_finishing)) {
            $this->_postpone_mask = 0;
        }

        $in_handler = true;

        foreach ($this->_handlers as $handler) {
            if (!$handler) {
                continue;
            }
            $result = $handler->call($error);
            if (!$result) {
                continue;       // Handler did not handle error.
            } elseif (is_object($result)) {
                // handler filtered the result. Still should pass to
                // the rest of the chain.
                if ($error->isFatal()) {
                    // Don't let handlers make fatal errors non-fatal.
                    $result->errno = $error->errno;
                }
                $error = $result;
            } else {
                // Handler handled error.
                if (!$error->isFatal()) {
                    $in_handler = false;
                    return;
                }
                break;
            }
        }

        $this->_noCacheHeaders();

        // Error was either fatal, or was not handled by a handler.
        // Handle it ourself.
        if ($error->isFatal()) {
            echo "<html><body><div style=\"font-weight:bold; color:red\">Fatal Error:</div>\n";
            $this->_die($error);
        } elseif (($error->errno & error_reporting()) != 0) {
            if (($error->errno & $this->_postpone_mask) == 0) {
                if ($error instanceof PhpErrorOnce) {
                    $error->removeDoublettes($this->_postponed_errors);
                    if ($error->_count < 2) {
                        $this->_postponed_errors[] = $error;
                    }
                } else {
                    $this->_postponed_errors[] = $error;
                }
            }
        }
        $in_handler = false;
    }

    public function warning($msg, $errno = E_USER_NOTICE)
    {
        $this->handleError(new PhpWikiError($errno, $msg));
    }

    /**
     * @access private
     */
    public function _die($error)
    {
        //echo "\n\n<html><body>";
        $error->printXML();
        PrintXML($this->_flush_errors());
        if ($this->_fatal_handler) {
            $this->_fatal_handler->call($error);
        }
        exit - 1;
    }

    /**
     * @access private
     */
    public function _flush_errors($keep_mask = 0)
    {
        $errors = &$this->_postponed_errors;
        if (empty($errors)) {
            return '';
        }
        $flushed = HTML();
        for ($i = 0; $i < count($errors); $i++) {
            $error = $errors[$i];
            if (!is_object($error)) {
                continue;
            }
            if (($error->errno & $keep_mask) != 0) {
                continue;
            }
            unset($errors[$i]);
            $flushed->pushContent($error);
        }
        return $flushed;
    }

    public function _noCacheHeaders()
    {
        global $request;
        static $already = false;

        if (isset($request) and isset($request->_validators)) {
            $request->_validators->_tag = false;
            $request->_validators->_mtime = false;
        }
        if ($already) {
            return;
        }

        // FIXME: Howto announce that to Request->cacheControl()?
        if (!headers_sent()) {
            header("Cache-control: no-cache");
            header("Pragma: nocache");
        }
        $already = true;
    }
}

/**
 * Global error handler for class ErrorManager.
 *
 * This is necessary since PHP's set_error_handler() does not allow
 * one to set an object method as a handler.
 *
 * @access private
 */
function ErrorManager_errorHandler($errno, $errstr, $errfile, $errline)
{
    if (!isset($GLOBALS['ErrorManager'])) {
        $GLOBALS['ErrorManager'] = new ErrorManager();
    }

    $error = new PhpErrorOnce($errno, $errstr, $errfile, $errline);
    $GLOBALS['ErrorManager']->handleError($error);
}


/**
 * A class representing a PHP error report.
 *
 * @see The PHP documentation for set_error_handler at
 *      http://php.net/manual/en/function.set-error-handler.php .
 */
class PhpError
{
    /**
     * The PHP errno
     */
    //var $errno;

    /**
     * The PHP error message.
     */
    //var $errstr;

    /**
     * The source file where the error occurred.
     */
    //var $errfile;

    /**
     * The line number (in $this->errfile) where the error occured.
     */
    //var $errline;

    /**
     * Construct a new PhpError.
     * @param $errno   int
     * @param $errstr  string
     * @param $errfile string
     * @param $errline int
     */
    public function __construct($errno, $errstr, $errfile, $errline)
    {
        $this->errno   = $errno;
        $this->errstr  = $errstr;
        $this->errfile = $errfile;
        $this->errline = $errline;
    }

    /**
     * Determine whether this is a fatal error.
     * @return bool True if this is a fatal error.
     */
    public function isFatal()
    {
        return ($this->errno & (2048 | EM_WARNING_ERRORS | EM_NOTICE_ERRORS)) == 0;
    }

    /**
     * Determine whether this is a warning level error.
     * @return bool
     */
    public function isWarning()
    {
        return ($this->errno & EM_WARNING_ERRORS) != 0;
    }

    /**
     * Determine whether this is a notice level error.
     * @return bool
     */
    public function isNotice()
    {
        return ($this->errno & EM_NOTICE_ERRORS) != 0;
    }
    public function getHtmlClass()
    {
        if ($this->isNotice()) {
            return 'hint';
        } elseif ($this->isWarning()) {
            return 'warning';
        } else {
            return 'errors';
        }
    }

    public function getDescription()
    {
        if ($this->isNotice()) {
            return 'Notice';
        } elseif ($this->isWarning()) {
            return 'Warning';
        } else {
            return 'Error';
        }
    }

    /**
     * Get a printable, HTML, message detailing this error.
     * @return object The detailed error message.
     */
    public function _getDetail()
    {
        $dir = defined('PHPWIKI_DIR') ? PHPWIKI_DIR : substr(dirname(__FILE__), 0, -4);
        if (substr(PHP_OS, 0, 3) == 'WIN') {
            $dir = str_replace('/', '\\', $dir);
            $this->errfile = str_replace('/', '\\', $this->errfile);
            $dir .= "\\";
        } else {
            $dir .= '/';
        }
        $errfile = preg_replace('|^' . preg_quote($dir, '|') . '|', '', $this->errfile);
        $lines = explode("\n", $this->errstr);
        if (DEBUG & _DEBUG_VERBOSE) {
            $msg = sprintf(
                "%s:%d: %s[%d]: %s",
                $errfile,
                $this->errline,
                $this->getDescription(),
                $this->errno,
                array_shift($lines)
            );
        } else {
            $msg = sprintf(
                "%s:%d: %s: \"%s\"",
                $errfile,
                $this->errline,
                $this->getDescription(),
                array_shift($lines)
            );
        }

        $html = HTML::div(array('class' => $this->getHtmlClass()), HTML::p($msg));
        // The class is now used for the div container.
        // $html = HTML::div(HTML::p($msg));
        if ($lines) {
            $list = HTML::ul();
            foreach ($lines as $line) {
                $list->pushContent(HTML::li($line));
            }
            $html->pushContent($list);
        }

        return $html;
    }

    /**
     * Print an HTMLified version of this error.
     * @see asXML()
     */
    public function printXML()
    {
        PrintXML($this->_getDetail());
    }

    /**
     * Return an HTMLified version of this error.
     */
    public function asXML()
    {
        return AsXML($this->_getDetail());
    }

    /**
     * Return a plain-text version of this error.
     */
    public function asString()
    {
        return AsString($this->_getDetail());
    }

    public function printSimpleTrace($bt)
    {
        $nl = isset($_SERVER['REQUEST_METHOD']) ? "<br />" : "\n";
        echo $nl . "Traceback:" . $nl;
        foreach ($bt as $i => $elem) {
            if (!array_key_exists('file', $elem)) {
                continue;
            }
            print "  " . $elem['file'] . ':' . $elem['line'] . $nl;
        }
        flush();
    }
}

/**
 * A class representing a PhpWiki warning.
 *
 * This is essentially the same as a PhpError, except that the
 * error message is quieter: no source line, etc...
 */
class PhpWikiError extends PhpError
{
    /**
     * Construct a new PhpError.
     * @param $errno   int
     * @param $errstr  string
     */
    public function __construct($errno, $errstr)
    {
        parent::__construct($errno, $errstr, '?', '?');
    }

    public function _getDetail()
    {
        return HTML::div(
            array('class' => $this->getHtmlClass()),
            HTML::p($this->getDescription() . ": $this->errstr")
        );
    }
}

/**
 * A class representing a Php warning, printed only the first time.
 *
 * Similar to PhpError, except only the first same error message is printed,
 * with number of occurences.
 */
class PhpErrorOnce extends PhpError
{

    public function __construct($errno, $errstr, $errfile, $errline)
    {
        $this->_count = 1;
        parent::__construct($errno, $errstr, $errfile, $errline);
    }

    public function _sameError($error)
    {
        if (!$error) {
            return false;
        }
        return ($this->errno == $error->errno and
                $this->errfile == $error->errfile and
                $this->errline == $error->errline);
    }

    // count similar handlers, increase _count and remove the rest
    public function removeDoublettes(&$errors)
    {
        for ($i = 0; $i < count($errors); $i++) {
            if (!isset($errors[$i])) {
                continue;
            }
            if ($this->_sameError($errors[$i])) {
                $errors[$i]->_count++;
                $this->_count++;
                if ($i) {
                    unset($errors[$i]);
                }
            }
        }
        return $this->_count;
    }

    public function _getDetail($count = 0)
    {
        // Codendi : don't display notices
        //if ($this->isNotice()) return;
        if (!$count) {
            $count = $this->_count;
        }
        $dir = defined('PHPWIKI_DIR') ? PHPWIKI_DIR : substr(dirname(__FILE__), 0, -4);
        if (substr(PHP_OS, 0, 3) == 'WIN') {
            $dir = str_replace('/', '\\', $dir);
            $this->errfile = str_replace('/', '\\', $this->errfile);
            $dir .= "\\";
        } else {
            $dir .= '/';
        }
        $errfile = preg_replace('|^' . preg_quote($dir, '|') . '|', '', $this->errfile);
        if (is_string($this->errstr)) {
            $lines = explode("\n", $this->errstr);
        } elseif (is_object($this->errstr)) {
            $lines = array($this->errstr->asXML());
        }
        $errtype = (DEBUG & _DEBUG_VERBOSE) ? sprintf("%s[%d]", $this->getDescription(), $this->errno)
                                            : sprintf("%s", $this->getDescription());
        $msg = sprintf(
            "%s:%d: %s: %s %s",
            $errfile,
            $this->errline,
            $errtype,
            array_shift($lines),
            $count > 1 ? sprintf(" (...repeated %d times)", $count) : ""
        );
        $html = HTML::div(
            array('class' => $this->getHtmlClass()),
            HTML::p($msg)
        );
        if ($lines) {
            $list = HTML::ul();
            foreach ($lines as $line) {
                $list->pushContent(HTML::li($line));
            }
            $html->pushContent($list);
        }

        return $html;
    }
}

require_once(dirname(__FILE__) . '/HtmlElement.php');

if (!isset($GLOBALS['ErrorManager'])) {
    $GLOBALS['ErrorManager'] = new ErrorManager();
}

// $Log: ErrorManager.php,v $
// Revision 1.45  2005/10/29 14:28:08  uckelman
// existence of isa should be checked, not built-in is_a()
//
// Revision 1.44  2005/08/07 10:52:43  rurban
// stricter error handling: dba errors are fatal, display errors on Request->finish or session_close
//
// Revision 1.43  2005/04/11 19:41:23  rurban
// Improve postponed errors+warnins list layout.
//
// Revision 1.42  2005/02/26 18:29:07  rurban
// re-enable colored boxed errors
//
// Revision 1.41  2004/12/26 17:08:36  rurban
// php5 fixes: case-sensitivity, no & new
//
// Revision 1.40  2004/12/13 14:39:46  rurban
// aesthetics
//
// Revision 1.39  2004/11/05 18:04:20  rurban
// print errno only if _DEBUG_VERBOSE
//
// Revision 1.38  2004/10/19 17:34:55  rurban
// <4.3 fix
//
// Revision 1.37  2004/10/14 19:23:58  rurban
// remove debugging prints
//
// Revision 1.36  2004/10/12 15:35:43  rurban
// avoid Php Notice header
//
// Revision 1.35  2004/10/12 13:13:19  rurban
// php5 compatibility (5.0.1 ok)
//
// Revision 1.34  2004/09/24 18:52:19  rurban
// in deferred html error messages use the worst header and class
// (notice => warning => errors)
//
// Revision 1.33  2004/09/14 10:28:21  rurban
// use assert, maybe we should only turn it off for releases
//
// Revision 1.32  2004/07/08 13:50:32  rurban
// various unit test fixes: print error backtrace on _DEBUG_TRACE; allusers fix; new PHPWIKI_NOMAIN constant for omitting the mainloop
//
// Revision 1.31  2004/07/02 09:55:58  rurban
// more stability fixes: new DISABLE_GETIMAGESIZE if your php crashes when loading LinkIcons: failing getimagesize in old phps; blockparser stabilized
//
// Revision 1.30  2004/06/25 14:29:12  rurban
// WikiGroup refactoring:
//   global group attached to user, code for not_current user.
//   improved helpers for special groups (avoid double invocations)
// new experimental config option ENABLE_XHTML_XML (fails with IE, and document.write())
// fixed a XHTML validation error on userprefs.tmpl
//
// Revision 1.29  2004/06/20 15:30:04  rurban
// get_class case-sensitivity issues
//
// Revision 1.28  2004/06/16 11:51:04  rurban
// fixed typo: undefined object #235
//
// Revision 1.27  2004/06/13 09:38:20  rurban
// isa() workaround, if stdlib.php is not loaded
//
// Revision 1.26  2004/06/02 18:01:45  rurban
// init global FileFinder to add proper include paths at startup
//   adds PHPWIKI_DIR if started from another dir, lib/pear also
// fix slashify for Windows
// fix USER_AUTH_POLICY=old, use only USER_AUTH_ORDER methods (besides HttpAuth)
//
// Revision 1.25  2004/06/02 10:18:36  rurban
// assert only if DEBUG is non-false
//
// Revision 1.24  2004/05/27 17:49:05  rurban
// renamed DB_Session to DbSession (in CVS also)
// added WikiDB->getParam and WikiDB->getAuthParam method to get rid of globals
// remove leading slash in error message
// added force_unlock parameter to File_Passwd (no return on stale locks)
// fixed adodb session AffectedRows
// added FileFinder helpers to unify local filenames and DATA_PATH names
// editpage.php: new edit toolbar javascript on ENABLE_EDIT_TOOLBAR
// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
