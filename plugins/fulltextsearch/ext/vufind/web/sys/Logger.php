<?php
/**
 *
 * Copyright (C) Villanova University 2009.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */
require_once 'Log.php';
 
/**
 * VuFind Logger Class
 *
 * This is a wrapper class to load configuration options and forward log messages
 * to the user-specified logging mechanisms using the PEAR Log framework.  See
 * the comments in web/conf/config.ini for details on how logging is configured.
 *
 * @author      Demian Katz <demian.katz@villanova.edu>
 * @access      public
 */
class Logger
{
    private $logMethods = array();
    
    /**
     * Constructor
     *
     * Sets up logging functionality using settings from config.ini.
     *
     * @access  public
     */
    public function __construct()
    {
        global $configArray;
        
        // Activate database logging, if applicable:
        if (isset($configArray['Logging']['database'])) {
            $config = array('dsn' => $configArray['Database']['database']);
            $this->addLogger($configArray['Logging']['database'], 'sql', $config);
        }
        
        // Activate file logging, if applicable:
        if (isset($configArray['Logging']['file'])) {
            $this->addLogger($configArray['Logging']['file'], 'file');
        }
        
        // Activate email logging, if applicable:
        if (isset($configArray['Logging']['email'])) {
            $config = array('subject' => 'VuFind Log Message');
            $this->addLogger($configArray['Logging']['email'], 'mail', $config);
        }
    }

    /**
     * Given a setting from the config file and a logger type, add the appropriate
     * PEAR logger object and associated verbosity settings to our $logMethods 
     * array if logging should be enabled using this logging method.
     *
     * @access private
     * @param   string  $configString   The line from config.ini.
     * @param   string  $loggerType     The type of PEAR Log object to create.
     * @param   array   $config         Extra settings for Log factory method.
     */
    private function addLogger($configString, $loggerType, $config = array())
    {
        if ($configString) {
            // Construct the log object:
            list($name, $levels) = explode(':', $configString);
            $ident = 'vufind';
            $logger = Log::factory($loggerType, $name, $ident, $config);

            // Only add the object to our array if it exists and at least one level
            // of logging was specified:
            $levels = $this->parseLevels($levels);
            if ($levels['mask'] != PEAR_LOG_NONE && $logger) {
                // Set a mask to only log message types requested by the user:
                $logger->setMask($levels['mask']);
                
                // Store the logger object and verbosity information:
                $this->logMethods[] = array(
                    'logger' => $logger, 
                    'verbosity' => $levels['verbosity']
                );
            }
        }
    }
    
    /**
     * Given a comma-separated level string from the config file, parse it into
     * an array containing a PEAR-style numeric level value ('mask') and a verbosity
     * breakdown indexed by PEAR logging type ('verbosity').
     *
     * @access  private
     * @param   string  $levelStr   The level string from the config file
     * @return  array               Mask and verbosity array parsed from the config
     */
    private function parseLevels($levelStr)
    {
        // Initialize the components of the return value to assume no logging:
        $mask = PEAR_LOG_NONE;
        $verbosity = array();
        
        // Loop through the individual level settings from the config file:
        $settings = explode(',', $levelStr);
        foreach ($settings as $setting) {
            // Each section of the config string may have a detail level on the
            // end following a dash -- parse this out and default to 1 if no valid
            // setting is found:
            list($logType, $currentVerbosity) = explode('-', $setting);
            if (empty($currentVerbosity) || !is_numeric($currentVerbosity)) {
                $currentVerbosity = 1;
            }
            
            // Fill in the mask bits and detail level array based on the level 
            // string provided by the user.
            //
            // Note that each config file level string actually corresponds with two
            // PEAR constants -- granularity was reduced to simplify configuration.
            // You can easily change this and create custom logging levels by 
            // modifying this switch statement.
            switch (strtolower(trim($logType))) {
                case 'alert':
                    $mask |= Log::MASK(PEAR_LOG_EMERG);
                    $mask |= Log::MASK(PEAR_LOG_ALERT);
                    $verbosity[PEAR_LOG_EMERG] = $currentVerbosity;
                    $verbosity[PEAR_LOG_ALERT] = $currentVerbosity;
                    break;
                case 'error':
                    $mask |= Log::MASK(PEAR_LOG_CRIT);
                    $mask |= Log::MASK(PEAR_LOG_ERR);
                    $verbosity[PEAR_LOG_CRIT] = $currentVerbosity;
                    $verbosity[PEAR_LOG_ERR] = $currentVerbosity;
                    break;
                case 'notice':
                    $mask |= Log::MASK(PEAR_LOG_WARNING);
                    $mask |= Log::MASK(PEAR_LOG_NOTICE);
                    $verbosity[PEAR_LOG_WARNING] = $currentVerbosity;
                    $verbosity[PEAR_LOG_NOTICE] = $currentVerbosity;
                    break;
                case 'debug':
                    $mask |= Log::MASK(PEAR_LOG_INFO);
                    $mask |= Log::MASK(PEAR_LOG_DEBUG);
                    $verbosity[PEAR_LOG_INFO] = $currentVerbosity;
                    $verbosity[PEAR_LOG_DEBUG] = $currentVerbosity;
                    break;
            }
        }
        
        return array('mask' => $mask, 'verbosity' => $verbosity);
    }
    
    /**
     * Given an array of possible log messages and the maximum verbosity, return 
     * the most appropriate message from the array (or false if all array entries 
     * are more verbose than the most detailed level desired).
     *
     * @access  private
     * @param   array   $messages   The array of messages indexed by detail level
     * @param   int     $verbosity  The highest verbosity level we may log
     * @return  mixed               Message to log or false for no message
     */
    private function pickMsg($messages, $verbosity)
    {
        // Initialize two key variables: the best verbosity level match found so
        // far, and the message we have picked out.
        $bestVerb = -1;
        $chosenMessage = false;
        
        // Loop through all possible messages and try to pick the best match:
        foreach ($messages as $currentVerb => $currentMessage) {
            // Save the current message if it is more verbose than the best
            // previously-found match without exceeding the user-specified limit.
            if ($currentVerb <= $verbosity && $currentVerb > $bestVerb) {
                $bestVerb = $currentVerb;
                $chosenMessage = $currentMessage;
            }
        }
        
        // Send back the best match we found in our loop:
        return $chosenMessage;
    }
    
    /**
     * Log a message to all active loggers.  If you pass in a string as a message,
     * this will log that string regardless of the detail level requested by the
     * user.  If you pass in an array indexed by verbosity level (a value between
     * 1 and 5, with 5 being more detailed than 1), it will log the highest index 
     * that is equal to or less than the user's requested verbosity level.
     *
     * Sample parameter sets:
     *
     * // This will be logged as long as the user has "notice" logging turned on,
     * // regardless of verbosity level:
     * $msg = "This is a string.";
     * $level = PEAR_LOG_NOTICE;
     *
     * // This will cause the user's current verbosity level to be logged, as long
     * // as they have "error" logging turned on:
     * $msg = array(1 => "1", 2 => "2", 3 => "3", 4 => "4", 5 => "5");
     * $level = PEAR_LOG_ERR;
     *
     * // This message will only be logged if the user's current verbosity level is
     * // set to 4 or higher and they have "debug" logging turned on:
     * $msg = array(4 => "This is a debug message of little importance.");
     * $level = PEAR_LOG_DEBUG;
     *
     * @access  public
     * @param   mixed   $msg        Log message (string or array of detail levels).
     * @param   int     $pearLevel  The PEAR logging level of the message
     */
    public function log($msg, $pearLevel)
    {
        // Evaluate each possible logging method separately; we may have different
        // verbosity and mask settings at each level.
        foreach ($this->logMethods as $method) {
            // If $msg is an array, we need to pick the actual message to log:
            if (is_array($msg)) {
                $msgToLog = $this->pickMsg($msg, $method['verbosity'][$pearLevel]);
                
                // If we have no message to log, it means that no options were
                // available within the specified verbosity limit -- move on to the
                // next logging method:
                if (empty($msgToLog)) {
                    continue;
                }
            } else {
                // If $msg is not an array, we just need to log it as-is:
                $msgToLog = $msg;
            }
            
            // Write the message to the log:
            $method['logger']->open();
            $method['logger']->log($msgToLog, $pearLevel);
            $method['logger']->close();
        }
    }
}
?>