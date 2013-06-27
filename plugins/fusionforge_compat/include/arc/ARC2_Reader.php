<?php
/**
 * ARC2 Web Client
 *
 * @author Benjamin Nowack
 * @license <http://arc.semsol.org/license>
 * @homepage <http://arc.semsol.org/>
 * @package ARC2
 * @version 2010-07-06
*/

ARC2::inc('Class');

class ARC2_Reader extends ARC2_Class {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_Reader($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {/* inc_path, proxy_host, proxy_port, proxy_skip, http_accept_header, http_user_agent_header, max_redirects */
    parent::__init();
    $this->http_method = $this->v('http_method', 'GET', $this->a);
    $this->message_body = $this->v('message_body', '', $this->a);;
    $this->http_accept_header = $this->v('http_accept_header', 'Accept: application/rdf+xml; q=0.9, */*; q=0.1', $this->a);
    $this->http_user_agent_header = $this->v('http_user_agent_header', 'User-Agent: ARC Reader (http://arc.semsol.org/)', $this->a);
    $this->http_custom_headers = $this->v('http_custom_headers', '', $this->a);
    $this->max_redirects = $this->v('max_redirects', 3, $this->a);
    $this->format = $this->v('format', false, $this->a);
    $this->redirects = array();
    $this->stream_id = '';
    $this->timeout = $this->v('reader_timeout', 30, $this->a);
    $this->response_headers = array();
    $this->digest_auth = 0;
    $this->auth_infos = $this->v('reader_auth_infos', array(), $this->a);
  }

  /*  */
  
  function setHTTPMethod($v) {
    $this->http_method = $v;
  }

  function setMessageBody($v) {
    $this->message_body = $v;
  }

  function setAcceptHeader($v) {
    $this->http_accept_header = $v;
  }

  function setCustomHeaders($v) {
    $this->http_custom_headers = $v;
  }

  function addCustomHeaders($v) {
    if ($this->http_custom_headers) $this->http_custom_headers .= "\r\n";
    $this->http_custom_headers .= $v;
  }

  /*  */

  function activate($path, $data = '', $ping_only = 0, $timeout = 0) {
    $this->setCredentials($path);
    $this->ping_only = $ping_only;
    if ($timeout) $this->timeout = $timeout;
    $id = md5($path . ' ' . $data);
    if ($this->stream_id != $id) {
      $this->stream_id = $id;
      /* data uri? */
      if (!$data && preg_match('/^data\:([^\,]+)\,(.*)$/', $path, $m)) {
        $path = '';
        $data = preg_match('/base64/', $m[1]) ? base64_decode($m[2]) : rawurldecode($m[2]);
      }
      $this->base = $this->calcBase($path);
      $this->uri = $this->calcURI($path, $this->base);
      $this->stream = ($data) ? $this->getDataStream($data) : $this->getSocketStream($this->base, $ping_only);
      if ($this->stream && !$this->ping_only) {
        $this->getFormat();
      }
    }
  }

  /*
   * HTTP Basic/Digest + Proxy authorization can be defined in the
   * arc_reader_credentials config setting:

        'arc_reader_credentials' => array(
          'http://basic.example.com/' => 'user:pass', // shortcut for type=basic
          'http://digest.example.com/' => 'user::pass', // shortcut for type=digest
          'http://proxy.example.com/' => array('type' => 'basic', 'proxy', 'user' => 'user', 'pass' => 'pass'),
        ),

   */

  function setCredentials($url) {
    if (!$creds = $this->v('arc_reader_credentials', array(), $this->a))  return 0;
    foreach ($creds as $pattern => $creds) {
      /* digest shortcut (user::pass) */
      if (!is_array($creds) && preg_match('/^(.+)\:\:(.+)$/', $creds, $m)) {
        $creds = array('type' => 'digest', 'user' => $m[1], 'pass' => $m[2]);
      }
      /* basic shortcut (user:pass) */
      if (!is_array($creds) && preg_match('/^(.+)\:(.+)$/', $creds, $m)) {
        $creds = array('type' => 'basic', 'user' => $m[1], 'pass' => $m[2]);
      }
      if (!is_array($creds)) return 0;
      $regex = '/' . preg_replace('/([\:\/\.\?])/', '\\\\\1', $pattern) . '/';
      if (!preg_match($regex, $url)) continue;
      $mthd = 'set' . $this->camelCase($creds['type']) . 'AuthCredentials';
      if (method_exists($this, $mthd)) $this->$mthd($creds, $url);
    }
  }

  function setBasicAuthCredentials($creds) {
    $auth = 'Basic ' . base64_encode($creds['user'] . ':' . $creds['pass']);
    $h = in_array('proxy', $creds) ? 'Proxy-Authorization' : 'Authorization';
    $this->addCustomHeaders($h . ': ' . $auth);
    //echo $h . ': ' . $auth . print_r($creds, 1);
  }

  function setDigestAuthCredentials($creds, $url) {
    $path = $this->v1('path', '/', parse_url($url));
    $auth = '';
    $hs = $this->getResponseHeaders();
    /* initial 401 */
    $h = $this->v('www-authenticate', '', $hs);
    if ($h && preg_match('/Digest/i', $h)) {
      $auth = 'Digest ';
      /* Digest realm="$realm", nonce="$nonce", qop="auth", opaque="$opaque" */
      $ks = array('realm', 'nonce', 'opaque');/* skipping qop, assuming "auth" */
      foreach ($ks as $i => $k) {
        $$k = preg_match('/' . $k . '=\"?([^\"]+)\"?/i', $h, $m) ? $m[1] : '';
        $auth .= ($i ? ', ' : '') . $k . '="' . $$k . '"';
        $this->auth_infos[$k] = $$k;
      }
      $this->auth_infos['auth'] = $auth;
      $this->auth_infos['request_count'] = 1;
    }
    /* initial 401 or repeated request */
    if ($this->v('auth', 0, $this->auth_infos)) {
      $qop = 'auth';
      $auth = $this->auth_infos['auth'];
      $rc = $this->auth_infos['request_count'];
      $realm = $this->auth_infos['realm'];
      $nonce = $this->auth_infos['nonce'];
      $ha1 = md5($creds['user'] . ':' . $realm . ':' . $creds['pass']);
      $ha2 = md5($this->http_method . ':' . $path);
      $nc = dechex($rc);
      $cnonce = dechex($rc * 2);
      $resp = md5($ha1 . ':' . $nonce . ':' . $nc . ':' . $cnonce . ':' . $qop . ':' . $ha2);
      $auth .= ', username="' . $creds['user'] . '"' .
        ', uri="' . $path . '"' .
        ', qop=' . $qop . '' .
        ', nc=' . $nc .
        ', cnonce="' . $cnonce . '"' .
        ', uri="' . $path . '"' .
        ', response="' . $resp . '"' .
      '';
      $this->auth_infos['request_count'] = $rc + 1;
    }
    if (!$auth) return 0;
    $h = in_array('proxy', $creds) ? 'Proxy-Authorization' : 'Authorization';
    $this->addCustomHeaders($h . ': ' . $auth);
  }

  /*  */

  function useProxy($url) {
    if (!$this->v1('proxy_host', 0, $this->a)) {
      return false;
    }
    $skips = $this->v1('proxy_skip', array(), $this->a);
    foreach ($skips as $skip) {
      if (strpos($url, $skip) !== false) {
        return false;
      }
    }
    return true;
  }

  /*  */
  
  function createStream($path, $data = '') {
    $this->base = $this->calcBase($path);
    $this->stream = ($data) ? $this->getDataStream($data) : $this->getSocketStream($this->base);
  }

  function getDataStream($data) {
    return array('type' => 'data', 'pos' => 0, 'headers' => array(), 'size' => strlen($data), 'data' => $data, 'buffer' => '');
  }
  
  function getSocketStream($url) {
    if ($url == 'file://') {
      return $this->addError('Error: file does not exists or is not accessible');
    }
    $parts = parse_url($url);
    $mappings = array('file' => 'File', 'http' => 'HTTP', 'https' => 'HTTP');
    if ($scheme = $this->v(strtolower($parts['scheme']), '', $mappings)) {
      return $this->m('get' . $scheme . 'Socket', $url, $this->getDataStream(''));
    }
  }
  
  function getFileSocket($url) {
    $parts = parse_url($url);
    $s = file_exists($parts['path']) ? @fopen($parts['path'], 'rb') : false;
    if (!$s) {
      return $this->addError('Socket error: Could not open "' . $parts['path'] . '"');
    }
    return array('type' => 'socket', 'socket' =>& $s, 'headers' => array(), 'pos' => 0, 'size' => filesize($parts['path']), 'buffer' => '');
  }
  
  function getHTTPSocket($url, $redirs = 0, $prev_parts = '') {
    $parts = parse_url($url);
    /* relative redirect */
    if (!isset($parts['scheme']) && $prev_parts) $parts['scheme'] = $prev_parts['scheme'];
    if (!isset($parts['host']) && $prev_parts) $parts['host'] = $prev_parts['host'];
    /* no scheme */
    if (!$this->v('scheme', '', $parts)) return $this->addError('Socket error: Missing URI scheme.');
    /* port tweaks */
    $parts['port'] = ($parts['scheme'] == 'https') ? $this->v1('port', 443, $parts) : $this->v1('port', 80, $parts);
    $nl = "\r\n";
    $http_mthd = strtoupper($this->http_method);
    if ($this->v1('user', 0, $parts) || $this->useProxy($url)) {
      $h_code = $http_mthd . ' ' . $url;
    }
    else {
      $h_code = $http_mthd . ' ' . $this->v1('path', '/', $parts) . (($v = $this->v1('query', 0, $parts)) ? '?' . $v : '') . (($v = $this->v1('fragment', 0, $parts)) ? '#' . $v : '');
    }
    $port_code = ($parts['port'] != 80) ? ':' . $parts['port'] : '';
    $h_code .= ' HTTP/1.0' . $nl.
      'Host: ' . $parts['host'] . $port_code . $nl .
      (($v = $this->http_accept_header) ? $v . $nl : '') .
      (($v = $this->http_user_agent_header) && !preg_match('/User\-Agent\:/', $this->http_custom_headers) ? $v . $nl : '') .
      (($http_mthd == 'POST') ? 'Content-Length: ' . strlen($this->message_body) . $nl : '') .
      ($this->http_custom_headers ? trim($this->http_custom_headers) . $nl : '') .
      $nl .
    '';
    /* post body */
    if ($http_mthd == 'POST') {
      $h_code .= $this->message_body . $nl;
    }
    /* connect */
    if ($this->useProxy($url)) {
      $s = @fsockopen($this->a['proxy_host'], $this->a['proxy_port'], $errno, $errstr, $this->timeout);
    }
    elseif (($parts['scheme'] == 'https') && function_exists('stream_socket_client')) {
      // SSL options via config array, code by Hannes Muehleisen (muehleis@informatik.hu-berlin.de)
  	  $context = stream_context_create();
      foreach ($this->a as $k => $v) {
        if (preg_match('/^arc_reader_ssl_(.+)$/', $k, $m)) {
          stream_context_set_option($context, 'ssl', $m[1], $v);
        }
      }
      $s = stream_socket_client('ssl://' . $parts['host'] . $port_code, $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT, $context);
    }
    elseif ($parts['scheme'] == 'https') {
      $s = @fsockopen('ssl://' . $parts['host'], $parts['port'], $errno, $errstr, $this->timeout);
    }
    elseif ($parts['scheme'] == 'http') {
      $s = @fsockopen($parts['host'], $parts['port'], $errno, $errstr, $this->timeout);
    }
    if (!$s) {
      return $this->addError('Socket error: Could not connect to "' . $url . '" (proxy: ' . ($this->useProxy($url) ? '1' : '0') . '): ' . $errstr);
    }
    /* request */
    fwrite($s, $h_code);
    /* timeout */
    if ($this->timeout) {
      //stream_set_blocking($s, false);
      stream_set_timeout($s, $this->timeout);
    }
    /* response headers */
    $h = array();
    $this->response_headers = $h;
    if (!$this->ping_only) {
      do {
        $line = trim(fgets($s, 4096));
        $info = stream_get_meta_data($s);
        if (preg_match("/^HTTP[^\s]+\s+([0-9]{1})([0-9]{2})(.*)$/i", $line, $m)) {/* response code */
          $error = in_array($m[1], array('4', '5')) ? $m[1] . $m[2] . ' ' . $m[3] : '';
          $error = ($m[1].$m[2] == '304') ? '304 '.$m[3] : $error;
          $h['response-code'] = $m[1] . $m[2];
          $h['error'] = $error;
          $h['redirect'] = ($m[1] == '3') ? true : false;
        }
        elseif (preg_match('/^([^\:]+)\:\s*(.*)$/', $line, $m)) {/* header */
          $h_name = strtolower($m[1]);
          if (!isset($h[$h_name])) {/* 1st value */
            $h[$h_name] = trim($m[2]);
          }
          elseif (!is_array($h[$h_name])) {/* 2nd value */
            $h[$h_name] = array($h[$h_name], trim($m[2]));
          }
          else {/* more values */
            $h[$h_name][] = trim($m[2]);
          }
        }
      } while(!$info['timed_out'] && !feof($s) && $line);
      $h['format'] = strtolower(preg_replace('/^([^\s]+).*$/', '\\1', $this->v('content-type', '', $h)));
      $h['encoding'] = preg_match('/(utf\-8|iso\-8859\-1|us\-ascii)/', $this->v('content-type', '', $h), $m) ? strtoupper($m[1]) : '';
      $h['encoding'] = preg_match('/charset=\s*([^\s]+)/si', $this->v('content-type', '', $h), $m) ? strtoupper($m[1]) : $h['encoding'];
      $this->response_headers = $h;
      /* result */
      if ($info['timed_out']) {
        return $this->addError('Connection timed out after ' . $this->timeout . ' seconds');
      }
      /* error */
      if ($v = $this->v('error', 0, $h)) {
        /* digest auth */
        /* 401 received */
        if (preg_match('/Digest/i', $this->v('www-authenticate', '', $h)) && !$this->digest_auth) {
          $this->setCredentials($url);
          $this->digest_auth = 1;
          return $this->getHTTPSocket($url);
        }
        return $this->addError($error . ' "' . (!feof($s) ? trim(strip_tags(fread($s, 128))) . '..."' : ''));
      }
      /* redirect */
      if ($this->v('redirect', 0, $h) && ($new_url = $this->v1('location', 0, $h))) {
        fclose($s);
        $this->redirects[$url] = $new_url;
        $this->base = $new_url;
        if ($redirs > $this->max_redirects) {
          return $this->addError('Max numbers of redirects exceeded.');
        }
        return $this->getHTTPSocket($new_url, $redirs+1, $parts);
      }
    }
    if ($this->timeout) {
      stream_set_blocking($s, true);
    }
    return array('type' => 'socket', 'url' => $url, 'socket' =>& $s, 'headers' => $h, 'pos' => 0, 'size' => $this->v('content-length', 0, $h), 'buffer' => '');
  }

  function readStream($buffer_xml = true, $d_size = 1024) {
    //if (!$s = $this->v('stream')) return '';
    if (!$s = $this->v('stream')) return $this->addError('missing stream in "readStream" ' . $this->uri);
    $s_type = $this->v('type', '', $s);
    $r = $s['buffer'];
    $s['buffer'] = '';
    if ($s['size']) $d_size = min($d_size, $s['size'] - $s['pos']);
    /* data */
    if ($s_type == 'data') {
      $d = ($d_size > 0) ? substr($s['data'], $s['pos'], $d_size) : '';
    }
    /* socket */
    elseif ($s_type == 'socket') {
      $d = ($d_size > 0) && !feof($s['socket']) ? fread($s['socket'], $d_size) : '';
    }
    $eof = $d ? false : true;
    /* chunked despite HTTP 1.0 request */
    if (isset($s['headers']) && isset($s['headers']['transfer-encoding']) && ($s['headers']['transfer-encoding'] == 'chunked')) {
      $d = preg_replace('/(^|[\r\n]+)[0-9a-f]{1,4}[\r\n]+/', '', $d);
    }
    $s['pos'] += strlen($d);
    if ($buffer_xml) {/* stop after last closing xml tag (if available) */
      if (preg_match('/^(.*\>)([^\>]*)$/s', $d, $m)) {
        $d = $m[1];
        $s['buffer'] = $m[2];
      }
      elseif (!$eof) {
        $s['buffer'] = $r . $d;
        $this->stream = $s;
        return $this->readStream(true, $d_size);
      }
    }
    $this->stream = $s;
    return $r . $d;
  }
  
  function closeStream() {
    if (isset($this->stream)) {
      if ($this->v('type', 0, $this->stream) == 'socket') {
        @fclose($this->stream['socket']);
      }
      unset($this->stream);
    }
  }
  
  /*  */
  
  function getFormat() {
    if (!$this->format) {
      if (!$this->v('stream')) {
        return $this->addError('missing stream in "getFormat"');
      }
      $v = $this->readStream(false);
      $mtype = $this->v('format', '', $this->stream['headers']);
      $this->stream['buffer'] = $v . $this->stream['buffer'];
      $ext = preg_match('/\.([^\.]+)$/', $this->uri, $m) ? $m[1] : '';
      $this->format = ARC2::getFormat($v, $mtype, $ext);
    }
    return $this->format;
  }
    
  /*  */

  function getResponseHeaders() {
    if (isset($this->stream) && isset($this->stream['headers'])) {
      return $this->stream['headers'];
    }
    return $this->response_headers;
  }
  
  function getEncoding($default = 'UTF-8') {
    return $this->v1('encoding', $default, $this->stream['headers']);
  }

  function getRedirects() {
    return $this->redirects;
  }

  function getAuthInfos() {
    return $this->auth_infos;
  }
  
  /*  */
  
}
