<?php

require_once('include/DataAccessObject.class.php');

/**
 *  Data Access Object for DB Tables
 */
class DBTablesDao extends DataAccessObject {
    /**
    * Constructs the DBTablesDao
    * @param $da instance of the DataAccess class
    */
    function DBTablesDao( & $da ) {
        DataAccessObject::DataAccessObject($da);
    }

    /**
    * Gets a log files
    * @return object a result object
    */
    function &searchAll() {
        $sql="SHOW TABLES";
        return $this->retrieve($sql);
    }
    
    function analyzeTable($name) {
        $sql = "ANALYZE TABLE ".$name;
        return $this->retrieve($sql);
    }
    
    function convertToUTF8($name) {
        $sql = "SHOW FULL COLUMNS FROM ".$name;
        foreach($this->retrieve($sql) as $field) {
            if ($field['Collation']) {
                if (preg_match('/_bin$/', $field['Collation'])) {
                    $collate = 'bin';
                } else {
                    $collate = 'general_ci';
                }
                $sql = "ALTER TABLE ". $name ." CHANGE ". $field['Field'] ." ". 
                        $field['Field'] ." ". 
                        $field['Type'] ." CHARACTER SET utf8 COLLATE utf8_". $collate ." ".
                        ($field['Null'] == 'No' ? 'NOT NULL' : 'NULL') ." ".
                        ($field['Default'] ? "DEFAULT '". $field['Default'] ."'" : '');
                echo $sql."\n";
                $this->update($sql);
            }
        }
        $sql = "ALTER TABLE ". $name ." DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";
        echo $sql."\n";
        return $this->update($sql);
    }
    
    /**
    * Gets a log files
    * @return object a result object
    */
    function searchByName($name) {
        $sql = "DESC ".$name;
        return $this->retrieve($sql);
    }
    
    function updateFromFile($filename) {
        $file_content = file($filename);
        $query = "";
        foreach($file_content as $sql_line){
            if(trim($sql_line) != "" && strpos($sql_line, "--") === false){
                $query .= $sql_line;
                if(preg_match("/;\s*(\r\n|\n|$)/", $sql_line)){
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
?>
