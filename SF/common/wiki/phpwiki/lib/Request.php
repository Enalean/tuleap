<?php // -*-php-*-
rcs_id('$Id$');
/*
 Copyright (C) 2002,2004 $ThePhpWikiProgrammingTeam
 
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

// backward compatibility for PHP < 4.2.0
if (!function_exists('ob_clean')) {
    function ob_clean() {
        ob_end_clean();
        ob_start();
    }
}

        
class Request {
        
    function Request() {
        $this->_fix_magic_quotes_gpc();
        $this->_fix_multipart_form_data();
        
        switch($this->get('REQUEST_METHOD')) {
        case 'GET':
        case 'HEAD':
            $this->args = &$GLOBALS['HTTP_GET_VARS'];
            break;
        case 'POST':
            $this->args = &$GLOBALS['HTTP_POST_VARS'];
            break;
        default:
            $this->args = array();
            break;
        }
        
        $this->session = new Request_SessionVars; 
        $this->cookies = new Request_CookieVars;
        
        if (ACCESS_LOG) {
            if (! is_writeable(ACCESS_LOG)) {
                trigger_error
                    (sprintf(_("%s is not writable."), _("The PhpWiki access log file"))
                    . "\n"
                    . sprintf(_("Please ensure that %s is writable, or redefine %s in index.php."),
                            sprintf(_("the file '%s'"), ACCESS_LOG),
                            'ACCESS_LOG')
                    , E_USER_NOTICE);
            }
            else
                $this->_log_entry = & new Request_AccessLogEntry($this,
                                                                ACCESS_LOG);
        }
        
        $GLOBALS['request'] = $this;
    }

    function get($key) {
        if (!empty($GLOBALS['HTTP_SERVER_VARS']))
            $vars = &$GLOBALS['HTTP_SERVER_VARS'];
        else // cgi or other servers than Apache
            $vars = &$GLOBALS['_ENV'];

        if (isset($vars[$key]))
            return $vars[$key];

        switch ($key) {
        case 'REMOTE_HOST':
            $addr = $vars['REMOTE_ADDR'];
            if (defined('ENABLE_REVERSE_DNS') && ENABLE_REVERSE_DNS)
                return $vars[$key] = gethostbyaddr($addr);
            else
                return $addr;
        default:
            return false;
        }
    }

    function getArg($key) {
        if (isset($this->args[$key]))
            return $this->args[$key];
        return false;
    }

    function getArgs () {
        return $this->args;
    }
    
    function setArg($key, $val) {
        if ($val === false)
            unset($this->args[$key]);
        else
            $this->args[$key] = $val;
    }
    
    // Well oh well. Do we really want to pass POST params back as GET?
    function getURLtoSelf($args = false, $exclude = array()) {
        $get_args = $this->args;
        if ($args)
            $get_args = array_merge($get_args, $args);

        // Err... good point...
        // sortby buttons
        if ($this->isPost()) {
            $exclude = array_merge($exclude, array('action','auth'));
            //$get_args = $args; // or only the provided
            /*
            trigger_error("Request::getURLtoSelf() should probably not be from POST",
                          E_USER_NOTICE);
            */
        }

        foreach ($exclude as $ex) {
            if (!empty($get_args[$ex])) unset($get_args[$ex]);
        }

        $pagename = $get_args['pagename'];
        unset ($get_args['pagename']);
        if (!empty($get_args['action']) and $get_args['action'] == 'browse')
            unset($get_args['action']);

        return WikiURL($pagename, $get_args);
    }

    function isPost () {
        return $this->get("REQUEST_METHOD") == "POST";
    }

    function isGetOrHead () {
        return in_array($this->get('REQUEST_METHOD'),
                        array('GET', 'HEAD'));
    }

    function httpVersion() {
        if (!preg_match('@HTTP\s*/\s*(\d+.\d+)@', $this->get('SERVER_PROTOCOL'), $m))
            return false;
        return (float) $m[1];
    }
    
    function redirect($url, $noreturn=true) {
        $bogus = defined('DISABLE_HTTP_REDIRECT') and DISABLE_HTTP_REDIRECT;
        
        if (!$bogus) {
            //header("Location: $url");
            /*
             * "302 Found" is not really meant to be sent in response
             * to a POST.  Worse still, according to (both HTTP 1.0
             * and 1.1) spec, the user, if it is sent, the user agent
             * is supposed to use the same method to fetch the
             * redirected URI as the original.
             *
             * That means if we redirect from a POST, the user-agent
             * supposed to generate another POST.  Not what we want.
             * (We do this after a page save after all.)
             *
             * Fortunately, most/all browsers don't do that.
             *
             * "303 See Other" is what we really want.  But it only
             * exists in HTTP/1.1
             *
             * FIXME: this is still not spec compliant for HTTP
             * version < 1.1.
             */
            $status = $this->httpVersion() >= 1.1 ? 303 : 302;

            $this->setStatus($status);
        }

        if ($noreturn) {
            include_once('lib/Template.php');
            $this->discardOutput();
            $tmpl = new Template('redirect', $this, array('REDIRECT_URL' => $url));
            $tmpl->printXML();
            $this->finish();
        }
        else if ($bogus) {
            return JavaScript("
              function redirect(url) {
                if (typeof location.replace == 'function')
                  location.replace(url);
                else if (typeof location.assign == 'function')
                  location.assign(url);
                else
                  window.location = url;
              }
              redirect('" . addslashes($url) . "')");
        }
    }

    /** Set validators for this response.
     *
     * This sets a (possibly incomplete) set of validators
     * for this response.
     *
     * The validator set can be extended using appendValidators().
     *
     * When you're all done setting and appending validators, you
     * must call checkValidators() to check them and set the
     * appropriate headers in the HTTP response.
     *
     * Example Usage:
     *  ...
     *  $request->setValidators(array('pagename' => $pagename,
     *                                '%mtime' => $rev->get('mtime')));
     *  ...
     *  // Wups... response content depends on $otherpage, too...
     *  $request->appendValidators(array('otherpage' => $otherpagerev->getPageName(),
     *                                   '%mtime' => $otherpagerev->get('mtime')));
     *  ...
     *  // After all validators have been set:
     *  $request->checkValidators();
     */
    function setValidators($validator_set) {
        if (is_array($validator_set))
            $validator_set = new HTTP_ValidatorSet($validator_set);
        $this->_validators = $validator_set;
    }
    
    /** Append more validators for this response. 
     *  i.e dependencies on other pages mtimes
     *  now it may be called in init also to simplify client code.
     */ 
    function appendValidators($validator_set) {
        if (!isset($this->_validators)) {
            $this->setValidators($validator_set);
            return;
        }
        $this->_validators->append($validator_set);
    }
    
    /** Check validators and set headers in HTTP response
     *
     * This sets the appropriate "Last-Modified" and "ETag"
     * headers in the HTTP response.
     *
     * Additionally, if the validators match any(all) conditional
     * headers in the HTTP request, this method will not return, but
     * instead will send "304 Not Modified" or "412 Precondition
     * Failed" (as appropriate) back to the client.
     */
    function checkValidators() {
        $validators = &$this->_validators;
        
        // Set validator headers
        /*if (($etag = $validators->getETag()) !== false)
            header("ETag: " . $etag->asString());
        if (($mtime = $validators->getModificationTime()) !== false)
            header("Last-Modified: " . Rfc1123DateTime($mtime));*/

        // Set cache control headers
        $this->cacheControl();

        if (CACHE_CONTROL == 'NONE')
            return;             // don't check conditionals...
        
        // Check conditional headers in request
        $status = $validators->checkConditionalRequest($this);
        if ($status) {
            // Return short response due to failed conditionals
            $this->setStatus($status);
            print "\n\n";
            $this->discardOutput();
            $this->finish();
            exit();
        }
    }

    /** Set the cache control headers in the HTTP response.
     */
    function cacheControl($strategy=CACHE_CONTROL, $max_age=CACHE_CONTROL_MAX_AGE) {
        if ($strategy == 'NONE') {
            $cache_control = "no-cache";
            $max_age = -20;
        }
        elseif ($strategy == 'ALLOW_STALE' && $max_age > 0) {
            $cache_control = sprintf("max-age=%d", $max_age);
        }
        else {
            $cache_control = "must-revalidate";
            $max_age = -20;
        }
        /*header("Cache-Control: $cache_control");
        header("Expires: " . Rfc1123DateTime(time() + $max_age));
        header("Vary: Cookie"); // FIXME: add more here?*/
    }
    
    function setStatus($status) {
        if (preg_match('|^HTTP/.*?\s(\d+)|i', $status, $m)) {
            //header($status);
            $status = $m[1];
        }
        else {
            $status = (integer) $status;
            $reason = array('200' => 'OK',
                            '302' => 'Found',
                            '303' => 'See Other',
                            '304' => 'Not Modified',
                            '400' => 'Bad Request',
                            '401' => 'Unauthorized',
                            '403' => 'Forbidden',
                            '404' => 'Not Found',
                            '412' => 'Precondition Failed');
            // FIXME: is it always okay to send HTTP/1.1 here, even for older clients?
            // header(sprintf("HTTP/1.1 %d %s", $status, $reason[$status]));
        }

        if (isset($this->_log_entry))
            $this->_log_entry->setStatus($status);
    }

    function buffer_output($compress = true) {
        if (defined('COMPRESS_OUTPUT')) {
            if (!COMPRESS_OUTPUT)
                $compress = false;
        }
        elseif (!function_exists('version_compare')
                || version_compare(phpversion(), '4.2.3', "<")) {
            $compress = false;
        }

        // Should we compress even when apache_note is not available?
        // sf.net bug #933183 and http://bugs.php.net/17557
        if (!function_exists('ob_gzhandler') or !function_exists('apache_note'))
            $compress = false;
        
        if ($compress) {
            ob_start('ob_gzhandler');
            /*
             * Attempt to prevent Apache from doing the dreaded double-gzip.
             *
             * It would be better if we could detect when apache was going
             * to zip for us, and then let it ... but I have yet to figure
             * out how to do that.
             */
            if (function_exists('apache_note'))
                @apache_note('no-gzip', 1);
        }
        else {
            // Now we alway buffer output.
            // This is so we can set HTTP headers (e.g. for redirect)
            // at any point.
            // FIXME: change the name of this method.
            ob_start();
        }
        $this->_is_buffering_output = true;
    }

    function discardOutput() {
        if (!empty($this->_is_buffering_output))
            ob_clean();
        else
            trigger_error("Not buffering output", E_USER_NOTICE);
    }
    
    function finish() {
        session_write_close();
        if (!empty($this->_is_buffering_output)) {
            //header(sprintf("Content-Length: %d", ob_get_length()));
            ob_end_flush();
        }
        //exit;
    }

    function getSessionVar($key) {
        return $this->session->get($key);
    }
    function setSessionVar($key, $val) {
        return $this->session->set($key, $val);
    }
    function deleteSessionVar($key) {
        return $this->session->delete($key);
    }

    function getCookieVar($key) {
        return $this->cookies->get($key);
    }
    function setCookieVar($key, $val, $lifetime_in_days = false, $path = false) {
        return $this->cookies->set($key, $val, $lifetime_in_days, $path);
    }
    function deleteCookieVar($key) {
        return $this->cookies->delete($key);
    }
    
    function getUploadedFile($key) {
        return Request_UploadedFile::getUploadedFile($key);
    }
    

    function _fix_magic_quotes_gpc() {
        $needs_fix = array('HTTP_POST_VARS',
                           'HTTP_GET_VARS',
                           'HTTP_COOKIE_VARS',
                           'HTTP_SERVER_VARS',
                           'HTTP_POST_FILES');
        
        // Fix magic quotes.
        if (get_magic_quotes_gpc()) {
            foreach ($needs_fix as $vars)
                $this->_stripslashes($GLOBALS[$vars]);
        }
    }

    function _stripslashes(&$var) {
        if (is_array($var)) {
            foreach ($var as $key => $val)
                $this->_stripslashes($var[$key]);
        }
        elseif (is_string($var))
            $var = stripslashes($var);
    }
    
    function _fix_multipart_form_data () {
        if (preg_match('|^multipart/form-data|', $this->get('CONTENT_TYPE')))
            $this->_strip_leading_nl($GLOBALS['HTTP_POST_VARS']);
    }
    
    function _strip_leading_nl(&$var) {
        if (is_array($var)) {
            foreach ($var as $key => $val)
                $this->_strip_leading_nl($var[$key]);
        }
        elseif (is_string($var))
            $var = preg_replace('|^\r?\n?|', '', $var);
    }
}

class Request_SessionVars {
    function Request_SessionVars() {
        // Prevent cacheing problems with IE 5
        //session_cache_limiter('none');
                                        
        //        session_start();
    }
    
    function get($key) {
        $vars = &$GLOBALS['HTTP_SESSION_VARS'];
        if (isset($vars[$key]))
            return $vars[$key];
        return false;
    }
    
    function set($key, $val) {
        $vars = &$GLOBALS['HTTP_SESSION_VARS'];
        if ($key == 'wiki_user') {
            if (DEBUG) {
	      if (!$val) {
	        trigger_error("delete user session",E_USER_WARNING);
	      } elseif (!$val->_level) {
	        trigger_error("lost level in session",E_USER_WARNING);
	      }
            }
	    if (is_object($val)) {
                $val->page   = $GLOBALS['request']->getArg('pagename');
                $val->action = $GLOBALS['request']->getArg('action');
                // sessiondata may not exceed a certain size!
                // otherwise it will get lost.
                unset($val->_HomePagehandle);
                unset($val->_auth_dbi);
	    }
        }
        if (!function_usable('get_cfg_var') or get_cfg_var('register_globals')) {
            // This is funky but necessary, at least in some PHP's
            $GLOBALS[$key] = $val;
        }
        $vars[$key] = $val;
        if (isset($_SESSION))
            $_SESSION[$key] = $val;
        //session_register($key);
    }
    
    function delete($key) {
        $vars = &$GLOBALS['HTTP_SESSION_VARS'];
        if (!function_usable('ini_get') or ini_get('register_globals'))
            unset($GLOBALS[$key]);
        if (DEBUG) trigger_error("delete session $key",E_USER_WARNING);
        unset($vars[$key]);
        //session_unregister($key);
    }
}

class Request_CookieVars {
    
    function get($key) {
        $vars = &$GLOBALS['HTTP_COOKIE_VARS'];
        if (isset($vars[$key])) {
            @$val = unserialize(base64_decode($vars[$key]));
            if (!empty($val))
                return $val;
            @$val = urldecode($vars[$key]);
            if (!empty($val))
                return $val;
        }
        return false;
    }

    function get_old($key) {
        $vars = &$GLOBALS['HTTP_COOKIE_VARS'];
        if (isset($vars[$key])) {
            @$val = unserialize(base64_decode($vars[$key]));
            if (!empty($val))
                return $val;
            @$val = unserialize($vars[$key]);
            if (!empty($val))
                return $val;
            @$val = $vars[$key];
            if (!empty($val))
                return $val;
        }
        return false;
    }

    function set($key, $val, $persist_days = false, $path = false) {
    	// if already defined, ignore
    	if (defined('MAIN_setUser') and $key = 'WIKI_ID') return;
        $vars = &$GLOBALS['HTTP_COOKIE_VARS'];
        if (is_numeric($persist_days)) {
            $expires = time() + (24 * 3600) * $persist_days;
        }
        else {
            $expires = 0;
        }
        if (is_array($val) or is_object($val))
            $packedval = base64_encode(serialize($val));
        else
            $packedval = urlencode($val);
        $vars[$key] = $packedval;
        /*        if ($path)
            setcookie($key, $packedval, $expires, $path);
        else
            setcookie($key, $packedval, $expires);*/
    }
    
    function delete($key) {
        static $deleted = array();
        if (isset($deleted[$key])) return;
        $vars = &$GLOBALS['HTTP_COOKIE_VARS'];
        //setcookie($key,'',0);
        //setcookie($key,'',0,defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '/');
        unset($vars[$key]);
        unset($GLOBALS['HTTP_COOKIE_VARS'][$key]);
        $deleted[$key] = 1;
    }
}

/* Win32 Note:
   [\winnt\php.ini]
   You must set "upload_tmp_dir" = "/tmp/" or "C:/tmp/"
   Best on the same drive as apache, with forward slashes 
   and with ending slash!
   Otherwise "\\" => "" and the uploaded file will not be found.
*/
class Request_UploadedFile {
    function getUploadedFile($postname) {
        global $HTTP_POST_FILES;
        
        if (!isset($HTTP_POST_FILES[$postname]))
            return false;
        
        $fileinfo = &$HTTP_POST_FILES[$postname];
        if ($fileinfo['error']) {
            trigger_error("Upload error: #" . $fileinfo['error'],
                          E_USER_ERROR);
            return false;
        }

        // With windows/php 4.2.1 is_uploaded_file() always returns false.
        if (!is_uploaded_file($fileinfo['tmp_name'])) {
            if (isWindows()) {
                if (!$tmp_file = get_cfg_var('upload_tmp_dir')) {
                    $tmp_file = dirname(tempnam('', ''));
                }
                $tmp_file .= '/' . basename($fileinfo['tmp_name']);
                /* but ending slash in php.ini upload_tmp_dir is required. */
                if (ereg_replace('/+', '/', $tmp_file) != $fileinfo['tmp_name']) {
                    trigger_error(sprintf("Uploaded tmpfile illegal: %s != %s",$tmp_file, $fileinfo['tmp_name']),
                                  E_USER_ERROR);
                    return false;
                } else {
                    trigger_error(sprintf("Workaround for PHP/Windows is_uploaded_file() problem for %s.",
                                          $fileinfo['tmp_name'])."\n".
            	                  "Probably illegal TEMP environment setting.",E_USER_NOTICE);
                }
            } else {
              trigger_error(sprintf("Uploaded tmpfile %s not found.",$fileinfo['tmp_name'])."\n".
                           " Probably illegal TEMP environment setting.",
                          E_USER_WARNING);
            }
        }
        return new Request_UploadedFile($fileinfo);
    }
    
    function Request_UploadedFile($fileinfo) {
        $this->_info = $fileinfo;
    }

    function getSize() {
        return $this->_info['size'];
    }

    function getName() {
        return $this->_info['name'];
    }

    function getType() {
        return $this->_info['type'];
    }

    function getTmpName() {
        return $this->_info['tmp_name'];
    }

    function open() {
        if ( ($fd = fopen($this->_info['tmp_name'], "rb")) ) {
            if ($this->getSize() < filesize($this->_info['tmp_name'])) {
                // FIXME: Some PHP's (or is it some browsers?) put
                //    HTTP/MIME headers in the file body, some don't.
                //
                // At least, I think that's the case.  I know I used
                // to need this code, now I don't.
                //
                // This code is more-or-less untested currently.
                //
                // Dump HTTP headers.
                while ( ($header = fgets($fd, 4096)) ) {
                    if (trim($header) == '') {
                        break;
                    }
                    else if (!preg_match('/^content-(length|type):/i', $header)) {
                        rewind($fd);
                        break;
                    }
                }
            }
        }
        return $fd;
    }

    function getContents() {
        $fd = $this->open();
        $data = fread($fd, $this->getSize());
        fclose($fd);
        return $data;
    }
}

/**
 * Create NCSA "combined" log entry for current request.
 */
class Request_AccessLogEntry
{
    /**
     * Constructor.
     *
     * The log entry will be automatically appended to the log file
     * when the current request terminates.
     *
     * If you want to modify a Request_AccessLogEntry before it gets
     * written (e.g. via the setStatus and setSize methods) you should
     * use an '&' on the constructor, so that you're working with the
     * original (rather than a copy) object.
     *
     * <pre>
     *    $log_entry = & new Request_AccessLogEntry($req, "/tmp/wiki_access_log");
     *    $log_entry->setStatus(401);
     * </pre>
     *
     *
     * @param $request object  Request object for current request.
     * @param $logfile string  Log file name.
     */
    function Request_AccessLogEntry (&$request, $logfile) {
        $this->logfile = $logfile;
        
        $this->host  = $request->get('REMOTE_HOST');
        $this->ident = $request->get('REMOTE_IDENT');
        if (!$this->ident)
            $this->ident = '-';
        $this->user = '-';        // FIXME: get logged-in user name
        $this->time = time();
        $this->request = join(' ', array($request->get('REQUEST_METHOD'),
                                         $request->get('REQUEST_URI'),
                                         $request->get('SERVER_PROTOCOL')));
        $this->status = 200;
        $this->size = 0;
        $this->referer = (string) $request->get('HTTP_REFERER');
        $this->user_agent = (string) $request->get('HTTP_USER_AGENT');

        global $Request_AccessLogEntry_entries;
        if (!isset($Request_AccessLogEntry_entries)) {
            register_shutdown_function("Request_AccessLogEntry_shutdown_function");
        }
        $Request_AccessLogEntry_entries[] = &$this;
    }

    /**
     * Set result status code.
     *
     * @param $status integer  HTTP status code.
     */
    function setStatus ($status) {
        $this->status = $status;
    }
    
    /**
     * Set response size.
     *
     * @param $size integer
     */
    function setSize ($size) {
        $this->size = $size;
    }
    
    /**
     * Get time zone offset.
     *
     * This is a static member function.
     *
     * @param $time integer Unix timestamp (defaults to current time).
     * @return string Zone offset, e.g. "-0800" for PST.
     */
    function _zone_offset ($time = false) {
        if (!$time)
            $time = time();
        $offset = date("Z", $time);
        $negoffset = "";
        if ($offset < 0) {
            $negoffset = "-";
            $offset = -$offset;
        }
        $offhours = floor($offset / 3600);
        $offmins  = $offset / 60 - $offhours * 60;
        return sprintf("%s%02d%02d", $negoffset, $offhours, $offmins);
    }

    /**
     * Format time in NCSA format.
     *
     * This is a static member function.
     *
     * @param $time integer Unix timestamp (defaults to current time).
     * @return string Formatted date & time.
     */
    function _ncsa_time($time = false) {
        if (!$time)
            $time = time();

        return date("d/M/Y:H:i:s", $time) .
            " " . $this->_zone_offset();
    }

    /**
     * Write entry to log file.
     */
    function write() {
        $entry = sprintf('%s %s %s [%s] "%s" %d %d "%s" "%s"',
                         $this->host, $this->ident, $this->user,
                         $this->_ncsa_time($this->time),
                         $this->request, $this->status, $this->size,
                         $this->referer, $this->user_agent);

        //Error log doesn't provide locking.
        //error_log("$entry\n", 3, $this->logfile);

        // Alternate method
        if (($fp = fopen($this->logfile, "a"))) {
            flock($fp, LOCK_EX);
            fputs($fp, "$entry\n");
            fclose($fp);
        }
    }
}

/**
 * Shutdown callback.
 *
 * @access private
 * @see Request_AccessLogEntry
 */
function Request_AccessLogEntry_shutdown_function ()
{
    global $Request_AccessLogEntry_entries;
    
    foreach ($Request_AccessLogEntry_entries as $entry) {
        $entry->write();
    }
    unset($Request_AccessLogEntry_entries);
}


class HTTP_ETag {
    function HTTP_ETag($val, $is_weak=false) {
        $this->_val = hash($val);
        $this->_weak = $is_weak;
    }

    /** Comparison
     *
     * Strong comparison: If either (or both) tag is weak, they
     *  are not equal.
     */
    function equals($that, $strong_match=false) {
        if ($this->_val != $that->_val)
            return false;
        if ($strong_match and ($this->_weak or $that->_weak))
            return false;
        return true;
    }


    function asString() {
        $quoted = '"' . addslashes($this->_val) . '"';
        return $this->_weak ? "W/$quoted" : $quoted;
    }

    /** Parse tag from header.
     *
     * This is a static member function.
     */
    function parse($strval) {
        if (!preg_match(':^(W/)?"(.+)"$:i', trim($strval), $m))
            return false;       // parse failed
        list(,$weak,$str) = $m;
        return new HTTP_ETag(stripslashes($str), $weak);
    }

    function matches($taglist, $strong_match=false) {
        $taglist = trim($taglist);

        if ($taglist == '*') {
            if ($strong_match)
                return ! $this->_weak;
            else
                return true;
        }

        while (preg_match('@^(W/)?"((?:\\\\.|[^"])*)"\s*,?\s*@i',
                          $taglist, $m)) {
            list($match, $weak, $str) = $m;
            $taglist = substr($taglist, strlen($match));
            $tag = new HTTP_ETag(stripslashes($str), $weak);
            if ($this->equals($tag, $strong_match)) {
                return true;
            }
        }
        return false;
    }
}

// Possible results from the HTTP_ValidatorSet::_check*() methods.
// (Higher numerical values take precedence.)
define ('_HTTP_VAL_PASS', 0);   // Test is irrelevant
define ('_HTTP_VAL_NOT_MODIFIED', 1); // Test passed, content not changed
define ('_HTTP_VAL_MODIFIED', 2); // Test failed, content changed
define ('_HTTP_VAL_FAILED', 3); // Precondition failed.

class HTTP_ValidatorSet {
    function HTTP_ValidatorSet($validators) {
        $this->_mtime = $this->_weak = false;
        $this->_tag = array();
        
        foreach ($validators as $key => $val) {
            if ($key == '%mtime') {
                $this->_mtime = $val;
            }
            elseif ($key == '%weak') {
                if ($val)
                    $this->_weak = true;
            }
            else {
                $this->_tag[$key] = $val;
            }
        }
    }

    function append($that) {
        if (is_array($that))
            $that = new HTTP_ValidatorSet($that);

        // Pick the most recent mtime
        if (isset($that->_mtime))
            if (!isset($this->_mtime) || $that->_mtime > $this->_mtime)
                $this->_mtime = $that->_mtime;

        // If either is weak, we're weak
        if (!empty($that->_weak))
            $this->_weak = true;

        $this->_tag = array_merge($this->_tag, $that->_tag);
    }

    function getETag() {
        if (! $this->_tag)
            return false;
        return new HTTP_ETag($this->_tag, $this->_weak);
    }

    function getModificationTime() {
        return $this->_mtime;
    }
    
    function checkConditionalRequest (&$request) {
        $result = max($this->_checkIfUnmodifiedSince($request),
                      $this->_checkIfModifiedSince($request),
                      $this->_checkIfMatch($request),
                      $this->_checkIfNoneMatch($request));

        if ($result == _HTTP_VAL_PASS || $result == _HTTP_VAL_MODIFIED)
            return false;       // "please proceed with normal processing"
        elseif ($result == _HTTP_VAL_FAILED)
            return 412;         // "412 Precondition Failed"
        elseif ($result == _HTTP_VAL_NOT_MODIFIED)
            return 304;         // "304 Not Modified"

        trigger_error("Ack, shouldn't get here", E_USER_ERROR);
        return false;
    }

    function _checkIfUnmodifiedSince(&$request) {
        if ($this->_mtime !== false) {
            $since = ParseRfc1123DateTime($request->get("HTTP_IF_UNMODIFIED_SINCE"));
            if ($since !== false && $this->_mtime > $since)
                return _HTTP_VAL_FAILED;
        }
        return _HTTP_VAL_PASS;
    }

    function _checkIfModifiedSince(&$request) {
        if ($this->_mtime !== false and $request->isGetOrHead()) {
            $since = ParseRfc1123DateTime($request->get("HTTP_IF_MODIFIED_SINCE"));
            if ($since !== false) {
                if ($this->_mtime <= $since)
                    return _HTTP_VAL_NOT_MODIFIED;
                return _HTTP_VAL_MODIFIED;
            }
        }
        return _HTTP_VAL_PASS;
    }

    function _checkIfMatch(&$request) {
        if ($this->_tag && ($taglist = $request->get("HTTP_IF_MATCH"))) {
            $tag = $this->getETag();
            if (!$tag->matches($taglist, 'strong'))
                return _HTTP_VAL_FAILED;
        }
        return _HTTP_VAL_PASS;
    }

    function _checkIfNoneMatch(&$request) {
        if ($this->_tag && ($taglist = $request->get("HTTP_IF_NONE_MATCH"))) {
            $tag = $this->getETag();
            $strong_compare = ! $request->isGetOrHead();
            if ($taglist) {
                if ($tag->matches($taglist, $strong_compare)) {
                    if ($request->isGetOrHead())
                        return _HTTP_VAL_NOT_MODIFIED;
                    else
                        return _HTTP_VAL_FAILED;
                }
                return _HTTP_VAL_MODIFIED;
            }
        }
        return _HTTP_VAL_PASS;
    }
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
// Revision 1.54  2004/05/04 22:34:25  rurban
// more pdf support
//
// Revision 1.53  2004/05/03 21:57:47  rurban
// locale updates: we previously lost some words because of wrong strings in
//   PhotoAlbum, german rewording.
// fixed $_SESSION registering (lost session vars, esp. prefs)
// fixed ending slash in listAvailableLanguages/Themes
//
// Revision 1.52  2004/05/03 13:16:47  rurban
// fixed UserPreferences update, esp for boolean and int
//
// Revision 1.51  2004/05/02 21:26:38  rurban
// limit user session data (HomePageHandle and auth_dbi have to invalidated anyway)
//   because they will not survive db sessions, if too large.
// extended action=upgrade
// some WikiTranslation button work
// revert WIKIAUTH_UNOBTAINABLE (need it for main.php)
// some temp. session debug statements
//
// Revision 1.50  2004/04/29 19:39:44  rurban
// special support for formatted plugins (one-liners)
//   like <small><plugin BlaBla ></small>
// iter->asArray() helper for PopularNearby
// db_session for older php's (no &func() allowed)
//
// Revision 1.49  2004/04/26 20:44:34  rurban
// locking table specific for better databases
//
// Revision 1.48  2004/04/13 09:13:50  rurban
// sf.net bug #933183 and http://bugs.php.net/17557
// disable ob_gzhandler if apache_note cannot be used.
//   (conservative until we find why)
//
// Revision 1.47  2004/04/02 15:06:55  rurban
// fixed a nasty ADODB_mysql session update bug
// improved UserPreferences layout (tabled hints)
// fixed UserPreferences auth handling
// improved auth stability
// improved old cookie handling: fixed deletion of old cookies with paths
//
// Revision 1.46  2004/03/30 02:14:03  rurban
// fixed yet another Prefs bug
// added generic PearDb_iter
// $request->appendValidators no so strict as before
// added some box plugin methods
// PageList commalist for condensed output
//
// Revision 1.45  2004/03/24 19:39:02  rurban
// php5 workaround code (plus some interim debugging code in XmlElement)
//   php5 doesn't work yet with the current XmlElement class constructors,
//   WikiUserNew does work better than php4.
// rewrote WikiUserNew user upgrading to ease php5 update
// fixed pref handling in WikiUserNew
// added Email Notification
// added simple Email verification
// removed emailVerify userpref subclass: just a email property
// changed pref binary storage layout: numarray => hash of non default values
// print optimize message only if really done.
// forced new cookie policy: delete pref cookies, use only WIKI_ID as plain string.
//   prefs should be stored in db or homepage, besides the current session.
//
// Revision 1.44  2004/03/14 16:26:22  rurban
// copyright line
//
// Revision 1.43  2004/03/12 20:59:17  rurban
// important cookie fix by Konstantin Zadorozhny
// new editpage feature: JS_SEARCHREPLACE
//
// Revision 1.42  2004/03/10 15:38:48  rurban
// store current user->page and ->action in session for WhoIsOnline
// better WhoIsOnline icon
// fixed WhoIsOnline warnings
//
// Revision 1.41  2004/02/27 01:25:14  rurban
// Workarounds for upload handling
//
// Revision 1.40  2004/02/26 01:39:51  rurban
// safer code
//
// Revision 1.39  2004/02/24 15:14:57  rurban
// fixed action=upload problems on Win32, and remove Merge Edit buttons: file does not exist anymore
//
// Revision 1.38  2004/01/25 10:26:02  rurban
// fixed bug [ 541193 ] HTTP_SERVER_VARS are Apache specific
// http://sourceforge.net/tracker/index.php?func=detail&aid=541193&group_id=6121&atid=106121
// CGI and other servers than apache populate _ENV and not _SERVER
//
// Revision 1.37  2003/12/26 06:41:16  carstenklapp
// Bugfix: Try to defer OS errors about session.save_path and ACCESS_LOG,
// so they don't prevent IE from partially (or not at all) rendering the
// page. This should help a little for the IE user who encounters trouble
// when setting up a new PhpWiki for the first time.
//

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:   
?>
