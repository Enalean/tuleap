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
 * @version $Id: Response.php 19 2009-08-12 14:08:42Z donovan.jimenez $
 *
 * @package Apache
 * @subpackage Solr
 * @author Donovan Jimenez <djimenez@conduit-it.com>
 */

/**
 * Represents a Solr response.  Parses the raw response into a set of stdClass objects
 * and associative arrays for easy access.
 *
 * Currently requires json_decode which is bundled with PHP >= 5.2.0, Alternatively can be
 * installed with PECL.  Zend Framework also includes a purely PHP solution.
 */
class Apache_Solr_Response
{
	/**
	 * SVN Revision meta data for this class
	 */
	const SVN_REVISION = '$Revision: 19 $';

	/**
	 * SVN ID meta data for this class
	 */
	const SVN_ID = '$Id: Response.php 19 2009-08-12 14:08:42Z donovan.jimenez $';

	/**
	 * Holds the raw response used in construction
	 *
	 * @var string
	 */
	protected $_rawResponse;

	/**
	 * Parsed values from the passed in http headers
	 *
	 * @var string
	 */
	protected $_httpStatus, $_httpStatusMessage, $_type, $_encoding;

	/**
	 * Whether the raw response has been parsed
	 *
	 * @var boolean
	 */
	protected $_isParsed = false;

	/**
	 * Parsed representation of the data
	 *
	 * @var mixed
	 */
	protected $_parsedData;

	/**
	 * Data parsing flags.  Determines what extra processing should be done
	 * after the data is initially converted to a data structure.
	 *
	 * @var boolean
	 */
	protected $_createDocuments = true,
			$_collapseSingleValueArrays = true;

	/**
	 * Constructor. Takes the raw HTTP response body and the exploded HTTP headers
	 *
	 * @param string $rawResponse
	 * @param array $httpHeaders
	 * @param boolean $createDocuments Whether to convert the documents json_decoded as stdClass instances to Apache_Solr_Document instances
	 * @param boolean $collapseSingleValueArrays Whether to make multivalued fields appear as single values
	 */
	public function __construct($rawResponse, $httpHeaders = array(), $createDocuments = true, $collapseSingleValueArrays = true)
	{
		//Assume 0, 'Communication Error', utf-8, and  text/plain
		$status = 0;
		$statusMessage = 'Communication Error';
		$type = 'text/plain';
		$encoding = 'UTF-8';

		//iterate through headers for real status, type, and encoding
		if (is_array($httpHeaders) && count($httpHeaders) > 0)
		{
			//look at the first headers for the HTTP status code
			//and message (errors are usually returned this way)
			//
			//HTTP 100 Continue response can also be returned before
			//the REAL status header, so we need look until we find
			//the last header starting with HTTP
			//
			//the spec: http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.1
			//
			//Thanks to Daniel Andersson for pointing out this oversight
			while (isset($httpHeaders[0]) && substr($httpHeaders[0], 0, 4) == 'HTTP')
			{
				$parts = explode(' ', substr($httpHeaders[0], 9), 2);

				$status = $parts[0];
				$statusMessage = trim($parts[1]);

				array_shift($httpHeaders);
			}

			//Look for the Content-Type response header and determine type
			//and encoding from it (if possible - such as 'Content-Type: text/plain; charset=UTF-8')
			foreach ($httpHeaders as $header)
			{
				if (strncasecmp($header, 'Content-Type:', 13) == 0)
				{
					//split content type value into two parts if possible
					$parts = explode(';', substr($header, 13), 2);

					$type = trim($parts[0]);

					if ($parts[1])
					{
						//split the encoding section again to get the value
						$parts = explode('=', $parts[1], 2);

						if ($parts[1])
						{
							$encoding = trim($parts[1]);
						}
					}

					break;
				}
			}
		}

		$this->_rawResponse = $rawResponse;
		$this->_type = $type;
		$this->_encoding = $encoding;
		$this->_httpStatus = $status;
		$this->_httpStatusMessage = $statusMessage;
		$this->_createDocuments = (bool) $createDocuments;
		$this->_collapseSingleValueArrays = (bool) $collapseSingleValueArrays;
	}

	/**
	 * Get the HTTP status code
	 *
	 * @return integer
	 */
	public function getHttpStatus()
	{
		return $this->_httpStatus;
	}

	/**
	 * Get the HTTP status message of the response
	 *
	 * @return string
	 */
	public function getHttpStatusMessage()
	{
		return $this->_httpStatusMessage;
	}

	/**
	 * Get content type of this Solr response
	 *
	 * @return string
	 */
	public function getType()
	{
		return $this->_type;
	}

	/**
	 * Get character encoding of this response. Should usually be utf-8, but just in case
	 *
	 * @return string
	 */
	public function getEncoding()
	{
		return $this->_encoding;
	}

	/**
	 * Get the raw response as it was given to this object
	 *
	 * @return string
	 */
	public function getRawResponse()
	{
		return $this->_rawResponse;
	}

	/**
	 * Magic get to expose the parsed data and to lazily load it
	 *
	 * @param unknown_type $key
	 * @return unknown
	 */
	public function __get($key)
	{
		if (!$this->_isParsed)
		{
			$this->_parseData();
			$this->_isParsed = true;
		}

		if (isset($this->_parsedData->$key))
		{
			return $this->_parsedData->$key;
		}

		return null;
	}

	/**
	 * Parse the raw response into the parsed_data array for access
	 */
	protected function _parseData()
	{
		//An alternative would be to use Zend_Json::decode(...)
		$data = json_decode($this->_rawResponse);

		//if we're configured to collapse single valued arrays or to convert them to Apache_Solr_Document objects
		//and we have response documents, then try to collapse the values and / or convert them now
		if (($this->_createDocuments || $this->_collapseSingleValueArrays) && isset($data->response) && is_array($data->response->docs))
		{
			$documents = array();

			foreach ($data->response->docs as $originalDocument)
			{
				if ($this->_createDocuments)
				{
					$document = new Apache_Solr_Document();
				}
				else
				{
					$document = $originalDocument;
				}

				foreach ($originalDocument as $key => $value)
				{
					//If a result is an array with only a single
					//value then its nice to be able to access
					//it as if it were always a single value
					if ($this->_collapseSingleValueArrays && is_array($value) && count($value) <= 1)
					{
						$value = array_shift($value);
					}

					$document->$key = $value;
				}

				$documents[] = $document;
			}

			$data->response->docs = $documents;
		}

		$this->_parsedData = $data;
	}
}