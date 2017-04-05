<?php // -*-php-*-
rcs_id('$Id: Request.php,v 1.100 2006/01/17 18:57:09 uckelman Exp $');
/*
 Copyright (C) 2002,2004,2005 $ThePhpWikiProgrammingTeam
 
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
            $this->args = &$_GET;
            break;
        case 'POST':
            $this->args = &$_POST;
            break;
        default:
            $this->args = array();
            break;
        }
        
        $this->session = new Request_SessionVars; 

        if (ACCESS_LOG or ACCESS_LOG_SQL) {
            $this->_accesslog = new Request_AccessLog(ACCESS_LOG, ACCESS_LOG_SQL);
        }
        
        $GLOBALS['request'] = $this;
    }

    function get($key) {
        if (!empty($_SERVER))
            $vars = &$_SERVER;
        else // cgi or other servers than Apache
            $vars = &$_ENV;

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
    
    /* Redirects after edit may fail if no theme signature image is defined. 
     * Set DISABLE_HTTP_REDIRECT = true then.
     */
    function redirect($url, $noreturn = true) {
        $bogus = defined('DISABLE_HTTP_REDIRECT') && DISABLE_HTTP_REDIRECT;
        
        if (!$bogus) {
            header("Location: $url");
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
            $this->discardOutput(); // This might print the gzip headers. Not good.
            $this->buffer_output(false);
            
            include_once('lib/Template.php');
            $tmpl = new Template('redirect', $this, array('REDIRECT_URL' => $url));
            $tmpl->printXML();
            $this->finish();
        }
        elseif ($bogus) {
            // Safari needs window.location.href = targeturl
            return JavaScript("
              function redirect(url) {
                if (typeof location.replace == 'function')
                  location.replace(url);
                else if (typeof location.assign == 'function')
                  location.assign(url);
                else if (self.location.href)
                  self.location.href = url;
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
        if ($this->_is_buffering_output or !headers_sent()) {
            if (($etag = $validators->getETag()) !== false)
                header("ETag: " . $etag->asString());
            if (($mtime = $validators->getModificationTime()) !== false)
                header("Last-Modified: " . Rfc1123DateTime($mtime));

            // Set cache control headers
            $this->cacheControl();
        }

        if (CACHE_CONTROL == 'NO_CACHE')
            return;             // don't check conditionals...
        
        // Check conditional headers in request
        $status = $validators->checkConditionalRequest($this);
        if ($status) {
            // Return short response due to failed conditionals
            $this->setStatus($status);
            echo "\n\n";
            $this->discardOutput();
            $this->finish();
            exit();
        }
    }

    /** Set the cache control headers in the HTTP response.
     */
    function cacheControl($strategy=CACHE_CONTROL, $max_age=CACHE_CONTROL_MAX_AGE) {
        if ($strategy == 'NO_CACHE') {
            $cache_control = "no-cache"; // better set private. See Pear HTTP_Header
            $max_age = -20;
        }
        elseif ($strategy == 'ALLOW_STALE' && $max_age > 0) {
            $cache_control = sprintf("max-age=%d", $max_age);
        }
        else {
            $cache_control = "must-revalidate";
            $max_age = -20;
        }
        header("Cache-Control: $cache_control");
        header("Expires: " . Rfc1123DateTime(time() + $max_age));
        header("Vary: Cookie"); // FIXME: add more here?
    }
    
    function setStatus($status) {
        if (preg_match('|^HTTP/.*?\s(\d+)|i', $status, $m)) {
            header($status);
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
            header(sprintf("HTTP/1.1 %d %s", $status, $reason[$status]));
        }

        if (isset($this->_log_entry))
            $this->_log_entry->setStatus($status);
    }

    function buffer_output($compress = true) {
        // FIXME: disables sessions (some byte before all headers_sent())
        /*if (defined('USECACHE') and !USECACHE) {
            $this->_is_buffering_output = false;
            return;
        }*/
        if (defined('COMPRESS_OUTPUT')) {
            if (!COMPRESS_OUTPUT)
                $compress = false;
        }
        elseif (!check_php_version(4,2,3))
            $compress = false;
        elseif (isCGI()) // necessary?
            $compress = false;
            
        if ($this->getArg('start_debug'))
            $compress = false;
        
        // Should we compress even when apache_note is not available?
        // sf.net bug #933183 and http://bugs.php.net/17557
        // This effectively eliminates CGI, but all other servers also. hmm.
        if ($compress 
            and (!function_exists('ob_gzhandler') 
                 or !function_exists('apache_note'))) 
            $compress = false;
            
        // "output handler 'ob_gzhandler' cannot be used twice"
        // http://www.php.net/ob_gzhandler
        if ($compress and ini_get("zlib.output_compression"))
            $compress = false;

        // New: we check for the client Accept-Encoding: "gzip" presence also
        // This should eliminate a lot or reported problems.
        if ($compress
            and (!$this->get("HTTP_ACCEPT_ENCODING")
                 or !strstr($this->get("HTTP_ACCEPT_ENCODING"), "gzip")))
            $compress = false;

        // Most RSS clients are NOT(!) application/xml gzip compatible yet. 
        // Even if they are sending the accept-encoding gzip header!
        // wget is, Mozilla, and MSIE no.
        // Of the RSS readers only MagpieRSS 0.5.2 is. http://www.rssgov.com/rssparsers.html
        // See also http://phpwiki.sourceforge.net/phpwiki/KnownBugs
        if ($compress 
            and $this->getArg('format') 
            and strstr($this->getArg('format'), 'rss'))
            $compress = false;

        if ($compress) {
            ob_start('phpwiki_gzhandler');
            
            // TODO: dont send a length or get the gzip'ed data length.
            $this->_is_compressing_output = true; 
            header("Content-Encoding: gzip");
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
            $this->_is_compressing_output = false;
        }
        $this->_is_buffering_output = true;
        $this->_ob_get_length = 0;
    }

    function discardOutput() {
        if (!empty($this->_is_buffering_output)) {
            ob_clean();
            $this->_is_buffering_output = false;
        } else {
            trigger_error("Not buffering output", E_USER_NOTICE);
        }
    }

    /** 
     * Longer texts need too much memory on tiny or memory-limit=8MB systems.
     * We might want to flush our buffer and restart again.
     * (This would be fine if php would release its memory)
     * Note that this must not be called inside Template expansion or other 
     * sections with ob_buffering.
     */
    function chunkOutput() {
        if (!empty($this->_is_buffering_output) or 
            (function_exists('ob_get_level') and @ob_get_level())) {
            $this->_do_chunked_output = true;
            if (empty($this->_ob_get_length)) $this->_ob_get_length = 0;
            $this->_ob_get_length += ob_get_length();
            while (@ob_end_flush());
            ob_end_clean();
            ob_start();
        }
    }

    function finish() {
    	$this->_finishing = true;
        if (!empty($this->_accesslog)) {
            $this->_accesslog->push($this);
            if (empty($this->_do_chunked_output) and empty($this->_ob_get_length))
                $this->_ob_get_length = ob_get_length();
            $this->_accesslog->setSize($this->_ob_get_length);
            global $RUNTIMER;
            if ($RUNTIMER) $this->_accesslog->setDuration($RUNTIMER->getTime());
            // sql logging must be done before the db is closed.
            if (isset($this->_accesslog->logtable))
                $this->_accesslog->write_sql();
        }
        
        if (!empty($this->_is_buffering_output)) {
            /* This cannot work because it might destroy xml markup */
            /*
            if (0 and $GLOBALS['SearchHighLightQuery'] and check_php_version(4,2)) {
                $html = str_replace($GLOBALS['SearchHighLightQuery'],
                                    '<span class="search-term">'.$GLOBALS['SearchHighLightQuery'].'</span>',
                                    ob_get_contents());
                ob_clean();
                header(sprintf("Content-Length: %d", strlen($html)));
                echo $html;
            } else {
            */
            // if _is_compressing_output then ob_get_length() returns 
            // the uncompressed length, not the gzip'ed as required.
	    if (!headers_sent() and !$this->_is_compressing_output) {
		if (empty($this->_do_chunked_output)) {
		    $this->_ob_get_length = ob_get_length();
		}
		header(sprintf("Content-Length: %d", $this->_ob_get_length));
            }
            $this->_is_buffering_output = false;
	}

        while (@ob_end_flush()); // hmm. there's some error in redirect
        session_write_close();
        if (!empty($this->_dbi)) {
            $this->_dbi->close();
            unset($this->_dbi);
        }

        exit;
    }

    function getSessionVar($key) {
        return $this->session->get($key);
    }
    function setSessionVar($key, $val) {
        if ($key == 'wiki_user') {
            if (empty($val->page))
                $val->page = $this->getArg('pagename');
            if (empty($val->action))
                $val->action = $this->getArg('action');
            // avoid recursive objects and session resource handles
            // avoid overlarge session data (max 4000 byte!)
            if (isset($val->_group)) {
                unset($val->_group->_request);
                unset($val->_group->user);
            }
            if (ENABLE_USER_NEW) {
                unset($val->_HomePagehandle);
                unset($val->_auth_dbi);
            } else {
                unset($val->_dbi);
                unset($val->_authdbi);
                unset($val->_homepage);
                unset($val->_request);
            }
        }
        return $this->session->set($key, $val);
    }
    function deleteSessionVar($key) {
        return $this->session->delete($key);
    }
    
    function getUploadedFile($key) {
        return Request_UploadedFile::getUploadedFile($key);
    }
    

    function _fix_magic_quotes_gpc() {
        $needs_fix = array('_POST',
                           '_GET',
                           '_COOKIE',
                           '_SERVER',
                           '_FILES');
        
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
            $this->_strip_leading_nl($_POST);
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
        session_cache_limiter('none');
                                        
        // Avoid to get a notice if session is already started,
        // for example if session.auto_start is activated
        if (!session_id())
            session_start();
    }
    
    function get($key) {
        if (isset($_SESSION[$key]))
            return $_SESSION[$key];
        return false;
    }
    
    function set($key, $val) {
        if (!function_usable('get_cfg_var') or get_cfg_var('register_globals')) {
            // This is funky but necessary, at least in some PHP's
            $GLOBALS[$key] = $val;
        }
        $_SESSION[$key] = $val;
    }
    
    function delete($key) {
        if (!function_usable('ini_get') or ini_get('register_globals'))
            unset($GLOBALS[$key]);
        if (DEBUG) trigger_error("delete session $key", E_USER_WARNING);
        unset($_SESSION[$key]);
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

        // Against php5 with !ini_get('register-long-arrays'). See Bug #1180115
        if (!isset($_FILES[$postname]))
            return false;
        
        $fileinfo =& $_FILES[$postname];
        if ($fileinfo['error']) {
            // See https://sourceforge.net/forum/message.php?msg_id=3093651
            $err = (int) $fileinfo['error'];
            // errmsgs by Shilad Sen
            switch ($err) {
            case 1:
                trigger_error(_("Upload error: file too big"), E_USER_WARNING);
                break;
            case 2:
                trigger_error(_("Upload error: file too big"), E_USER_WARNING);
                break;
            case 3:
                trigger_error(_("Upload error: file only partially recieved"), E_USER_WARNING);
                break;
            case 4:
                trigger_error(_("Upload error: no file selected"), E_USER_WARNING);
                break;
            default:
                trigger_error(_("Upload error: unknown error #") . $err, E_USER_WARNING);
            }
            return false;
        }

        // With windows/php 4.2.1 is_uploaded_file() always returns false.
        // Be sure that upload_tmp_dir ends with a slash!
        if (!is_uploaded_file($fileinfo['tmp_name'])) {
            if (isWindows()) {
                if (!$tmp_file = get_cfg_var('upload_tmp_dir')) {
                    $tmp_file = dirname(tempnam('', ''));
                }
                $tmp_file .= '/' . basename($fileinfo['tmp_name']);
                /* but ending slash in php.ini upload_tmp_dir is required. */
                if (realpath(ereg_replace('/+', '/', $tmp_file)) != realpath($fileinfo['tmp_name'])) {
                    trigger_error(sprintf("Uploaded tmpfile illegal: %s != %s.",$tmp_file, $fileinfo['tmp_name']).
                    	          "\n".
                    	          "Probably illegal TEMP environment or upload_tmp_dir setting.",
                                  E_USER_ERROR);
                    return false;
                } else {
                    /*
                    trigger_error(sprintf("Workaround for PHP/Windows is_uploaded_file() problem for %s.",
                                          $fileinfo['tmp_name'])."\n".
            	                  "Probably illegal TEMP environment or upload_tmp_dir setting.", 
            	                  E_USER_NOTICE);
            	    */
            	    ;
                }
            } else {
              trigger_error(sprintf("Uploaded tmpfile %s not found.", $fileinfo['tmp_name'])."\n".
                           " Probably illegal TEMP environment or upload_tmp_dir setting.",
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
 * Also needed for advanced spam prevention.
 * global object holding global state (sql or file, entries, to dump)
 */
class Request_AccessLog {
    /**
     * @param $logfile string  Log file name.
     */
    function Request_AccessLog ($logfile, $do_sql = false) {
        //global $request; // request not yet initialized!

        $this->logfile = $logfile;
        if ($logfile and !is_writeable($logfile)) {
            trigger_error
                (sprintf(_("%s is not writable."), _("The PhpWiki access log file"))
                 . "\n"
                 . sprintf(_("Please ensure that %s is writable, or redefine %s in config/config.ini."),
                           sprintf(_("the file '%s'"), ACCESS_LOG),
                           'ACCESS_LOG')
                 , E_USER_NOTICE);
        }
        //$request->_accesslog =& $this;
        //if (empty($request->_accesslog->entries))
        register_shutdown_function("Request_AccessLogEntry_shutdown_function");
        
        if ($do_sql) {
            global $DBParams;
            if (!in_array($DBParams['dbtype'], array('SQL','ADODB'))) {
                trigger_error("Unsupported database backend for ACCESS_LOG_SQL.\nNeed DATABASE_TYPE=SQL or ADODB");
            } else {
                //$this->_dbi =& $request->_dbi;
                $this->logtable = (!empty($DBParams['prefix']) ? $DBParams['prefix'] : '')."accesslog";
            }
        }
        $this->entries = array();
        $this->entries[] = new Request_AccessLogEntry($this);
    }

    function _do($cmd, &$arg) {
        if ($this->entries)
            for ($i=0; $i < count($this->entries);$i++)
                $this->entries[$i]->$cmd($arg);
    }
    function push(&$request)   { $this->_do('push',$request); }
    function setSize($arg)     { $this->_do('setSize',$arg); }
    function setStatus($arg)   { $this->_do('setStatus',$arg); }
    function setDuration($arg) { $this->_do('setDuration',$arg); }

    /**
     * Read sequentially all previous entries from the beginning.
     * while ($logentry = Request_AccessLogEntry::read()) ;
     * For internal log analyzers: RecentReferrers, WikiAccessRestrictions
     */
    function read() {
        return $this->logtable ? $this->read_sql() : $this->read_file();
    }

    /**
     * Return iterator of referer items reverse sorted (latest first).
     */
    function get_referer($limit=15, $external_only=false) {
        if ($external_only) { // see stdlin.php:isExternalReferrer()
            $base = SERVER_URL;
            $blen = strlen($base);
        }
        if (!empty($this->_dbi)) {
            // check same hosts in referer and request and remove them
            $ext_where = " AND LEFT(referer,$blen) <> ".$this->_dbi->quote($base)
                ." AND LEFT(referer,$blen) <> LEFT(CONCAT(".$this->_dbi->quote(SERVER_URL).",request_uri),$blen)";
            return $this->_read_sql_query("(referer <>'' AND NOT(ISNULL(referer)))"
                                          .($external_only ? $ext_where : '')
                                          ." ORDER BY time_stamp DESC"
                                          .($limit ? " LIMIT $limit" : ""));
        } else {
            $iter = new WikiDB_Array_generic_iter(0);
            $logs =& $iter->_array;
            while ($logentry = $this->read_file()) {
                if (!empty($logentry->referer)
                    and (!$external_only or (substr($logentry->referer,0,$blen) != $base)))
                {
                    $iter->_array[] = $logentry;
                    if ($limit and count($logs) > $limit)
                        array_shift($logs);
                }
            }
            $logs = array_reverse($logs);
            $logs = array_slice($logs,0,min($limit,count($logs)));
            return $iter;
        }
    }

    /**
     * Return iterator of matching host items reverse sorted (latest first).
     */
    function get_host($host, $since_minutes=20) {
        if ($this->logtable) {
            // mysql specific only:
            return $this->read_sql("request_host=".$this->_dbi->quote($host)." AND time_stamp > ". (time()-$since_minutes*60) 
                            ." ORDER BY time_stamp DESC");
        } else {
            $iter = new WikiDB_Array_generic_iter();
            $logs =& $iter->_array;
            $logentry = new Request_AccessLogEntry($this);
            while ($logentry->read_file()) {
                if (!empty($logentry->referer)) {
                    $iter->_array[] = $logentry;
                    if ($limit and count($logs) > $limit)
                        array_shift($logs);
                    $logentry = new Request_AccessLogEntry($this);
                }
            }
            $logs = array_reverse($logs);
            $logs = array_slice($logs,0,min($limit,count($logs)));
            return $iter;
        }
    }

    /**
     * Read sequentially all previous entries from log file.
     */
    function read_file() {
        global $request;
        if ($this->logfile) $this->logfile = ACCESS_LOG; // support Request_AccessLog::read

        if (empty($this->reader))       // start at the beginning
            $this->reader = fopen($this->logfile, "r");
        if ($s = fgets($this->reader)) {
            $entry = new Request_AccessLogEntry($this);
            if (preg_match('/^(\S+)\s(\S+)\s(\S+)\s\[(.+?)\] "([^"]+)" (\d+) (\d+) "([^"]*)" "([^"]*)"$/',$s,$m)) {
            	list(,$entry->host, $entry->ident, $entry->user, $entry->time,
                     $entry->request, $entry->status, $entry->size,
                     $entry->referer, $entry->user_agent) = $m;
            }
            return $entry;
        } else { // until the end
            fclose($this->reader);
            return false;
        }
    }
    function _read_sql_query($where='') {
        $dbh =& $GLOBALS['request']->_dbi;
        $log_tbl =& $this->logtable;
        return $dbh->genericSqlIter("SELECT *,request_uri as request,request_time as time,remote_user as user,"
                                    ."remote_host as host,agent as user_agent"
                                    ." FROM $log_tbl"
                                    . ($where ? " WHERE $where" : ""));
    }
    function read_sql($where='') {
        if (empty($this->sqliter))
            $this->sqliter = $this->_read_sql_query($where);
        return $this->sqliter->next();
    }

    /* done in request->finish() before the db is closed */
    function write_sql() {
    	$dbh =& $GLOBALS['request']->_dbi;
        if (isset($this->entries) and $dbh and $dbh->isOpen())
            foreach ($this->entries as $entry) {
                $entry->write_sql();
            }
    }
    /* done in the shutdown callback */
    function write_file() {
        if (isset($this->entries) and $this->logfile)
            foreach ($this->entries as $entry) {
                $entry->write_file();
            }
        unset($this->entries);
    }
    /* in an ideal world... */
    function write() {
        if ($this->logfile) $this->write_file();
        if ($this->logtable) $this->write_sql();
        unset($this->entries);
    }
}

class Request_AccessLogEntry
{
    /**
     * Constructor.
     *
     * The log entry will be automatically appended to the log file or 
     * SQL table when the current request terminates.
     *
     * If you want to modify a Request_AccessLogEntry before it gets
     * written (e.g. via the setStatus and setSize methods) you should
     * use an '&' on the constructor, so that you're working with the
     * original (rather than a copy) object.
     *
     * <pre>
     *    $log_entry = new Request_AccessLogEntry("/tmp/wiki_access_log");
     *    $log_entry->setStatus(401);
     *    $log_entry->push($request);
     * </pre>
     *
     *
     */
    function Request_AccessLogEntry (&$accesslog) {
        $this->_accesslog = $accesslog;
        $this->logfile = $accesslog->logfile;
        $this->time = time();
        $this->status = 200;    // see setStatus()
        $this->size = 0;	// see setSize()
    }

    /**
     * @param $request object  Request object for current request.
     */
    function push(&$request) {
        $this->host  = $request->get('REMOTE_HOST');
        $this->ident = $request->get('REMOTE_IDENT');
        if (!$this->ident)
            $this->ident = '-';
        $user = $request->getUser();
        if ($user->isAuthenticated())
            $this->user = $user->UserName();
        else
            $this->user = '-';
        $this->request = join(' ', array($request->get('REQUEST_METHOD'),
                                         $request->get('REQUEST_URI'),
                                         $request->get('SERVER_PROTOCOL')));
        $this->referer = (string) $request->get('HTTP_REFERER');
        $this->user_agent = (string) $request->get('HTTP_USER_AGENT');
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
    function setSize ($size=0) {
        $this->size = $size;
    }
    function setDuration ($seconds) {
        $this->duration = $seconds;
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

    function write() {
        if ($this->_accesslog->logfile) $this->write_file();
        if ($this->_accesslog->logtable) $this->write_sql();
    }

    /**
     * Write entry to log file.
     */
    function write_file() {
        $entry = sprintf('%s %s %s [%s] "%s" %d %d "%s" "%s"',
                         $this->host, $this->ident, $this->user,
                         $this->_ncsa_time($this->time),
                         $this->request, $this->status, $this->size,
                         $this->referer, $this->user_agent);
        if (!empty($this->_accesslog->reader)) {
            fclose($this->_accesslog->reader);
            unset($this->_accesslog->reader);
        }
        //Error log doesn't provide locking.
        //error_log("$entry\n", 3, $this->logfile);
        // Alternate method
        if (($fp = fopen($this->logfile, "a"))) {
            flock($fp, LOCK_EX);
            fputs($fp, "$entry\n");
            fclose($fp);
        }
    }

    /* This is better been done by apache mod_log_sql */
    /* If ACCESS_LOG_SQL & 2 we do write it by our own */
    function write_sql() {
    	global $request;
    	
        $dbh =& $request->_dbi;
        if ($dbh and $dbh->isOpen() and $this->_accesslog->logtable) {
            $log_tbl =& $this->_accesslog->logtable;
            if ($request->get('REQUEST_METHOD') == "POST") {
                $args = $_POST;

                // garble passwords
                if (!empty($args['auth']['passwd']))    $args['auth']['passwd'] = '<not displayed>';
                if (!empty($args['dbadmin']['passwd'])) $args['dbadmin']['passwd'] = '<not displayed>';
                if (!empty($args['pref']['passwd']))    $args['pref']['passwd'] = '<not displayed>';
                if (!empty($args['pref']['passwd2']))   $args['pref']['passwd2'] = '<not displayed>';
                $this->request_args = substr(serialize($args),0,254); // if VARCHAR(255) is used.
            } else {
          	$this->request_args = $request->get('QUERY_STRING'); 
            }
            // duration problem: sprintf "%f" might use comma e.g. "100,201" in european locales
            $dbh->genericSqlQuery
                (
                 sprintf("INSERT INTO $log_tbl"
                         . " (time_stamp,remote_host,remote_user,request_method,request_line,request_uri,"
                         .   "request_args,request_time,status,bytes_sent,referer,agent,request_duration)"
                         . " VALUES(%d,%s,%s,%s,%s,%s,%s,%s,%d,%d,%s,%s,'%s')",
                     $this->time,
                     $dbh->quote($this->host), $dbh->quote($this->user),
                     $dbh->quote($request->get('REQUEST_METHOD')), $dbh->quote($this->request), 
                     $dbh->quote($request->get('REQUEST_URI')), $dbh->quote($this->request_args),
                     $dbh->quote($this->_ncsa_time($this->time)), $this->status, $this->size,
                     $dbh->quote($this->referer),
                     $dbh->quote($this->user_agent),
                     $this->duration));
        }
    }

}

/**
 * Shutdown callback.
 *
 * @access private
 * @see Request_AccessLogEntry
 */
function Request_AccessLogEntry_shutdown_function () {
    global $request;
    
    if (isset($request->_accesslog->entries) and $request->_accesslog->logfile)
        foreach ($request->_accesslog->entries as $entry) {
            $entry->write_file();
        }
    unset($request->_accesslog->entries);
}


class HTTP_ETag {
    function HTTP_ETag($val, $is_weak=false) {
        $this->_val = wikihash($val);
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
define ('_HTTP_VAL_PASS', 0);         	// Test is irrelevant
define ('_HTTP_VAL_NOT_MODIFIED', 1); 	// Test passed, content not changed
define ('_HTTP_VAL_MODIFIED', 2); 	// Test failed, content changed
define ('_HTTP_VAL_FAILED', 3);   	// Precondition failed.

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
        if (is_array($this->_tag))
            $this->_tag = array_merge($this->_tag, $that->_tag);
        else
            $this->_tag = $that->_tag;
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


// $Log: Request.php,v $
// Revision 1.100  2006/01/17 18:57:09  uckelman
// _accesslog->logtable is not set when using non-SQL logging; check should
//  be isset to avoid a PHP warning
//
// Revision 1.99  2005/09/18 16:01:09  rurban
// trick to send the correct gzipped Content-Length
//
// Revision 1.98  2005/09/18 15:15:53  rurban
// add a proper Content-Encoding: gzip if compressed, and omit Content-Length then.
//
// Revision 1.97  2005/09/14 05:58:17  rurban
// protect against Content-Length if headers_sent(), fixed writing unwanted accesslog sql entries
//
// Revision 1.96  2005/08/07 10:52:43  rurban
// stricter error handling: dba errors are fatal, display errors on Request->finish or session_close
//
// Revision 1.95  2005/08/07 10:09:33  rurban
// set _COOKIE also
//
// Revision 1.94  2005/08/07 09:14:39  rurban
// fix comments
//
// Revision 1.93  2005/08/06 14:31:10  rurban
// ensure absolute uploads path
//
// Revision 1.92  2005/05/14 07:22:47  rurban
// remove mysql specific INSERT DELAYED
//
// Revision 1.91  2005/04/11 19:40:14  rurban
// Simplify upload. See https://sourceforge.net/forum/message.php?msg_id=3093651
// Improve UpLoad warnings.
// Move auth check before upload.
//
// Revision 1.90  2005/02/26 18:30:01  rurban
// update (C)
//
// Revision 1.89  2005/02/04 10:38:36  rurban
// do not log passwords! Thanks to Charles Corrigan
//
// Revision 1.88  2005/01/25 07:00:23  rurban
// fix redirect,
//
// Revision 1.87  2005/01/08 21:27:45  rurban
// Prevent from Overlarge session data crash
//
// Revision 1.86  2005/01/04 20:26:34  rurban
// honor DISABLE_HTTP_REDIRECT, do not gzip the redirect template, flush it
//
// Revision 1.85  2004/12/26 17:08:36  rurban
// php5 fixes: case-sensitivity, no & new
//
// Revision 1.84  2004/12/17 16:37:30  rurban
// avoid warning
//
// Revision 1.83  2004/12/10 02:36:43  rurban
// More help with the new native xmlrpc lib. no warnings, no user cookie on xmlrpc.
//
// Revision 1.82  2004/12/06 19:49:55  rurban
// enable action=remove which is undoable and seeable in RecentChanges: ADODB ony for now.
// renamed delete_page to purge_page.
// enable action=edit&version=-1 to force creation of a new version.
// added BABYCART_PATH config
// fixed magiqc in adodb.inc.php
// and some more docs
//
// Revision 1.81  2004/11/27 14:39:04  rurban
// simpified regex search architecture:
//   no db specific node methods anymore,
//   new sql() method for each node
//   parallel to regexp() (which returns pcre)
//   regex types bitmasked (op's not yet)
// new regex=sql
// clarified WikiDB::quote() backend methods:
//   ->quote() adds surrounsing quotes
//   ->qstr() (new method) assumes strings and adds no quotes! (in contrast to ADODB)
//   pear and adodb have now unified quote methods for all generic queries.
//
// Revision 1.80  2004/11/21 11:59:16  rurban
// remove final \n to be ob_cache independent
//
// Revision 1.79  2004/11/11 18:29:44  rurban
// (write_sql) isOpen really is useless in non-SQL, do more explicit check
//
// Revision 1.78  2004/11/10 15:29:20  rurban
// * requires newer Pear_DB (as the internal one): quote() uses now escapeSimple for strings
// * ACCESS_LOG_SQL: fix cause request not yet initialized
// * WikiDB: moved SQL specific methods upwards
// * new Pear_DB quoting: same as ADODB and as newer Pear_DB.
//   fixes all around: WikiGroup, WikiUserNew SQL methods, SQL logging
//
// Revision 1.77  2004/11/09 17:11:04  rurban
// * revert to the wikidb ref passing. there's no memory abuse there.
// * use new wikidb->_cache->_id_cache[] instead of wikidb->_iwpcache, to effectively
//   store page ids with getPageLinks (GleanDescription) of all existing pages, which
//   are also needed at the rendering for linkExistingWikiWord().
//   pass options to pageiterator.
//   use this cache also for _get_pageid()
//   This saves about 8 SELECT count per page (num all pagelinks).
// * fix passing of all page fields to the pageiterator.
// * fix overlarge session data which got broken with the latest ACCESS_LOG_SQL changes
//
// Revision 1.76  2004/11/09 08:15:18  rurban
// fix ADODB quoting style
//
// Revision 1.75  2004/11/07 18:34:28  rurban
// more logging fixes
//
// Revision 1.74  2004/11/07 16:02:51  rurban
// new sql access log (for spam prevention), and restructured access log class
// dbh->quote (generic)
// pear_db: mysql specific parts seperated (using replace)
//
// Revision 1.73  2004/11/06 04:51:25  rurban
// readable ACCESS_LOG support: RecentReferrers, WikiAccessRestrictions
//
// Revision 1.72  2004/11/01 10:43:55  rurban
// seperate PassUser methods into seperate dir (memory usage)
// fix WikiUser (old) overlarge data session
// remove wikidb arg from various page class methods, use global ->_dbi instead
// ...
//
// Revision 1.71  2004/10/22 09:20:36  rurban
// fix for USECACHE=false
//
// Revision 1.70  2004/10/21 19:59:18  rurban
// Patch #991494 (ppo): Avoid notice in PHP >= 4.3.3 if session already started
//
// Revision 1.69  2004/10/21 19:00:37  rurban
// upload errmsgs by Shilad Sen.
// chunkOutput support: flush the buffer piecewise (dumphtml, large pagelists)
//   doesn't gain much because ob_end_clean() doesn't release its
//   memory properly yet.
//
// Revision 1.68  2004/10/12 13:13:19  rurban
// php5 compatibility (5.0.1 ok)
//
// Revision 1.67  2004/09/25 18:56:54  rurban
// make start_debug logic work
//
// Revision 1.66  2004/09/25 16:24:52  rurban
// dont compress on debugging
//
// Revision 1.65  2004/09/17 14:13:49  rurban
// We check for the client Accept-Encoding: "gzip" presence also
// This should eliminate a lot or reported problems.
//
// Note that this doesn#t fix RSS ssues:
// Most RSS clients are NOT(!) application/xml gzip compatible yet.
// Even if they are sending the accept-encoding gzip header!
// wget is, Mozilla, and MSIE no.
// Of the RSS readers only MagpieRSS 0.5.2 is. http://www.rssgov.com/rssparsers.html
//
// Revision 1.64  2004/09/17 13:32:36  rurban
// Disable server-side gzip encoding for RSS (RDF encoding), even if the client says it
// supports it. Mozilla has this error, wget works fine. IE not checked.
//
// Revision 1.63  2004/07/01 09:29:40  rurban
// fixed another DbSession crash: wrong WikiGroup vars
//
// Revision 1.62  2004/06/27 10:26:02  rurban
// oci8 patch by Philippe Vanhaesendonck + some ADODB notes+fixes
//
// Revision 1.61  2004/06/25 14:29:17  rurban
// WikiGroup refactoring:
//   global group attached to user, code for not_current user.
//   improved helpers for special groups (avoid double invocations)
// new experimental config option ENABLE_XHTML_XML (fails with IE, and document.write())
// fixed a XHTML validation error on userprefs.tmpl
//
// Revision 1.60  2004/06/19 11:51:13  rurban
// CACHE_CONTROL: NONE => NO_CACHE
//
// Revision 1.59  2004/06/13 11:34:22  rurban
// fixed bug #969532 (space in uploaded filenames)
// improved upload error messages
//
// Revision 1.58  2004/06/04 20:32:53  rurban
// Several locale related improvements suggested by Pierrick Meignen
// LDAP fix by John Cole
// reanable admin check without ENABLE_PAGEPERM in the admin plugins
//
// Revision 1.57  2004/06/03 18:54:25  rurban
// fixed "lost level in session" warning, now that signout sets level = 0 (before -1)
//
// Revision 1.56  2004/05/17 17:43:29  rurban
// CGI: no PATH_INFO fix
//
// Revision 1.55  2004/05/15 18:31:00  rurban
// some action=pdf Request fixes: With MSIE it works now. Now the work with the page formatting begins.
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
