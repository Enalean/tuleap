<?php
/**
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2006
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */

$legalHeaderFileName = 'legal_header_st.txt';
$author = 'Manuel VACELET';
$year   = '2006';
define('INDENT', '    ');

$dbFields = array('field_id', 'group_id', 'field_name', 'data_type', 'display_type', 'display_size', 'label', 'description', 'required', 'empty_ok', 'special', 'default_value');
$classFields = array('id', 'groupId', 'name', 'type', 'displayType', 'displaySize', 'label', 'description', 'isRequired', 'isEmptyAllowed', 'special', 'defaultValue');
$className = 'Docman_Metadata';

//
//
//

function dumpHeader($fd, $headerFile, $author, $year) {
    // php header
    fwrite($fd, "<?php\n");
    fwrite($fd, "/**\n");

    // legal header
    $headerContent = file($headerFile);
    foreach($headerContent as $line) {
        $line = preg_replace('/%author%/', $author, $line);
        $line = preg_replace('/%year%/', $year, $line);
        $line = ' * '.$line;
        fwrite($fd, $line);
    }

    // end of comment
    fwrite($fd, " * \n");
    fwrite($fd, " * \\n");
    fwrite($fd, " */\n");
    fwrite($fd, "\n");
}

function footer($fd) {
    fwrite($fd, "?>\n");
}

function writeAttributs($fd, $classFields) {
    foreach($classFields as $field) {
        fwrite($fd, INDENT."var \$$field;\n");
    }
    fwrite($fd, "\n");
}

function createFunction($fd, $name, $params, $content) {
    $func = INDENT.'function '.$name.'('.implode(', ', $params).') {'."\n";
    $func .= $content;
    $func .= INDENT.'}'."\n";
    return $func;
}

function writeConstructor($fd, $className, $classFields) {
    $content = '';
    foreach($classFields as $field) {
        $content .= INDENT.INDENT.'$this->'.$field.' = null;'."\n";
    }    
    $func = createFunction($fd, $className, array(), $content);
    fwrite($fd, $func);
    fwrite($fd, "\n");
}

function writeGettersAndSetters($fd, $classFields) {    
    foreach($classFields as $field) {
        // getter
        $fname = 'set'.ucfirst($field);
        $content = INDENT.INDENT.'$this->'.$field.' = $v;'."\n";
        $func = createFunction($fd, $fname, array('$v'), $content);
        fwrite($fd, $func);

        // setter
        $content = '';
        $fname = 'get'.ucfirst($field);
        $content = INDENT.INDENT.'return $this->'.$field.';'."\n";
        $func = createFunction($fd, $fname, array(), $content);
        fwrite($fd, $func);

        fwrite($fd, "\n");
    }
}

function writeInitFromRow($fd, $classFields, $dbFields) {
    $content = '';
    foreach($classFields as $k => $field) {
        $content .= INDENT.INDENT.'if(isset($row[\''.$dbFields[$k].'\'])) $this->'.$field.' = $row[\''.$dbFields[$k].'\'];'."\n";
    }    
    $func = createFunction($fd, 'initFromRow', array('$row'), $content);
    fwrite($fd, $func);
}

function createClass($fd, $className, $classFields, $dbFields, $extends='') {
    fwrite($fd, "class $className {\n");

    writeAttributs($fd, $classFields);

    writeConstructor($fd, $className, $classFields);

    writeGettersAndSetters($fd, $classFields);

    writeInitFromRow($fd, $classFields, $dbFields);

    fwrite($fd, "}\n");
}

$cFileName = $className.'.class.php';

$cFd = fopen($cFileName, 'w');

dumpHeader($cFd, $legalHeaderFileName, $author, $year);

createClass($cFd, $className, $classFields, $dbFields);

footer($cFd);

fclose($cFd);

?>
