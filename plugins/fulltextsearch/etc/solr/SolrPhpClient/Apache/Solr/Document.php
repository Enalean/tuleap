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
 * @version $Id: Document.php 15 2009-08-04 17:53:08Z donovan.jimenez $
 *
 * @package Apache
 * @subpackage Solr
 * @author Donovan Jimenez <djimenez@conduit-it.com>
 */

/**
 * Holds Key / Value pairs that represent a Solr Document along with any associated boost
 * values. Field values can be accessed by direct dereferencing such as:
 * <code>
 * ...
 * $document->title = 'Something';
 * echo $document->title;
 * ...
 * </code>
 *
 * Additionally, the field values can be iterated with foreach
 *
 * <code>
 * foreach ($document as $fieldName => $fieldValue)
 * {
 * ...
 * }
 * </code>
 */
class Apache_Solr_Document implements IteratorAggregate
{
	/**
	 * SVN Revision meta data for this class
	 */
	const SVN_REVISION = '$Revision: 15 $';

	/**
	 * SVN ID meta data for this class
	 */
	const SVN_ID = '$Id: Document.php 15 2009-08-04 17:53:08Z donovan.jimenez $';

	/**
	 * Document boost value
	 *
	 * @var float
	 */
	protected $_documentBoost = false;

	/**
	 * Document field values, indexed by name
	 *
	 * @var array
	 */
	protected $_fields = array();

	/**
	 * Document field boost values, indexed by name
	 *
	 * @var array array of floats
	 */
	protected $_fieldBoosts = array();

	/**
	 * Clear all boosts and fields from this document
	 */
	public function clear()
	{
		$this->_documentBoost = false;

		$this->_fields = array();
		$this->_fieldBoosts = array();
	}

	/**
	 * Get current document boost
	 *
	 * @return mixed will be false for default, or else a float
	 */
	public function getBoost()
	{
		return $this->_documentBoost;
	}

	/**
	 * Set document boost factor
	 *
	 * @param mixed $boost Use false for default boost, else cast to float that should be > 0 or will be treated as false
	 */
	public function setBoost($boost)
	{
		$boost = (float) $boost;

		if ($boost > 0.0)
		{
			$this->_documentBoost = $boost;
		}
		else
		{
			$this->_documentBoost = false;
		}
	}

	/**
	 * Add a value to a multi-valued field
	 *
	 * NOTE: the solr XML format allows you to specify boosts
	 * PER value even though the underlying Lucene implementation
	 * only allows a boost per field. To remedy this, the final
	 * field boost value will be the product of all specified boosts
	 * on field values - this is similar to SolrJ's functionality.
	 *
	 * <code>
	 * $doc = new Apache_Solr_Document();
	 *
	 * $doc->addField('foo', 'bar', 2.0);
	 * $doc->addField('foo', 'baz', 3.0);
	 *
	 * // resultant field boost will be 6!
	 * echo $doc->getFieldBoost('foo');
	 * </code>
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param mixed $boost Use false for default boost, else cast to float that should be > 0 or will be treated as false
	 */
	public function addField($key, $value, $boost = false)
	{
		if (!isset($this->_fields[$key]))
		{
			// create holding array if this is the first value
			$this->_fields[$key] = array();
		}
		else if (!is_array($this->_fields[$key]))
		{
			// move existing value into array if it is not already an array
			$this->_fields[$key] = array($this->_fields[$key]);
		}

		if ($this->getFieldBoost($key) === false)
		{
			// boost not already set, set it now
			$this->setFieldBoost($key, $boost);
		}
		else if ((float) $boost > 0.0)
		{
			// multiply passed boost with current field boost - similar to SolrJ implementation
			$this->_fieldBoosts[$key] *= (float) $boost;
		}

		// add value to array
		$this->_fields[$key][] = $value;
	}

	/**
	 * Handle the array manipulation for a multi-valued field
	 *
	 * @param string $key
	 * @param string $value
	 * @param mixed $boost Use false for default boost, else cast to float that should be > 0 or will be treated as false
	 *
	 * @deprecated Use addField(...) instead
	 */
	public function setMultiValue($key, $value, $boost = false)
	{
		$this->addField($key, $value, $boost);
	}

	/**
	 * Get field information
	 *
	 * @param string $key
	 * @return mixed associative array of info if field exists, false otherwise
	 */
	public function getField($key)
	{
		if (isset($this->_fields[$key]))
		{
			return array(
				'name' => $key,
				'value' => $this->_fields[$key],
				'boost' => $this->getFieldBoost($key)
			);
		}

		return false;
	}

	/**
	 * Set a field value. Multi-valued fields should be set as arrays
	 * or instead use the addField(...) function which will automatically
	 * make sure the field is an array.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param mixed $boost Use false for default boost, else cast to float that should be > 0 or will be treated as false
	 */
	public function setField($key, $value, $boost = false)
	{
		$this->_fields[$key] = $value;
		$this->setFieldBoost($key, $boost);
	}

	/**
	 * Get the currently set field boost for a document field
	 *
	 * @param string $key
	 * @return float currently set field boost, false if one is not set
	 */
	public function getFieldBoost($key)
	{
		return isset($this->_fieldBoosts[$key]) ? $this->_fieldBoosts[$key] : false;
	}

	/**
	 * Set the field boost for a document field
	 *
	 * @param string $key field name for the boost
	 * @param mixed $boost Use false for default boost, else cast to float that should be > 0 or will be treated as false
	 */
	public function setFieldBoost($key, $boost)
	{
		$boost = (float) $boost;

		if ($boost > 0.0)
		{
			$this->_fieldBoosts[$key] = $boost;
		}
		else
		{
			$this->_fieldBoosts[$key] = false;
		}
	}

	/**
	 * Return current field boosts, indexed by field name
	 *
	 * @return array
	 */
	public function getFieldBoosts()
	{
		return $this->_fieldBoosts;
	}

	/**
	 * Get the names of all fields in this document
	 *
	 * @return array
	 */
	public function getFieldNames()
	{
		return array_keys($this->_fields);
	}

	/**
	 * Get the values of all fields in this document
	 *
	 * @return array
	 */
	public function getFieldValues()
	{
		return array_values($this->_fields);
	}

	/**
	 * IteratorAggregate implementation function. Allows usage:
	 *
	 * <code>
	 * foreach ($document as $key => $value)
	 * {
	 * 	...
	 * }
	 * </code>
	 */
	public function getIterator()
	{
		$arrayObject = new ArrayObject($this->_fields);

		return $arrayObject->getIterator();
	}

	/**
	 * Magic get for field values
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->_fields[$key];
	}

	/**
	 * Magic set for field values. Multi-valued fields should be set as arrays
	 * or instead use the addField(...) function which will automatically
	 * make sure the field is an array.
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function __set($key, $value)
	{
		$this->setField($key, $value);
	}

	/**
	 * Magic isset for fields values.  Do not call directly. Allows usage:
	 *
	 * <code>
	 * isset($document->some_field);
	 * </code>
	 *
	 * @param string $key
	 * @return boolean
	 */
	public function __isset($key)
	{
		return isset($this->_fields[$key]);
	}

	/**
	 * Magic unset for field values. Do not call directly. Allows usage:
	 *
	 * <code>
	 * unset($document->some_field);
	 * </code>
	 *
	 * @param string $key
	 */
	public function __unset($key)
	{
		unset($this->_fields[$key]);
		unset($this->_fieldBoosts[$key]);
	}
}