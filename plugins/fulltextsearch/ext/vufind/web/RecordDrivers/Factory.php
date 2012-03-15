<?php
/**
 *
 * Copyright (C) Villanova University 2010.
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

/**
 * RecordDriverFactory Class
 *
 * This is a factory class to build record drivers for accessing metadata.
 *
 * @author      Demian Katz <demian.katz@villanova.edu>
 * @access      public
 */
class RecordDriverFactory {
    /**
     * initSearchObject
     *
     * This constructs a search object for the specified engine.
     *
     * @access  public
     * @param   array   $record     The fields retrieved from the Solr index.
     * @return  object              The record driver for handling the record.
     */
    static function initRecordDriver($record)
    {
        global $configArray;
        
        // Determine driver path based on record type:
        $driver = ucwords($record['recordtype']) . 'Record';
        $path = "{$configArray['Site']['local']}/RecordDrivers/{$driver}.php";
        
        // If we can't load the driver, fall back to the default, index-based one:
        if (!is_readable($path)) {
            $driver = 'IndexRecord';
            $path = "{$configArray['Site']['local']}/RecordDrivers/{$driver}.php";
        }
        
        // Build the object:
        require_once $path;
        if (class_exists($driver)) {
            $obj = new $driver($record);
            return $obj;
        }
        
        // If we got here, something went very wrong:
        PEAR::raiseError(new PEAR_Error("Problem loading record driver: {$driver}"));
    }
}
?>