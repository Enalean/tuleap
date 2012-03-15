<?php
/**
 * Copyright (c) 2007-2009, Conduit Internet Technologies, Inc.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *  - Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *  - Neither the name of Conduit Internet Technologies, Inc. nor the names of
 *    its contributors may be used to endorse or promote products derived from
 *    this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @copyright Copyright 2007-2009 Conduit Internet Technologies, Inc. (http://conduit-it.com)
 * @license New BSD (http://solr-php-client.googlecode.com/svn/trunk/COPYING)
 * @version $Id: Service.php 22 2009-11-09 22:46:54Z donovan.jimenez $
 *
 * @package Apache
 * @subpackage Solr
 * @author Donovan Jimenez <djimenez@conduit-it.com>
 */

// See Issue #1 (http://code.google.com/p/solr-php-client/issues/detail?id=1)
// Doesn't follow typical include path conventions, but is more convenient for users
require_once(dirname(__FILE__) . '/Document.php');
require_once(dirname(__FILE__) . '/Response.php');

/**
 * Starting point for the Solr API. Represents a Solr server resource and has
 * methods for pinging, adding, deleting, committing, optimizing and searching.
 *
 * Example Usage:
 * <code>
 * ...
 * $solr = new Apache_Solr_Service(); //or explicitly new Apache_Solr_Service('localhost', 8180, '/solr')
 *
 * if ($solr->ping())
 * {
 * 		$solr->deleteByQuery('*:*'); //deletes ALL documents - be careful :)
 *
 * 		$document = new Apache_Solr_Document();
 * 		$document->id = uniqid(); //or something else suitably unique
 *
 * 		$document->title = 'Some Title';
 * 		$document->content = 'Some content for this wonderful document. Blah blah blah.';
 *
 * 		$solr->addDocument($document); 	//if you're going to be adding documents in bulk using addDocuments
 * 										//with an array of documents is faster
 *
 * 		$solr->commit(); //commit to see the deletes and the document
 * 		$solr->optimize(); //merges multiple segments into one
 *
 * 		//and the one we all care about, search!
 * 		//any other common or custom parameters to the request handler can go in the
 * 		//optional 4th array argument.
 * 		$solr->search('content:blah', 0, 10, array('sort' => 'timestamp desc'));
 * }
 * ...
 * </code>
 *
 * @todo Investigate using other HTTP clients other than file_get_contents built-in handler. Could provide performance
 * improvements when dealing with multiple requests by using HTTP's keep alive functionality
 */
class Apache_Solr_Service
{
	/**
	 * SVN Revision meta data for this class
	 */
	const SVN_REVISION = '$Revision: 22 $';

	/**
	 * SVN ID meta data for this class
	 */
	const SVN_ID = '$Id: Service.php 22 2009-11-09 22:46:54Z donovan.jimenez $';

	/**
	 * Response version we support
	 */
	const SOLR_VERSION = '1.2';

	/**
	 * Response writer we'll request - JSON. See http://code.google.com/p/solr-php-client/issues/detail?id=6#c1 for reasoning
	 */
	const SOLR_WRITER = 'json';

	/**
	 * NamedList Treatment constants
	 */
	const NAMED_LIST_FLAT = 'flat';
	const NAMED_LIST_MAP = 'map';

	/**
	 * Search HTTP Methods
	 */
	const METHOD_GET = 'GET';
	const METHOD_POST = 'POST';

	/**
	 * Servlet mappings
	 */
	const PING_SERVLET = 'admin/ping';
	const UPDATE_SERVLET = 'update';
	const SEARCH_SERVLET = 'select';
	const THREADS_SERVLET = 'admin/threads';

	/**
	 * Server identification strings
	 *
	 * @var string
	 */
	protected $_host, $_port, $_path;

	/**
	 * Whether {@link Apache_Solr_Response} objects should create {@link Apache_Solr_Document}s in
	 * the returned parsed data
	 *
	 * @var boolean
	 */
	protected $_createDocuments = true;

	/**
	 * Whether {@link Apache_Solr_Response} objects should have multivalue fields with only a single value
	 * collapsed to appear as a single value would.
	 *
	 * @var boolean
	 */
	protected $_collapseSingleValueArrays = true;

	/**
	 * How NamedLists should be formatted in the output.  This specifically effects facet counts. Valid values
	 * are {@link Apache_Solr_Service::NAMED_LIST_MAP} (default) or {@link Apache_Solr_Service::NAMED_LIST_FLAT}.
	 *
	 * @var string
	 */
	protected $_namedListTreatment = self::NAMED_LIST_MAP;

	/**
	 * Query delimiters. Someone might want to be able to change
	 * these (to use &amp; instead of & for example), so I've provided them.
	 *
	 * @var string
	 */
	protected $_queryDelimiter = '?', $_queryStringDelimiter = '&';

	/**
	 * Constructed servlet full path URLs
	 *
	 * @var string
	 */
	protected $_pingUrl, $_updateUrl, $_searchUrl, $_threadsUrl;

	/**
	 * Keep track of whether our URLs have been constructed
	 *
	 * @var boolean
	 */
	protected $_urlsInited = false;

	/**
	 * Reusable stream context resources for GET and POST operations
	 *
	 * @var resource
	 */
	protected $_getContext, $_postContext;

	/**
	 * Default HTTP timeout when one is not specified (initialized to default_socket_timeout ini setting)
	 *
	 * var float
	 */
	protected $_defaultTimeout;

	/**
	 * Escape a value for special query characters such as ':', '(', ')', '*', '?', etc.
	 *
	 * NOTE: inside a phrase fewer characters need escaped, use {@link Apache_Solr_Service::escapePhrase()} instead
	 *
	 * @param string $value
	 * @return string
	 */
	static public function escape($value)
	{
		//list taken from http://lucene.apache.org/java/docs/queryparsersyntax.html#Escaping%20Special%20Characters
		$pattern = '/(\+|-|&&|\|\||!|\(|\)|\{|}|\[|]|\^|"|~|\*|\?|:|\\\)/';
		$replace = '\\\$1';

		return preg_replace($pattern, $replace, $value);
	}

	/**
	 * Escape a value meant to be contained in a phrase for special query characters
	 *
	 * @param string $value
	 * @return string
	 */
	static public function escapePhrase($value)
	{
		$pattern = '/("|\\\)/';
		$replace = '\\\$1';

		return preg_replace($pattern, $replace, $value);
	}

	/**
	 * Convenience function for creating phrase syntax from a value
	 *
	 * @param string $value
	 * @return string
	 */
	static public function phrase($value)
	{
		return '"' . self::escapePhrase($value) . '"';
	}

	/**
	 * Constructor. All parameters are optional and will take on default values
	 * if not specified.
	 *
	 * @param string $host
	 * @param string $port
	 * @param string $path
	 */
	public function __construct($host = 'localhost', $port = 8180, $path = '/solr/')
	{
		$this->setHost($host);
		$this->setPort($port);
		$this->setPath($path);

		$this->_initUrls();

		// create our shared get and post stream contexts
		$this->_getContext = stream_context_create();
		$this->_postContext = stream_context_create();

		// determine our default http timeout from ini settings
		$this->_defaultTimeout = (int) ini_get('default_socket_timeout');

		// double check we didn't get 0 for a timeout
		if ($this->_defaultTimeout <= 0)
		{
			$this->_defaultTimeout = 60;
		}
	}

	/**
	 * Return a valid http URL given this server's host, port and path and a provided servlet name
	 *
	 * @param string $servlet
	 * @return string
	 */
	protected function _constructUrl($servlet, $params = array())
	{
		if (count($params))
		{
			//escape all parameters appropriately for inclusion in the query string
			$escapedParams = array();

			foreach ($params as $key => $value)
			{
				$escapedParams[] = urlencode($key) . '=' . urlencode($value);
			}

			$queryString = $this->_queryDelimiter . implode($this->_queryStringDelimiter, $escapedParams);
		}
		else
		{
			$queryString = '';
		}

		return 'http://' . $this->_host . ':' . $this->_port . $this->_path . $servlet . $queryString;
	}

	/**
	 * Construct the Full URLs for the three servlets we reference
	 */
	protected function _initUrls()
	{
		//Initialize our full servlet URLs now that we have server information
		$this->_pingUrl = $this->_constructUrl(self::PING_SERVLET);
		$this->_updateUrl = $this->_constructUrl(self::UPDATE_SERVLET, array('wt' => self::SOLR_WRITER ));
		$this->_searchUrl = $this->_constructUrl(self::SEARCH_SERVLET);
		$this->_threadsUrl = $this->_constructUrl(self::THREADS_SERVLET, array('wt' => self::SOLR_WRITER ));

		$this->_urlsInited = true;
	}

	/**
	 * Central method for making a get operation against this Solr Server
	 *
	 * @param string $url
	 * @param float $timeout Read timeout in seconds
	 * @return Apache_Solr_Response
	 *
	 * @throws Exception If a non 200 response status is returned
	 */
	protected function _sendRawGet($url, $timeout = FALSE)
	{
		// set the timeout if specified
		if ($timeout !== FALSE && $timeout > 0.0)
		{
			// timeouts with file_get_contents seem to need
			// to be halved to work as expected
			$timeout = (float) $timeout / 2;

			stream_context_set_option($this->_getContext, 'http', 'timeout', $timeout);
		}
		else
		{
			// use the default timeout pulled from default_socket_timeout otherwise
			stream_context_set_option($this->_getContext, 'http', 'timeout', $this->_defaultTimeout);
		}

		//$http_response_header is set by file_get_contents
		$response = new Apache_Solr_Response(@file_get_contents($url, false, $this->_getContext), $http_response_header, $this->_createDocuments, $this->_collapseSingleValueArrays);

		if ($response->getHttpStatus() != 200)
		{
			throw new Exception('"' . $response->getHttpStatus() . '" Status: ' . $response->getHttpStatusMessage(), $response->getHttpStatus());
		}

		return $response;
	}

	/**
	 * Central method for making a post operation against this Solr Server
	 *
	 * @param string $url
	 * @param string $rawPost
	 * @param float $timeout Read timeout in seconds
	 * @param string $contentType
	 * @return Apache_Solr_Response
	 *
	 * @throws Exception If a non 200 response status is returned
	 */
	protected function _sendRawPost($url, $rawPost, $timeout = FALSE, $contentType = 'text/xml; charset=UTF-8')
	{
		stream_context_set_option($this->_postContext, array(
				'http' => array(
					// set HTTP method
					'method' => 'POST',

					// Add our posted content type
					'header' => "Content-Type: $contentType",

					// the posted content
					'content' => $rawPost,

					// default timeout
					'timeout' => $this->_defaultTimeout
				)
			)
		);

		// set the timeout if specified
		if ($timeout !== FALSE && $timeout > 0.0)
		{
			// timeouts with file_get_contents seem to need
			// to be halved to work as expected
			$timeout = (float) $timeout / 2;

			stream_context_set_option($this->_postContext, 'http', 'timeout', $timeout);
		}

		//$http_response_header is set by file_get_contents
		$response = new Apache_Solr_Response(@file_get_contents($url, false, $this->_postContext), $http_response_header, $this->_createDocuments, $this->_collapseSingleValueArrays);

		if ($response->getHttpStatus() != 200)
		{
			throw new Exception('"' . $response->getHttpStatus() . '" Status: ' . $response->getHttpStatusMessage(), $response->getHttpStatus());
		}

		return $response;
	}

	/**
	 * Returns the set host
	 *
	 * @return string
	 */
	public function getHost()
	{
		return $this->_host;
	}

	/**
	 * Set the host used. If empty will fallback to constants
	 *
	 * @param string $host
	 */
	public function setHost($host)
	{
		//Use the provided host or use the default
		if (empty($host))
		{
			throw new Exception('Host parameter is empty');
		}
		else
		{
			$this->_host = $host;
		}

		if ($this->_urlsInited)
		{
			$this->_initUrls();
		}
	}

	/**
	 * Get the set port
	 *
	 * @return integer
	 */
	public function getPort()
	{
		return $this->_port;
	}

	/**
	 * Set the port used. If empty will fallback to constants
	 *
	 * @param integer $port
	 */
	public function setPort($port)
	{
		//Use the provided port or use the default
		$port = (int) $port;

		if ($port <= 0)
		{
			throw new Exception('Port is not a valid port number');
		}
		else
		{
			$this->_port = $port;
		}

		if ($this->_urlsInited)
		{
			$this->_initUrls();
		}
	}

	/**
	 * Get the set path.
	 *
	 * @return string
	 */
	public function getPath()
	{
		return $this->_path;
	}

	/**
	 * Set the path used. If empty will fallback to constants
	 *
	 * @param string $path
	 */
	public function setPath($path)
	{
		$path = trim($path, '/');

		$this->_path = '/' . $path . '/';

		if ($this->_urlsInited)
		{
			$this->_initUrls();
		}
	}

	/**
	 * Set the create documents flag. This determines whether {@link Apache_Solr_Response} objects will
	 * parse the response and create {@link Apache_Solr_Document} instances in place.
	 *
	 * @param unknown_type $createDocuments
	 */
	public function setCreateDocuments($createDocuments)
	{
		$this->_createDocuments = (bool) $createDocuments;
	}

	/**
	 * Get the current state of teh create documents flag.
	 *
	 * @return boolean
	 */
	public function getCreateDocuments()
	{
		return $this->_createDocuments;
	}

	/**
	 * Set the collapse single value arrays flag.
	 *
	 * @param boolean $collapseSingleValueArrays
	 */
	public function setCollapseSingleValueArrays($collapseSingleValueArrays)
	{
		$this->_collapseSingleValueArrays = (bool) $collapseSingleValueArrays;
	}

	/**
	 * Get the current state of the collapse single value arrays flag.
	 *
	 * @return boolean
	 */
	public function getCollapseSingleValueArrays()
	{
		return $this->_collapseSingleValueArrays;
	}

	/**
	 * Set how NamedLists should be formatted in the response data. This mainly effects
	 * the facet counts format.
	 *
	 * @param string $namedListTreatment
	 * @throws Exception If invalid option is set
	 */
	public function setNamedListTreatmet($namedListTreatment)
	{
		switch ((string) $namedListTreatment)
		{
			case Apache_Solr_Service::NAMED_LIST_FLAT:
				$this->_namedListTreatment = Apache_Solr_Service::NAMED_LIST_FLAT;
				break;

			case Apache_Solr_Service::NAMED_LIST_MAP:
				$this->_namedListTreatment = Apache_Solr_Service::NAMED_LIST_MAP;
				break;

			default:
				throw new Exception('Not a valid named list treatement option');
		}
	}

	/**
	 * Get the current setting for named list treatment.
	 *
	 * @return string
	 */
	public function getNamedListTreatment()
	{
		return $this->_namedListTreatment;
	}


	/**
	 * Set the string used to separate the path form the query string.
	 * Defaulted to '?'
	 *
	 * @param string $queryDelimiter
	 */
	public function setQueryDelimiter($queryDelimiter)
	{
		$this->_queryDelimiter = $queryDelimiter;
	}

	/**
	 * Set the string used to separate the parameters in thequery string
	 * Defaulted to '&'
	 *
	 * @param string $queryStringDelimiter
	 */
	public function setQueryStringDelimiter($queryStringDelimiter)
	{
		$this->_queryStringDelimiter = $queryStringDelimiter;
	}

	/**
	 * Call the /admin/ping servlet, can be used to quickly tell if a connection to the
	 * server is able to be made.
	 *
	 * @param float $timeout maximum time to wait for ping in seconds, -1 for unlimited (default is 2)
	 * @return float Actual time taken to ping the server, FALSE if timeout or HTTP error status occurs
	 */
	public function ping($timeout = 2)
	{
		$start = microtime(true);

		// when using timeout in context and file_get_contents
		// it seems to take twice the timout value
		$timeout = (float) $timeout / 2;

		if ($timeout <= 0.0)
		{
			$timeout = -1;
		}

		$context = stream_context_create(
			array(
				'http' => array(
					'method' => 'HEAD',
					'timeout' => $timeout
				)
			)
		);

		// attempt a HEAD request to the solr ping page
		$ping = @file_get_contents($this->_pingUrl, false, $context);

		// result is false if there was a timeout
		// or if the HTTP status was not 200
		if ($ping !== false)
		{
			return microtime(true) - $start;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Call the /admin/threads servlet and retrieve information about all threads in the
	 * Solr servlet's thread group. Useful for diagnostics.
	 *
	 * @return Apache_Solr_Response
	 *
	 * @throws Exception If an error occurs during the service call
	 */
	public function threads()
	{
		return $this->_sendRawGet($this->_threadsUrl);
	}

	/**
	 * Raw Add Method. Takes a raw post body and sends it to the update service.  Post body
	 * should be a complete and well formed "add" xml document.
	 *
	 * @param string $rawPost
	 * @return Apache_Solr_Response
	 *
	 * @throws Exception If an error occurs during the service call
	 */
	public function add($rawPost)
	{
		return $this->_sendRawPost($this->_updateUrl, $rawPost);
	}

	/**
	 * Add a Solr Document to the index
	 *
	 * @param Apache_Solr_Document $document
	 * @param boolean $allowDups
	 * @param boolean $overwritePending
	 * @param boolean $overwriteCommitted
	 * @return Apache_Solr_Response
	 *
	 * @throws Exception If an error occurs during the service call
	 */
	public function addDocument(Apache_Solr_Document $document, $allowDups = false, $overwritePending = true, $overwriteCommitted = true)
	{
		$dupValue = $allowDups ? 'true' : 'false';
		$pendingValue = $overwritePending ? 'true' : 'false';
		$committedValue = $overwriteCommitted ? 'true' : 'false';

		$rawPost = '<add allowDups="' . $dupValue . '" overwritePending="' . $pendingValue . '" overwriteCommitted="' . $committedValue . '">';
		$rawPost .= $this->_documentToXmlFragment($document);
		$rawPost .= '</add>';

		return $this->add($rawPost);
	}

	/**
	 * Add an array of Solr Documents to the index all at once
	 *
	 * @param array $documents Should be an array of Apache_Solr_Document instances
	 * @param boolean $allowDups
	 * @param boolean $overwritePending
	 * @param boolean $overwriteCommitted
	 * @return Apache_Solr_Response
	 *
	 * @throws Exception If an error occurs during the service call
	 */
	public function addDocuments($documents, $allowDups = false, $overwritePending = true, $overwriteCommitted = true)
	{
		$dupValue = $allowDups ? 'true' : 'false';
		$pendingValue = $overwritePending ? 'true' : 'false';
		$committedValue = $overwriteCommitted ? 'true' : 'false';

		$rawPost = '<add allowDups="' . $dupValue . '" overwritePending="' . $pendingValue . '" overwriteCommitted="' . $committedValue . '">';

		foreach ($documents as $document)
		{
			if ($document instanceof Apache_Solr_Document)
			{
				$rawPost .= $this->_documentToXmlFragment($document);
			}
		}

		$rawPost .= '</add>';

		return $this->add($rawPost);
	}

	/**
	 * Create an XML fragment from a {@link Apache_Solr_Document} instance appropriate for use inside a Solr add call
	 *
	 * @return string
	 */
	protected function _documentToXmlFragment(Apache_Solr_Document $document)
	{
		$xml = '<doc';

		if ($document->getBoost() !== false)
		{
			$xml .= ' boost="' . $document->getBoost() . '"';
		}

		$xml .= '>';

		foreach ($document as $key => $value)
		{
			$key = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
			$fieldBoost = $document->getFieldBoost($key);

			if (is_array($value))
			{
				foreach ($value as $multivalue)
				{
					$xml .= '<field name="' . $key . '"';

					if ($fieldBoost !== false)
					{
						$xml .= ' boost="' . $fieldBoost . '"';

						// only set the boost for the first field in the set
						$fieldBoost = false;
					}

					$multivalue = htmlspecialchars($multivalue, ENT_NOQUOTES, 'UTF-8');

					$xml .= '>' . $multivalue . '</field>';
				}
			}
			else
			{
				$xml .= '<field name="' . $key . '"';

				if ($fieldBoost !== false)
				{
					$xml .= ' boost="' . $fieldBoost . '"';
				}

				$value = htmlspecialchars($value, ENT_NOQUOTES, 'UTF-8');

				$xml .= '>' . $value . '</field>';
			}
		}

		$xml .= '</doc>';

		// replace any control characters to avoid Solr XML parser exception
		return $this->_stripCtrlChars($xml);
	}

	/**
	 * Replace control (non-printable) characters from string that are invalid to Solr's XML parser with a space.
	 *
	 * @param string $string
	 * @return string
	 */
	protected function _stripCtrlChars($string)
	{
		// See:  http://w3.org/International/questions/qa-forms-utf-8.html
		// Printable utf-8 does not include any of these chars below x7F
		return preg_replace('@[\x00-\x08\x0B\x0C\x0E-\x1F]@', ' ', $string);
	}

	/**
	 * Send a commit command.  Will be synchronous unless both wait parameters are set to false.
	 *
	 * @param boolean $optimize Defaults to true
	 * @param boolean $waitFlush Defaults to true
	 * @param boolean $waitSearcher Defaults to true
	 * @param float $timeout Maximum expected duration (in seconds) of the commit operation on the server (otherwise, will throw a communication exception). Defaults to 1 hour
	 * @return Apache_Solr_Response
	 *
	 * @throws Exception If an error occurs during the service call
	 */
	public function commit($optimize = true, $waitFlush = true, $waitSearcher = true, $timeout = 3600)
	{
		$optimizeValue = $optimize ? 'true' : 'false';
		$flushValue = $waitFlush ? 'true' : 'false';
		$searcherValue = $waitSearcher ? 'true' : 'false';

		$rawPost = '<commit optimize="' . $optimizeValue . '" waitFlush="' . $flushValue . '" waitSearcher="' . $searcherValue . '" />';

		return $this->_sendRawPost($this->_updateUrl, $rawPost, $timeout);
	}

	/**
	 * Raw Delete Method. Takes a raw post body and sends it to the update service. Body should be
	 * a complete and well formed "delete" xml document
	 *
	 * @param string $rawPost Expected to be utf-8 encoded xml document
	 * @param float $timeout Maximum expected duration of the delete operation on the server (otherwise, will throw a communication exception)
	 * @return Apache_Solr_Response
	 *
	 * @throws Exception If an error occurs during the service call
	 */
	public function delete($rawPost, $timeout = 3600)
	{
		return $this->_sendRawPost($this->_updateUrl, $rawPost, $timeout);
	}

	/**
	 * Create a delete document based on document ID
	 *
	 * @param string $id Expected to be utf-8 encoded
	 * @param boolean $fromPending
	 * @param boolean $fromCommitted
	 * @param float $timeout Maximum expected duration of the delete operation on the server (otherwise, will throw a communication exception)
	 * @return Apache_Solr_Response
	 *
	 * @throws Exception If an error occurs during the service call
	 */
	public function deleteById($id, $fromPending = true, $fromCommitted = true, $timeout = 3600)
	{
		$pendingValue = $fromPending ? 'true' : 'false';
		$committedValue = $fromCommitted ? 'true' : 'false';

		//escape special xml characters
		$id = htmlspecialchars($id, ENT_NOQUOTES, 'UTF-8');

		$rawPost = '<delete fromPending="' . $pendingValue . '" fromCommitted="' . $committedValue . '"><id>' . $id . '</id></delete>';

		return $this->delete($rawPost, $timeout);
	}

	/**
	 * Create and post a delete document based on multiple document IDs.
	 *
	 * @param array $ids Expected to be utf-8 encoded strings
	 * @param boolean $fromPending
	 * @param boolean $fromCommitted
	 * @param float $timeout Maximum expected duration of the delete operation on the server (otherwise, will throw a communication exception)
	 * @return Apache_Solr_Response
	 *
	 * @throws Exception If an error occurs during the service call
	 */
	public function deleteByMultipleIds($ids, $fromPending = true, $fromCommitted = true, $timeout = 3600)
	{
		$pendingValue = $fromPending ? 'true' : 'false';
		$committedValue = $fromCommitted ? 'true' : 'false';

		$rawPost = '<delete fromPending="' . $pendingValue . '" fromCommitted="' . $committedValue . '">';

		foreach ($ids as $id)
		{
			//escape special xml characters
			$id = htmlspecialchars($id, ENT_NOQUOTES, 'UTF-8');

			$rawPost .= '<id>' . $id . '</id>';
		}

		$rawPost .= '</delete>';

		return $this->delete($rawPost, $timeout);
	}

	/**
	 * Create a delete document based on a query and submit it
	 *
	 * @param string $rawQuery Expected to be utf-8 encoded
	 * @param boolean $fromPending
	 * @param boolean $fromCommitted
	 * @param float $timeout Maximum expected duration of the delete operation on the server (otherwise, will throw a communication exception)
	 * @return Apache_Solr_Response
	 *
	 * @throws Exception If an error occurs during the service call
	 */
	public function deleteByQuery($rawQuery, $fromPending = true, $fromCommitted = true, $timeout = 3600)
	{
		$pendingValue = $fromPending ? 'true' : 'false';
		$committedValue = $fromCommitted ? 'true' : 'false';

		// escape special xml characters
		$rawQuery = htmlspecialchars($rawQuery, ENT_NOQUOTES, 'UTF-8');

		$rawPost = '<delete fromPending="' . $pendingValue . '" fromCommitted="' . $committedValue . '"><query>' . $rawQuery . '</query></delete>';

		return $this->delete($rawPost, $timeout);
	}

	/**
	 * Send an optimize command.  Will be synchronous unless both wait parameters are set
	 * to false.
	 *
	 * @param boolean $waitFlush
	 * @param boolean $waitSearcher
	 * @param float $timeout Maximum expected duration of the commit operation on the server (otherwise, will throw a communication exception)
	 * @return Apache_Solr_Response
	 *
	 * @throws Exception If an error occurs during the service call
	 */
	public function optimize($waitFlush = true, $waitSearcher = true, $timeout = 3600)
	{
		$flushValue = $waitFlush ? 'true' : 'false';
		$searcherValue = $waitSearcher ? 'true' : 'false';

		$rawPost = '<optimize waitFlush="' . $flushValue . '" waitSearcher="' . $searcherValue . '" />';

		return $this->_sendRawPost($this->_updateUrl, $rawPost, $timeout);
	}

	/**
	 * Simple Search interface
	 *
	 * @param string $query The raw query string
	 * @param int $offset The starting offset for result documents
	 * @param int $limit The maximum number of result documents to return
	 * @param array $params key / value pairs for other query parameters (see Solr documentation), use arrays for parameter keys used more than once (e.g. facet.field)
	 * @return Apache_Solr_Response
	 *
	 * @throws Exception If an error occurs during the service call
	 */
	public function search($query, $offset = 0, $limit = 10, $params = array(), $method = self::METHOD_GET)
	{
		if (!is_array($params))
		{
			$params = array();
		}

		// construct our full parameters
		// sending the version is important in case the format changes
		$params['version'] = self::SOLR_VERSION;

		// common parameters in this interface
		$params['wt'] = self::SOLR_WRITER;
		$params['json.nl'] = $this->_namedListTreatment;

		$params['q'] = $query;
		$params['start'] = $offset;
		$params['rows'] = $limit;

		// use http_build_query to encode our arguments because its faster
		// than urlencoding all the parts ourselves in a loop
		$queryString = http_build_query($params, null, $this->_queryStringDelimiter);

		// because http_build_query treats arrays differently than we want to, correct the query
		// string by changing foo[#]=bar (# being an actual number) parameter strings to just
		// multiple foo=bar strings. This regex should always work since '=' will be urlencoded
		// anywhere else the regex isn't expecting it
		$queryString = preg_replace('/%5B(?:[0-9]|[1-9][0-9]+)%5D=/', '=', $queryString);

		if ($method == self::METHOD_GET)
		{
			return $this->_sendRawGet($this->_searchUrl . $this->_queryDelimiter . $queryString);
		}
		else if ($method == self::METHOD_POST)
		{
			return $this->_sendRawPost($this->_searchUrl, $queryString, FALSE, 'application/x-www-form-urlencoded');
		}
		else
		{
			throw new Exception("Unsupported method '$method', please use the Apache_Solr_Service::METHOD_* constants");
		}
	}
}