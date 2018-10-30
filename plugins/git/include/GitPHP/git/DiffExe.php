<?php

namespace Tuleap\Git\GitPHP;

/**
 * GitPHP Diff Exe
 *
 * Diff executable class
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

/**
 * DiffExe class
 *
 * Class to handle working with the diff executable
 */
class DiffExe
{

    /**
     * binary
     *
     * Stores the binary path internally
     *
     * @access protected
     */
    protected $binary = '/usr/bin/diff';

    /**
     * unified
     *
     * Stores whether diff creates unified patches
     *
     * @access protected
     */
    protected $unified = true;

    /**
     * showFunction
     *
     * Stores whether to show the function each change is in
     *
     * @access protected
     */
    protected $showFunction = true;

    /**
     * GetBinary
     *
     * Gets the binary for this executable
     *
     * @return string binary
     * @access public
     */
    public function GetBinary() // @codingStandardsIgnoreLine
    {
        return $this->binary;
    }

    /**
     * GetUnified
     *
     * Gets whether diff is running in unified mode
     *
     * @access public
     * @return mixed boolean or number of context lines
     */
    public function GetUnified() // @codingStandardsIgnoreLine
    {
        return $this->unified;
    }

    /**
     * SetUnified
     *
     * Sets whether this diff is running in unified mode
     *
     * @access public
     * @param mixed $unified true or false, or number of context lines
     */
    public function SetUnified($unified) // @codingStandardsIgnoreLine
    {
        $this->unified = $unified;
    }

    /**
     * GetShowFunction
     *
     * Gets whether this diff is showing the function
     *
     * @access public
     * @return boolean true if showing function
     */
    public function GetShowFunction() // @codingStandardsIgnoreLine
    {
        return $this->showFunction;
    }

    /**
     * SetShowFunction
     *
     * Sets whether this diff is showing the function
     *
     * @access public
     * @param boolean $show true to show
     */
    public function SetShowFunction($show) // @codingStandardsIgnoreLine
    {
        $this->showFunction = $show;
    }

    /**
     * Execute
     *
     * Runs diff
     *
     * @access public
     * @param string $fromFile source file
     * @param string $fromName source file display name
     * @param string $toFile destination file
     * @param string $toName destination file display name
     * @return string diff output
     */
    public function Execute($fromFile = null, $fromName = null, $toFile = null, $toName = null) // @codingStandardsIgnoreLine
    {
        if (empty($fromFile) && empty($toFile)) {
            return '';
        }

        if (empty($fromFile)) {
            $fromFile = '/dev/null';
        }

        if (empty($toFile)) {
            $toFile = '/dev/null';
        }

        $args = array();
        if ($this->unified) {
            if (is_numeric($this->unified)) {
                $args[] = '-U';
                $args[] = $this->unified;
            } else {
                $args[] = '-u';
            }

            $args[] = '-L';
            if (empty($fromName)) {
                $args[] = '"' . $fromFile . '"';
            } else {
                $args[] = '"' . $fromName . '"';
            }

            $args[] = '-L';
            if (empty($toName)) {
                $args[] = '"' . $toFile . '"';
            } else {
                $args[] = '"' . $toName . '"';
            }
        }
        if ($this->showFunction) {
            $args[] = '-p';
        }

        $args[] = $fromFile;
        $args[] = $toFile;

        return shell_exec($this->binary . ' ' . implode(' ', $args));
    }

    /**
     * Diff
     *
     * Convenience function to run diff with the default settings
     * and immediately discard the object
     *
     * @access public
     * @static
     * @param string $fromFile source file
     * @param string $fromName source file display name
     * @param string $toFile destination file
     * @param string $toName destination file display name
     * @return string diff output
     */
    public static function Diff($fromFile = null, $fromName = null, $toFile = null, $toName = null) // @codingStandardsIgnoreLine
    {
        $obj = new DiffExe();
        $ret = $obj->Execute($fromFile, $fromName, $toFile, $toName);
        unset($obj);
        return $ret;
    }
}
