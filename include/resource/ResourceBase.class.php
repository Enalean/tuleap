<?php
/**
 * GitPHP ResourceBase
 *
 * Base class for all resource providers
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Resource
 */

/**
 * ResourceBase class
 *
 * @package GitPHP
 * @subpackage Resource
 * @abstract
 */
abstract class GitPHP_ResourceBase
{

	/**
	 * resources
	 *
	 * Array of resources
	 *
	 * @access protected
	 */
	protected $resources = array();

	/**
	 * GetResource
	 *
	 * Gets a resource
	 *
	 * @access public
	 * @param string $resource resource to fetch
	 * @param string $domain domain of string (for multiple translations of same string)
	 * @return string resource
	 */
	public function GetResource($resource, $domain = '')
	{
		if (!empty($resource)) {
			
			$reskey = $resource;
			if (!empty($domain))
				$reskey .= '_' . $domain;

			if (isset($this->resources[$reskey])) {
				return $this->resources[$reskey];
			}

		}

		/*
		 * Fallback on English if string hasn't been tokenized
		 */
		return $resource;
	}

	/**
	 * Format
	 *
	 * Looks up an i18n version of a string and formats
	 * it with the parameters
	 *
	 * @access public
	 * @param string $fmt format string
	 * @return formatted string resource
	 */
	public function Format($fmt)
	{
		$args = func_get_args();
		$x = array_shift($args);
		return vsprintf($this->GetResource($fmt), $args);
	}

}
