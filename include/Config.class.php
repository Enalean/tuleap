<?php
/**
 * GitPHP Config
 *
 * Configfile reader class
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 */

/**
 * Config class
 *
 * @package GitPHP
 */
class GitPHP_Config
{
	
	/**
	 * instance
	 *
	 * Stores the singleton instance
	 *
	 * @access protected
	 * @static
	 */
	protected static $instance;

	/**
	 * values
	 *
	 * Stores the config values
	 *
	 * @access protected
	 */
	protected $values = array();

	/**
	 * configs
	 *
	 * Stores the config files
	 *
	 * @access protected
	 */
	protected $configs = array();

	/**
	 * GetInstance
	 *
	 * Returns the singleton instance
	 *
	 * @access public
	 * @static
	 * @return mixed instance of config class
	 */
	public static function GetInstance()
	{
		if (!self::$instance) {
			self::$instance = new GitPHP_Config();
		}
		return self::$instance;
	}

	/**
	 * LoadConfig
	 *
	 * Loads a config file
	 *
	 * @access public
	 * @param string $configFile config file to load
	 * @throws Exception on failure
	 */
	public function LoadConfig($configFile)
	{
		// backwards compatibility for people who have been
		// making use of these variables in their title
		global $gitphp_version, $gitphp_appstring;

		if (!is_file($configFile)) {
			throw new GitPHP_MessageException('Could not load config file ' . $configFile, true, 500);
		}

		if (!include($configFile)) {
			throw new GitPHP_MessageException('Could not read config file ' . $configFile, true, 500);
		}

		if (isset($gitphp_conf) && is_array($gitphp_conf))
			$this->values = array_merge($this->values, $gitphp_conf);

		$this->configs[] = $configFile;
	}

	/**
	 * ClearConfig
	 *
	 * Clears all config values
	 *
	 * @access public
	 */
	public function ClearConfig()
	{
		$this->values = array();
		$this->configs = array();
	}

	/**
	 * GetValue
	 *
	 * Gets a config value
	 *
	 * @access public
	 * @param $key config key to fetch
	 * @param $default default config value to return
	 * @return mixed config value
	 */
	public function GetValue($key, $default = null)
	{
		if ($this->HasKey($key)) {
			return $this->values[$key];
		}
		return $default;
	}

	/**
	 * SetValue
	 *
	 * Sets a config value
	 *
	 * @access public
	 * @param string $key config key to set
	 * @param mixed $value value to set
	 */
	public function SetValue($key, $value)
	{
		if (empty($key)) {
			return;
		}
		if (empty($value)) {
			unset($this->values[$key]);
			return;
		}
		$this->values[$key] = $value;
	}

	/**
	 * HasKey
	 *
	 * Tests if the config has specified this key
	 *
	 * @access public
	 * @param string $key config key to find
	 * @return boolean true if key exists
	 */
	public function HasKey($key)
	{
		if (empty($key)) {
			return false;
		}
		return isset($this->values[$key]);
	}

}
