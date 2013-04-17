<?php
/** 
 * DropPHP - A simple Dropbox client that works without cURL.
 *
 * http://fabi.me/en/php-projects/dropphp-dropbox-api-client/
 * 
 * 
 * @author     Fabian Schlieper <fabian@fabi.me>
 * @copyright  Fabian Schlieper 2012
 * @version    1.3
 * @license    See LICENSE
 *
 */
 
require_once(dirname(__FILE__)."/OAuthSimple.php");

class DropboxClient {
	
	const API_URL = "https://api.dropbox.com/1/";
	const API_CONTENT_URL = "http://api-content.dropbox.com/1/";
	
	const BUFFER_SIZE = 4096;
	
	const MAX_UPLOAD_CHUNK_SIZE = 150000000; // 150MB

	private $appParams;	
	private $consumerToken;
	
	private $requestToken;
	private $accessToken;
	
	private $locale;
	private $rootPath;
	
	private $useCurl;
	
	function __construct ($app_params, $locale = "en"){
		$this->appParams = $app_params;
		if(empty($app_params['app_key']))
			throw new DropboxException("App Key is empty!");
		
		$this->consumerToken = array('t' => $this->appParams['app_key'], 's' => $this->appParams['app_secret']);		
		$this->locale = $locale;
		$this->rootPath = empty($app_params['app_full_access']) ? "sandbox" : "dropbox";
		
		$this->requestToken = null;
		$this->accessToken = null;
		
		$this->useCurl = function_exists('curl_init');
	}
	
	function SetUseCUrl($use_it)
	{
		$this->useCurl = $use_it && function_exists('curl_init');
	}
	
	// ##################################################
	// Authorization
	
	public function GetRequestToken($get_new_token=false)
	{
		if(!empty($this->requestToken) && !$get_new_token)
			return $this->requestToken;
		
		$rt = $this->authCall("oauth/request_token");
		if(empty($rt) || empty($rt['oauth_token']))
			throw new DropboxException('Could not get request token!');

		return ($this->requestToken = array('t'=>$rt['oauth_token'], 's'=>$rt['oauth_token_secret']));
	}
	
	public function GetAccessToken($request_token = null)
	{
		if(!empty($this->accessToken)) return $this->accessToken;
		
		if(empty($request_token)) $request_token = $this->requestToken;	
		if(empty($request_token)) throw new DropboxException('Request token required!');	
		
		$at = $this->authCall("oauth/access_token", $request_token);
		if(empty($at))
			throw new DropboxException(sprintf('Could not get access token! (request token: %s)', $request_token['t']));
		
		return ($this->accessToken = array('t'=>$at['oauth_token'], 's'=>$at['oauth_token_secret']));
	}
	
	public function SetAccessToken($token)
	{
		if(empty($token['t']) || empty($token['s'])) throw new DropboxException('Passed invalid access token.');
			$this->accessToken = $token;
	}	
	
	public function IsAuthorized()
	{
		if(empty($this->accessToken)) return false;		
		return true;
	}
	
	public function BuildAuthorizeUrl($return_url)
	{
		$rt = $this->GetRequestToken();		
		if(empty($rt) || empty($rt['t'])) throw new DropboxException('Request Token Invalid ('.print_r($rt,true).').');
		return "https://www.dropbox.com/1/oauth/authorize?oauth_token=".$rt['t']."&oauth_callback=".urlencode($return_url);
	}
	
	
	// ##################################################
	// API Functions
	
	
    /** 
	 * Retrieves information about the user's account.
	 * 
	 * @access public
	 * @return object Account info object. See https://www.dropbox.com/developers/reference/api#account-info
	 */		
	public function GetAccountInfo()
	{
		return $this->apiCall("account/info", "GET");
	}
	
	
    /** 
	 * Get file list of a dropbox folder.
	 * 
	 * @access public
	 * @param string|object $dropbox_path Dropbox path of the folder
	 * @return array An array with metadata of files/folders keyed by paths 
	 */		
	public function GetFiles($dropbox_path='', $recursive=false, $include_deleted=false)
	{
		if(is_object($dropbox_path) && !empty($dropbox_path->path)) $dropbox_path = $dropbox_path->path;
		return $this->getFileTree($dropbox_path, $include_deleted, $recursive ? 1000 : 0);
	}
	
    /** 
	 * Get file or folder metadata
	 * 
	 * @access public
	 * @param $dropbox_path string Dropbox path of the file or folder
	 */		
	public function GetMetadata($dropbox_path, $include_deleted=false, $rev=null)
	{
		if(is_object($dropbox_path) && !empty($dropbox_path->path)) $dropbox_path = $dropbox_path->path;
		return $this->apiCall("metadata/$this->rootPath/$dropbox_path", "GET", compact('include_deleted','rev'));
	}
	
    /** 
	 * Download a file to the webserver
	 * 
	 * @access public
	 * @param string|object $dropbox_file Dropbox path or metadata object of the file to download. 
	 * @param string $dest_path Local path for destination
	 * @param string $rev Optional. The revision of the file to retrieve. This defaults to the most recent revision.
	 * @param callback $progress_changed_callback Optional. Callback that will be called during download with 2 args: 1. bytes loaded, 2. file size
	 * @return object Dropbox file metadata
	 */	
	public function DownloadFile($dropbox_file, $dest_path='', $rev=null, $progress_changed_callback = null)
	{
		if(is_object($dropbox_file) && !empty($dropbox_file->path))
			$dropbox_file = $dropbox_file->path;
		
		if(empty($dest_path)) $dest_path = basename($dropbox_file);
		
		$url = $this->cleanUrl(self::API_CONTENT_URL."/files/$this->rootPath/$dropbox_file");
		$content = (!empty($rev)) ? http_build_query(array('rev' => $rev),'','&') : null;
		$context = $this->createRequestContext($url, "GET", $content);

		$fh = @fopen($dest_path, 'wb'); // write binary
		if($fh === false) {
			@fclose($rh);
			throw new DropboxException("Could not create file $dest_path !");
		}
		
		if($this->useCurl) {
			curl_setopt($context, CURLOPT_BINARYTRANSFER, true);
			curl_setopt($context, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($context, CURLOPT_FILE, $fh);
			$response_headers = array();
			self::execCurlAndClose($context, $response_headers);
			fclose($fh);
			$meta = self::getMetaFromHeaders($response_headers);
			$bytes_loaded = filesize($dest_path);
		} else {
			$rh = @fopen($url, 'rb', false, $context); // read binary
			if($rh === false)
				throw new DropboxException("HTTP request to $url failed!");
				
			
			// get file meta from HTTP header
			$s_meta = stream_get_meta_data($rh);
			$meta = self::getMetaFromHeaders($s_meta['wrapper_data']);
			$bytes_loaded = 0;
			while (!feof($rh)) {
			  if(($s=fwrite($fh, fread($rh, self::BUFFER_SIZE))) === false) {
				@fclose($rh);
				@fclose($fh);
				throw new DropboxException("Writing to file $dest_path failed!'");
			  }
			  $bytes_loaded += $s;
			  if(!empty($progress_changed_callback)) {
			  	call_user_func($progress_changed_callback, $bytes_loaded, $meta->bytes);
			  }
			}
					
			fclose($rh);
			fclose($fh);
		}
		
		if($meta->bytes != $bytes_loaded)
			throw new DropboxException("Download size mismatch!");
			
		return $meta;
	}
	
    /** 
	 * Upload a file to dropbox
	 * 
	 * @access public
	 * @param $src_file string Local file to upload
	 * @param $dropbox_path string Dropbox path for destination
	 * @return object Dropbox file metadata
	 */	
	public function UploadFile($src_file, $dropbox_path='', $overwrite=true, $parent_rev=null)
	{
		if(filesize($src_file) > self::MAX_UPLOAD_CHUNK_SIZE)
			throw new DropboxException("Cannot upload files larger than 150MB!");
			
		if(empty($dropbox_path)) $dropbox_path = basename($src_file);
		elseif(is_object($dropbox_path) && !empty($dropbox_path->path)) $dropbox_path = $dropbox_path->path;
			
		$query = http_build_query(array_merge(compact('overwrite', 'parent_rev'), array('locale' => $this->locale)),'','&');
		$url = $this->cleanUrl(self::API_CONTENT_URL."/files_put/$this->rootPath/$dropbox_path")."?$query";
		
		$prev_useCurl = $this->useCurl;
		$this->useCurl = false;
		
		if($this->useCurl) {
			throw new DropboxException("Upload does not work yet!");
			$context = $this->createRequestContext($url, "PUT");
			curl_setopt($context, CURLOPT_BINARYTRANSFER, true);
			$fh = fopen($src_file, 'rb');
			curl_setopt($context, CURLOPT_INFILE, $fh); // file pointer
			curl_setopt($context, CURLOPT_INFILESIZE, filesize($src_file));
			$context = $this->createRequestContext($url, "PUT", $content);
			$meta = json_decode(self::execCurlAndClose($context));
			fclose($fh);
			return $meta;
		} else {
			$content = file_get_contents($src_file);
			if(strlen($content) == 0)
				throw new DropboxException("Could not read file $src_file or file is empty!");
				
			$context = $this->createRequestContext($url, "PUT", $content);
			
			$this->useCurl = $prev_useCurl;
			
			return json_decode(file_get_contents($url, false, $context));
		}
	}
	
	
	function GetLink($dropbox_file, $preview=true, $short=true)
	{
		if(is_object($dropbox_file) && !empty($dropbox_file->path)) $dropbox_file = $dropbox_file->path;
		$url = $this->apiCall(($preview?"shares":"media")."/$this->rootPath/$dropbox_file", "POST", array('locale' => null, 'short_url'=> $preview ? $short : null));
		return $url->url;
	}
	
	function Delta($cursor)
	{
		return $this->apiCall("delta", "POST", compact('cursor'));
	}
	
	
	function Copy($from_path, $to_path)
	{
		if(is_object($from_path) && !empty($from_path->path)) $from_path = $from_path->path;
		return $this->apiCall("fileops/copy", "POST", array('root'=> $this->rootPath, 'from_path' => $from_path, 'to_path' => $to_path));
	}
	
    /** 
	 * Creates a new folder in the DropBox
	 * 
	 * @access public
	 * @param $path string The path to the new folder to create
	 * @return object Dropbox folder metadata
	 */	
	function CreateFolder($path)
	{
		return $this->apiCall("fileops/create_folder", "POST", array('root'=> $this->rootPath, 'path' => $path));
	}
	
    /** 
	 * Delete file or folder
	 * 
	 * @access public
	 * @param $path mixed The path or metadata of the file/folder to be deleted.
	 * @return object Dropbox metadata of deleted file or folder
	 */	
	function Delete($path)
	{
		if(is_object($path) && !empty($path->path)) $path = $path->path;
		return $this->apiCall("fileops/delete", "POST", array('locale' =>null, 'root'=> $this->rootPath, 'path' => $path));
	}
	
	function Move($from_path, $to_path)
	{
		if(is_object($from_path) && !empty($from_path->path)) $from_path = $from_path->path;
		return $this->apiCall("fileops/move", "POST", array('root'=> $this->rootPath, 'from_path' => $from_path, 'to_path' => $to_path));
	}
	
	function getFileTree($path="", $include_deleted = false, $max_depth = 0, $depth=0)
	{
		static $files;
		if($depth == 0) $files = array();
		
		$dir = $this->apiCall("metadata/$this->rootPath/$path", "GET", compact('include_deleted'));
		
		if(empty($dir) || !is_object($dir)) return false;
		
		if(!empty($dir->error)) throw new DropboxException($dir->error);
		
		foreach($dir->contents as $item)
		{
			$files[trim($item->path,'/')] = $item;
			if($item->is_dir && $depth < $max_depth)
			{
				$this->getFileTree($item->path, $include_deleted, $max_depth, $depth+1);
			}
		}
		
		return $files;
	}
	
	function createCurl($url, $http_context)
	{
		$ch = curl_init($url);
		
		$curl_opts = array(
				CURLOPT_HEADER => false, // exclude header from output
				//CURLOPT_MUTE => true, // no output!
				CURLOPT_RETURNTRANSFER => true, // but return!
				CURLOPT_SSL_VERIFYPEER => false,
		);
		
		$curl_opts[CURLOPT_CUSTOMREQUEST] = $http_context['method']; 
		
		if(!empty($http_context['content'])) {
			$curl_opts[CURLOPT_POSTFIELDS] =& $http_context['content'];
			if(defined("CURLOPT_POSTFIELDSIZE"))
				$curl_opts[CURLOPT_POSTFIELDSIZE] = strlen($http_context['content']);
		}
		
		$curl_opts[CURLOPT_HTTPHEADER] = array_map('trim',explode("\n",$http_context['header']));
		
		curl_setopt_array($ch, $curl_opts);
		return $ch;
	}
	
	static private $_curlHeadersRef;
	static function _curlHeaderCallback($ch, $header)
	{
		self::$_curlHeadersRef[] = trim($header);
		return strlen($header);
	}
	
	static function &execCurlAndClose($ch, &$out_response_headers = null)
	{
		if(is_array($out_response_headers)) {
			self::$_curlHeadersRef =& $out_response_headers;
			curl_setopt($ch, CURLOPT_HEADERFUNCTION, array(__CLASS__, '_curlHeaderCallback'));
		}
		$res = curl_exec($ch);
		$err_no = curl_errno($ch);
		$err_str = curl_error($ch);
		curl_close($ch);
		if($err_no || $res === false) {
			throw new DropboxException("cURL-Error ($err_no): $err_str");
		}
		
		return $res;
	}
	
	function createRequestContext($url, $method, &$content=null, $oauth_token=-1)
	{
		if($oauth_token === -1)
			$oauth_token = $this->accessToken;				 

		$http_context = array('method'=>$method, 'header'=> '');
		
		$oauth = new OAuthSimple($this->consumerToken['t'],$this->consumerToken['s']);
		
		if(empty($oauth_token) && !empty($this->accessToken))
			$oauth_token = $this->accessToken;
			
		if(!empty($oauth_token)) {
			$oauth->setParameters(array('oauth_token' => $oauth_token['t']));
			$oauth->signatures(array('oauth_secret'=>$oauth_token['s']));
		}
		
		if(!empty($content)) {
			$post_vars = ($method == "POST" && preg_match("/^[a-z][a-z0-9_]*=/i", substr($content, 0, 32)));
			$http_context['header'] .= "Content-Length: ".strlen($content)."\r\n";
			$http_context['header'] .= "Content-Type: application/".($post_vars?"x-www-form-urlencoded":"octet-stream")."\r\n";			
			$http_context['content'] =& $content;			
			if($post_vars)
				$oauth->setParameters($content);
		} elseif($method == "POST") {
			// make sure that content-length is always set when post request (otherwise some wrappers fail!)
			$http_context['content'] = "";
			$http_context['header'] .= "Content-Length: 0\r\n";
		}
		
		// check for query vars in url and add them to oauth parameters
		$query = strrchr($url,'?');
		if(!empty($query)) {
			$oauth->setParameters(substr($query,1));
			$url = substr($url, 0, -strlen($query));
		}
		
		$signed = $oauth->sign(array(
			'action' => $method,
            'path'=> $url));
		//print_r($signed);	
		
		$http_context['header'] .= "Authorization: ".$signed['header']."\r\n";
		
		return $this->useCurl ? $this->createCurl($url, $http_context) : stream_context_create(array('http'=>$http_context));
	}
	
	function authCall($path, $request_token=null)
	{
		$url = $this->cleanUrl(self::API_URL.$path);
		$dummy = null;
		$context = $this->createRequestContext($url, "POST", $dummy, $request_token);
		
		$contents = $this->useCurl ? self::execCurlAndClose($context) : file_get_contents($url, false, $context);
		$data = array();
		parse_str($contents, $data);
		return $data;
	}
	
	
	function apiCall($path, $method, $params=array())
	{
		$url = $this->cleanUrl(self::API_URL.$path);
		$content = http_build_query(array_merge(array('locale'=>$this->locale), $params),'','&');
		$context = $this->createRequestContext($url, $method, $content);
		$json = $this->useCurl ? self::execCurlAndClose($context) : file_get_contents($url, false, $context);
		//if($json === false)
//			throw new DropboxException();
		return json_decode($json);
	}
	

	private static function getMetaFromHeaders(&$header_array)
	{
		return json_decode(substr(@array_shift(array_filter($header_array, create_function('$s', 'return strpos($s, "x-dropbox-metadata:") === 0;'))), 20));
	}
		

	function cleanUrl($url) {
		$p = substr($url,0,8);
		$url = str_replace('//','/', str_replace('\\','/',substr($url,8)));
		$url = rawurlencode($url);
		$url = str_replace('%2F', '/', $url);
		return $p.$url;
	}
}

class DropboxException extends Exception {
	
	public function __construct($err = null, $isDebug = FALSE) 
	{
		if(is_null($err)) {
			$el = error_get_last();
			$this->message = $el['message'];
			$this->file = $el['file'];
			$this->line = $el['line'];
		} else
			$this->message = $err;
		self::log_error($err);
		if ($isDebug)
		{
			self::display_error($err, TRUE);
		}
	}
	
	public static function log_error($err)
	{
		error_log($err, 0);		
	}
	
	public static function display_error($err, $kill = FALSE)
	{
		print_r($err);
		if ($kill === FALSE)
		{
			die();
		}
	}
}
