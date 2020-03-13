<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  Data Access Object for DB Tables
 */
class DBTablesDao extends DataAccessObject
{
    /**
    * Gets a log files
    * @return object a result object
    */
    public function searchAll()
    {
        $sql = "SHOW TABLES";
        return $this->retrieve($sql);
    }

    public function analyzeTable($name)
    {
        $sql = "ANALYZE TABLE " . $name;
        return $this->retrieve($sql);
    }

    public function checkTable($name)
    {
        $sql = "CHECK TABLE " . $name;
        return $this->retrieve($sql);
    }

    public function convertToUTF8($name)
    {
        $field_changes = array();
        $sql = "SHOW FULL COLUMNS FROM " . $name;
        foreach ($this->retrieve($sql) as $field) {
            if ($field['Collation']) {
                if (preg_match('/_bin$/', $field['Collation'])) {
                    $collate = 'bin';
                } else {
                    $collate = 'general_ci';
                }
                $field_changes[] = " CHANGE " . $field['Field'] . " " .
                        $field['Field'] . " " .
                        $field['Type'] . " CHARACTER SET utf8 COLLATE utf8_" . $collate . " " .
                        (strtolower($field['Null']) == 'no' ? 'NOT NULL' : 'NULL') . " " .
                        ($field['Default'] ? "DEFAULT '" . $field['Default'] . "'" : '');
            }
        }
        $sql = "ALTER TABLE " . $name . " ";
        if (count($field_changes)) {
            $sql .= implode(",\n", $field_changes) . ",\n";
        }
        $sql .= " DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";
        return $this->update($sql);
    }

    /**
    * Gets a log files
    * @return object a result object
    */
    public function searchByName($name)
    {
        $sql = "DESC " . $name;
        return $this->retrieve($sql);
    }

    public function updateFromFile($filename)
    {
        $file_content = file($filename);
        $query = "";
        foreach ($file_content as $sql_line) {
            if (trim($sql_line) != "" && strpos($sql_line, "--") === false) {
                $query .= $sql_line;
                if (preg_match("/;\s*(\r\n|\n|$)/", $sql_line)) {
                    if (!$this->update($query)) {
                        return false;
                    }
                    $query = "";
                }
            }
        }
        return true;
    }
}
