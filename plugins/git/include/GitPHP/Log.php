<?php
/**
 * GitPHP Log
 *
 * Logging class
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 */

/**
 * Logging class
 *
 * @package GitPHP
 */
class GitPHP_Log
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
	 * enabled
	 *
	 * Stores whether logging is enabled
	 *
	 * @access protected
	 */
	protected $enabled = false;

	/**
	 * benchmark
	 *
	 * Stores whether benchmarking is enabled
	 *
	 * @access protected
	 */
	protected $benchmark = false;

	/**
	 * startTime
	 *
	 * Stores the starting instant
	 *
	 * @access protected
	 */
	protected $startTime;

	/**
	 * startMem
	 *
	 * Stores the starting memory
	 *
	 * @access protected
	 */
	protected $startMem;

	/**
	 * entries
	 *
	 * Stores the log entries
	 *
	 * @access protected
	 */
	protected $entries = array();

	/**
	 * GetInstance
	 *
	 * Returns the singleton instance
	 *
	 * @access public
	 * @static
	 * @return mixed instance of logging clas
	 */
	public static function GetInstance()
	{
		if (!self::$instance) {
			self::$instance = new GitPHP_Log();
		}

		return self::$instance;
	}

	/**
	 * __construct
	 *
	 * Constructor
	 *
	 * @access public
	 * @return Log object
	 */
	public function __construct()
	{
		$this->startTime = microtime(true);
		$this->startMem = memory_get_usage();

		$this->enabled = GitPHP_Config::GetInstance()->GetValue('debug', false);
		$this->benchmark = GitPHP_Config::GetInstance()->GetValue('benchmark', false);
	}

	/**
	 * SetStartTime
	 *
	 * Sets start time
	 *
	 * @access public
	 * @param float $start starting microtime
	 */
	public function SetStartTime($start)
	{
		$this->startTime = $start;
	}

	/**
	 * SetStartMemory
	 *
	 * Sets start memory
	 *
	 * @access public
	 * @param integer $start starting memory
	 */
	public function SetStartMemory($start)
	{
		$this->startMem = $start;
	}

	/**
	 * Log
	 *
	 * Log an entry
	 *
	 * @access public
	 * @param string $message message to log
	 */
	public function Log($message)
	{
		if (!$this->enabled)
			return;

		$entry = array();
		
		if ($this->benchmark) {
			$entry['time'] = microtime(true);
			$entry['mem'] = memory_get_usage();
		}

		$entry['msg'] = $message;
		$this->entries[] = $entry;
	}

	/**
	 * GetEnabled
	 *
	 * Gets whether logging is enabled
	 *
	 * @access public
	 * @return boolean true if logging is enabled
	 */
	public function GetEnabled()
	{
		return $this->enabled;
	}

	/**
	 * SetEnabled
	 *
	 * Sets whether logging is enabled
	 *
	 * @access public
	 * @param boolean $enable true if logging is enabled
	 */
	public function SetEnabled($enable)
	{
		$this->enabled = $enable;
	}

	/**
	 * GetBenchmark
	 *
	 * Gets whether benchmarking is enabled
	 *
	 * @access public
	 * @return boolean true if benchmarking is enabled
	 */
	public function GetBenchmark()
	{
		return $this->benchmark;
	}

	/**
	 * SetBenchmark
	 *
	 * Sets whether benchmarking is enabled
	 *
	 * @access public
	 * @param boolean $bench true if benchmarking is enabled
	 */
	public function SetBenchmark($bench)
	{
		$this->benchmark = $bench;
	}

	/**
	 * GetEntries
	 *
	 * Calculates times and gets log entries
	 *
	 * @access public
	 * @return array log entries
	 */
	public function GetEntries()
	{
		$data = array();
	
		if ($this->enabled) {

			if ($this->benchmark) {
				$endTime = microtime(true);
				$endMem = memory_get_usage();

				$lastTime = $this->startTime;
				$lastMem = $this->startMem;

				$data[] = 'DEBUG: [' . $this->startTime . '] [' . $this->startMem . ' bytes] Start';

			}

			foreach ($this->entries as $entry) {
				if ($this->benchmark) {
					$data[] = 'DEBUG: [' . $entry['time'] . '] [' . ($entry['time'] - $this->startTime) . ' sec since start] [' . ($entry['time'] - $lastTime) . ' sec since last] [' . $entry['mem'] . ' bytes] [' . ($entry['mem'] - $this->startMem) . ' bytes since start] [' . ($entry['mem'] - $lastMem) . ' bytes since last] ' . $entry['msg'];
					$lastTime = $entry['time'];
					$lastMem = $entry['mem'];
				} else {
					$data[] = 'DEBUG: ' . $entry['msg'];
				}
			}

			if ($this->benchmark) {
				$data[] = '[' . $endTime . '] [' . ($endTime - $this->startTime) . ' sec since start] [' . ($endTime - $lastTime) . ' sec since last] [' . $endMem . ' bytes] [' . ($endMem - $this->startMem) . ' bytes since start] [' . ($endMem - $lastMem) . ' bytes since last] End';
			}
		}

		return $data;
	}

}
