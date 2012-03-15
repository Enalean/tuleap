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
 * @version $Id: Balancer.php 15 2009-08-04 17:53:08Z donovan.jimenez $
 *
 * @package Apache
 * @subpackage Solr
 * @author Donovan Jimenez <djimenez@conduit-it.com>, Dan Wolfe
 */

// See Issue #1 (http://code.google.com/p/solr-php-client/issues/detail?id=1)
// Doesn't follow typical include path conventions, but is more convenient for users
require_once(dirname(dirname(__FILE__)) . '/Service.php');

/**
 * Reference Implementation for using multiple Solr services in a distribution. Functionality
 * includes:
 * 	routing of read / write operations
 * 	failover (on selection) for multiple read servers
 */
class Apache_Solr_Service_Balancer
{
	/**
	 * SVN Revision meta data for this class
	 */
	const SVN_REVISION = '$Revision: 15 $';

	/**
	 * SVN ID meta data for this class
	 */
	const SVN_ID = '$Id: Balancer.php 15 2009-08-04 17:53:08Z donovan.jimenez $';

	protected $_createDocuments = true;

	protected $_readableServices = array();
	protected $_writeableServices = array();

	protected $_currentReadService = null;
	protected $_currentWriteService = null;

	protected $_readPingTimeout = 2;
	protected $_writePingTimeout = 4;

	// Configuration for server selection backoff intervals
	protected $_useBackoff = false;		// Set to true to use more resillient write server selection
	protected $_backoffLimit = 600;		// 10 minute default maximum
	protected $_backoffEscalation = 2.0; 	// Rate at which to increase backoff period
	protected $_defaultBackoff = 2.0;		// Default backoff interval

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
		return Apache_Solr_Service::escape($value);
	}

	/**
	 * Escape a value meant to be contained in a phrase for special query characters
	 *
	 * @param string $value
	 * @return string
	 */
	static public function escapePhrase($value)
	{
		return Apache_Solr_Service::escapePhrase($value);
	}

	/**
	 * Convenience function for creating phrase syntax from a value
	 *
	 * @param string $value
	 * @return string
	 */
	static public function phrase($value)
	{
		return Apache_Solr_Service::phrase($value);
	}

	/**
	 * Constructor. Takes arrays of read and write service instances or descriptions
	 *
	 * @param array $readableServices
	 * @param array $writeableServices
	 */
	public function __construct($readableServices = array(), $writeableServices = array())
	{
		//setup readable services
		foreach ($readableServices as $service)
		{
			$this->addReadService($service);
		}

		//setup writeable services
		foreach ($writeableServices as $service)
		{
			$this->addWriteService($service);
		}
	}

	public function setReadPingTimeout($timeout)
	{
		$this->_readPingTimeout = $timeout;
	}

	public function setWritePingTimeout($timeout)
	{
		$this->_writePingTimeout = $timeout;
	}

	public function setUseBackoff($enable)
	{
		$this->_useBackoff = $enable;
	}

	/**
	 * Generates a service ID
	 *
	 * @param string $host
	 * @param integer $port
	 * @param string $path
	 * @return string
	 */
	protected function _getServiceId($host, $port, $path)
	{
		return $host . ':' . $port . $path;
	}

	/**
	 * Adds a service instance or service descriptor (if it is already
	 * not added)
	 *
	 * @param mixed $service
	 *
	 * @throws Exception If service descriptor is not valid
	 */
	public function addReadService($service)
	{
		if ($service instanceof Apache_Solr_Service)
		{
			$id = $this->_getServiceId($service->getHost(), $service->getPort(), $service->getPath());

			$this->_readableServices[$id] = $service;
		}
		else if (is_array($service))
		{
			if (isset($service['host']) && isset($service['port']) && isset($service['path']))
			{
				$id = $this->_getServiceId((string)$service['host'], (int)$service['port'], (string)$service['path']);

				$this->_readableServices[$id] = $service;
			}
			else
			{
				throw new Exception('A Readable Service description array does not have all required elements of host, port, and path');
			}
		}
	}

	/**
	 * Removes a service instance or descriptor from the available services
	 *
	 * @param mixed $service
	 *
	 * @throws Exception If service descriptor is not valid
	 */
	public function removeReadService($service)
	{
		$id = '';

		if ($service instanceof Apache_Solr_Service)
		{
			$id = $this->_getServiceId($service->getHost(), $service->getPort(), $service->getPath());
		}
		else if (is_array($service))
		{
			if (isset($service['host']) && isset($service['port']) && isset($service['path']))
			{
				$id = $this->_getServiceId((string)$service['host'], (int)$service['port'], (string)$service['path']);
			}
			else
			{
				throw new Exception('A Readable Service description array does not have all required elements of host, port, and path');
			}
		}
		else if (is_string($service))
		{
			$id = $service;
		}

		if ($id && isset($this->_readableServices[$id]))
		{
			unset($this->_readableServices[$id]);
		}
	}

	/**
	 * Adds a service instance or service descriptor (if it is already
	 * not added)
	 *
	 * @param mixed $service
	 *
	 * @throws Exception If service descriptor is not valid
	 */
	public function addWriteService($service)
	{
		if ($service instanceof Apache_Solr_Service)
		{
			$id = $this->_getServiceId($service->getHost(), $service->getPort(), $service->getPath());

			$this->_writeableServices[$id] = $service;
		}
		else if (is_array($service))
		{
			if (isset($service['host']) && isset($service['port']) && isset($service['path']))
			{
				$id = $this->_getServiceId((string)$service['host'], (int)$service['port'], (string)$service['path']);

				$this->_writeableServices[$id] = $service;
			}
			else
			{
				throw new Exception('A Writeable Service description array does not have all required elements of host, port, and path');
			}
		}
	}

	/**
	 * Removes a service instance or descriptor from the available services
	 *
	 * @param mixed $service
	 *
	 * @throws Exception If service descriptor is not valid
	 */
	public function removeWriteService($service)
	{
		$id = '';

		if ($service instanceof Apache_Solr_Service)
		{
			$id = $this->_getServiceId($service->getHost(), $service->getPort(), $service->getPath());
		}
		else if (is_array($service))
		{
			if (isset($service['host']) && isset($service['port']) && isset($service['path']))
			{
				$id = $this->_getServiceId((string)$service['host'], (int)$service['port'], (string)$service['path']);
			}
			else
			{
				throw new Exception('A Readable Service description array does not have all required elements of host, port, and path');
			}
		}
		else if (is_string($service))
		{
			$id = $service;
		}

		if ($id && isset($this->_writeableServices[$id]))
		{
			unset($this->_writeableServices[$id]);
		}
	}

	/**
	 * Iterate through available read services and select the first with a ping
	 * that satisfies configured timeout restrictions (or the default)
	 *
	 * @return Apache_Solr_Service
	 *
	 * @throws Exception If there are no read services that meet requirements
	 */
	protected function _selectReadService($forceSelect = false)
	{
		if (!$this->_currentReadService || !isset($this->_readableServices[$this->_currentReadService]) || $forceSelect)
		{
			if ($this->_currentReadService && isset($this->_readableServices[$this->_currentReadService]) && $forceSelect)
			{
				// we probably had a communication error, ping the current read service, remove it if it times out
				if ($this->_readableServices[$this->_currentReadService]->ping($this->_readPingTimeout) === false)
				{
					$this->removeReadService($this->_currentReadService);
				}
			}

			if (count($this->_readableServices))
			{
				// select one of the read services at random
				$ids = array_keys($this->_readableServices);

				$id = $ids[rand(0, count($ids) - 1)];
				$service = $this->_readableServices[$id];

				if (is_array($service))
				{
					//convert the array definition to a client object
					$service = new Apache_Solr_Service($service['host'], $service['port'], $service['path']);
					$this->_readableServices[$id] = $service;
				}

				$service->setCreateDocuments($this->_createDocuments);
				$this->_currentReadService = $id;
			}
			else
			{
				throw new Exception('No read services were available');
			}
		}

		return $this->_readableServices[$this->_currentReadService];
	}

	/**
	 * Iterate through available write services and select the first with a ping
	 * that satisfies configured timeout restrictions (or the default)
	 *
	 * @return Apache_Solr_Service
	 *
	 * @throws Exception If there are no write services that meet requirements
	 */
	protected function _selectWriteService($forceSelect = false)
	{
		if($this->_useBackoff)
		{
			return $this->_selectWriteServiceSafe($forceSelect);
		}

		if (!$this->_currentWriteService || !isset($this->_writeableServices[$this->_currentWriteService]) || $forceSelect)
		{
			if ($this->_currentWriteService && isset($this->_writeableServices[$this->_currentWriteService]) && $forceSelect)
			{
				// we probably had a communication error, ping the current read service, remove it if it times out
				if ($this->_writeableServices[$this->_currentWriteService]->ping($this->_writePingTimeout) === false)
				{
					$this->removeWriteService($this->_currentWriteService);
				}
			}

			if (count($this->_writeableServices))
			{
				// select one of the read services at random
				$ids = array_keys($this->_writeableServices);

				$id = $ids[rand(0, count($ids) - 1)];
				$service = $this->_writeableServices[$id];

				if (is_array($service))
				{
					//convert the array definition to a client object
					$service = new Apache_Solr_Service($service['host'], $service['port'], $service['path']);
					$this->_writeableServices[$id] = $service;
				}

				$this->_currentWriteService = $id;
			}
			else
			{
				throw new Exception('No write services were available');
			}
		}

		return $this->_writeableServices[$this->_currentWriteService];
	}

	/**
	 * Iterate through available write services and select the first with a ping
	 * that satisfies configured timeout restrictions (or the default).  The
	 * timeout period will increase until a connection is made or the limit is
	 * reached.   This will allow for increased reliability with heavily loaded
	 * server(s).
	 *
	 * @return Apache_Solr_Service
	 *
	 * @throws Exception If there are no write services that meet requirements
	 */

	protected function _selectWriteServiceSafe($forceSelect = false)
	{
		if (!$this->_currentWriteService || !isset($this->_writeableServices[$this->_currentWriteService]) || $forceSelect)
		{
			if (count($this->_writeableServices))
			{
				$backoff = $this->_defaultBackoff;

				do {
					// select one of the read services at random
					$ids = array_keys($this->_writeableServices);

					$id = $ids[rand(0, count($ids) - 1)];
					$service = $this->_writeableServices[$id];

					if (is_array($service))
					{
						//convert the array definition to a client object
						$service = new Apache_Solr_Service($service['host'], $service['port'], $service['path']);
						$this->_writeableServices[$id] = $service;
					}

					$this->_currentWriteService = $id;

					$backoff *= $this->_backoffEscalation;

					if($backoff > $this->_backoffLimit)
					{
						throw new Exception('No write services were available.  All timeouts exceeded.');
					}

				} while($this->_writeableServices[$this->_currentWriteService]->ping($backoff) === false);
			}
			else
			{
				throw new Exception('No write services were available');
			}
		}

		return $this->_writeableServices[$this->_currentWriteService];
	}

	public function setCreateDocuments($createDocuments)
	{
		$this->_createDocuments = (bool) $createDocuments;

		if ($this->_currentReadService)
		{
			$service = $this->_selectReadService();
			$service->setCreateDocuments($createDocuments);
		}
	}

	public function getCreateDocuments()
	{
		return $this->_createDocuments;
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
		$service = $this->_selectWriteService();

		do
		{
			try
			{
				return $service->add($rawPost);
			}
			catch (Exception $e)
			{
				if ($e->getCode() != 0) //IF NOT COMMUNICATION ERROR
				{
					throw $e;
				}
			}

			$service = $this->_selectWriteService(true);
		} while ($service);

		return false;
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
		$service = $this->_selectWriteService();

		do
		{
			try
			{
				return $service->addDocument($document, $allowDups, $overwritePending, $overwriteCommitted);
			}
			catch (Exception $e)
			{
				if ($e->getCode() != 0) //IF NOT COMMUNICATION ERROR
				{
					throw $e;
				}
			}

			$service = $this->_selectWriteService(true);
		} while ($service);

		return false;
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
		$service = $this->_selectWriteService();

		do
		{
			try
			{
				return $service->addDocuments($documents, $allowDups, $overwritePending, $overwriteCommitted);
			}
			catch (Exception $e)
			{
				if ($e->getCode() != 0) //IF NOT COMMUNICATION ERROR
				{
					throw $e;
				}
			}

			$service = $this->_selectWriteService(true);
		} while ($service);

		return false;
	}

	/**
	 * Send a commit command.  Will be synchronous unless both wait parameters are set
	 * to false.
	 *
	 * @param boolean $waitFlush
	 * @param boolean $waitSearcher
	 * @return Apache_Solr_Response
	 *
	 * @throws Exception If an error occurs during the service call
	 */
	public function commit($optimize = true, $waitFlush = true, $waitSearcher = true, $timeout = 3600)
	{
		$service = $this->_selectWriteService();

		do
		{
			try
			{
				return $service->commit($optimize, $waitFlush, $waitSearcher, $timeout);
			}
			catch (Exception $e)
			{
				if ($e->getCode() != 0) //IF NOT COMMUNICATION ERROR
				{
					throw $e;
				}
			}

			$service = $this->_selectWriteService(true);
		} while ($service);

		return false;
	}

	/**
	 * Raw Delete Method. Takes a raw post body and sends it to the update service. Body should be
	 * a complete and well formed "delete" xml document
	 *
	 * @param string $rawPost
	 * @return Apache_Solr_Response
	 *
	 * @throws Exception If an error occurs during the service call
	 */
	public function delete($rawPost)
	{
		$service = $this->_selectWriteService();

		do
		{
			try
			{
				return $service->delete($rawPost);
			}
			catch (Exception $e)
			{
				if ($e->getCode() != 0) //IF NOT COMMUNICATION ERROR
				{
					throw $e;
				}
			}

			$service = $this->_selectWriteService(true);
		} while ($service);

		return false;
	}

	/**
	 * Create a delete document based on document ID
	 *
	 * @param string $id
	 * @param boolean $fromPending
	 * @param boolean $fromCommitted
	 * @return Apache_Solr_Response
	 *
	 * @throws Exception If an error occurs during the service call
	 */
	public function deleteById($id, $fromPending = true, $fromCommitted = true)
	{
		$service = $this->_selectWriteService();

		do
		{
			try
			{
				return $service->deleteById($id, $fromPending, $fromCommitted);
			}
			catch (Exception $e)
			{
				if ($e->getCode() != 0) //IF NOT COMMUNICATION ERROR
				{
					throw $e;
				}
			}

			$service = $this->_selectWriteService(true);
		} while ($service);

		return false;
	}

	/**
	 * Create a delete document based on a query and submit it
	 *
	 * @param string $rawQuery
	 * @param boolean $fromPending
	 * @param boolean $fromCommitted
	 * @return Apache_Solr_Response
	 *
	 * @throws Exception If an error occurs during the service call
	 */
	public function deleteByQuery($rawQuery, $fromPending = true, $fromCommitted = true)
	{
		$service = $this->_selectWriteService();

		do
		{
			try
			{
				return $service->deleteByQuery($rawQuery, $fromPending, $fromCommitted);
			}
			catch (Exception $e)
			{
				if ($e->getCode() != 0) //IF NOT COMMUNICATION ERROR
				{
					throw $e;
				}
			}

			$service = $this->_selectWriteService(true);
		} while ($service);

		return false;
	}

	/**
	 * Send an optimize command.  Will be synchronous unless both wait parameters are set
	 * to false.
	 *
	 * @param boolean $waitFlush
	 * @param boolean $waitSearcher
	 * @return Apache_Solr_Response
	 *
	 * @throws Exception If an error occurs during the service call
	 */
	public function optimize($waitFlush = true, $waitSearcher = true)
	{
		$service = $this->_selectWriteService();

		do
		{
			try
			{
				return $service->optimize($waitFlush, $waitSearcher);
			}
			catch (Exception $e)
			{
				if ($e->getCode() != 0) //IF NOT COMMUNICATION ERROR
				{
					throw $e;
				}
			}

			$service = $this->_selectWriteService(true);
		} while ($service);

		return false;
	}

	/**
	 * Simple Search interface
	 *
	 * @param string $query The raw query string
	 * @param int $offset The starting offset for result documents
	 * @param int $limit The maximum number of result documents to return
	 * @param array $params key / value pairs for query parameters, use arrays for multivalued parameters
	 * @return Apache_Solr_Response
	 *
	 * @throws Exception If an error occurs during the service call
	 */
	public function search($query, $offset = 0, $limit = 10, $params = array())
	{
		$service = $this->_selectReadService();

		do
		{
			try
			{
				return $service->search($query, $offset, $limit, $params);
			}
			catch (Exception $e)
			{
				if ($e->getCode() != 0) //IF NOT COMMUNICATION ERROR
				{
					throw $e;
				}
			}

			$service = $this->_selectReadService(true);
		} while ($service);

		return false;
	}
}
