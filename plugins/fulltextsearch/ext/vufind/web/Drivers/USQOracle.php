<?php

class oracle_connection {
  // Database Handle
  private $db_handle;

  // Error information
  private $last_error;
  private $last_error_type;
  private $last_error_fields;
  private $last_sql;

  /**
   * *************************
   *   Connection
   */
  public function __construct($username, $password, $tns) {
    $this->clear_error();
    $tmp = error_reporting(1);
    if ($this->db_handle = @oci_connect($username, $password, $tns)) {
      error_reporting($tmp);
      $this->audit_id = 0;
      $this->detail_id = 0;
    } else {
      error_reporting($tmp);
      $this->handle_error('connect', oci_error());
      return false;
    }
  }
  public function get_handle() {return $this->db_handle;}
  public function __destruct() {oci_close($this->db_handle);}

  /**
   * *************************
   *   Basic SQL functions
   */
  public function prepare($sql) {
    if ($parsed = @oci_parse($this->db_handle, $sql)) {
      return $parsed;
    } else {
      $this->handle_error('parsing', oci_error($this->db_handle), $sql);
      return false;
    }
  }
  public function prep_row_id() {
    if ($new_id = @oci_new_descriptor($this->db_handle, OCI_D_ROWID)) {
      return $new_id;
    } else {
      $this->handle_error('new_descriptor', oci_error($this->db_handle));
      return false;
    }
  }
  public function bind_param($parsed, $place_holder, $data, $data_type = 'string', $length = -1) {
    switch ($data_type) {
      case 'string':  $oracle_data_type = SQLT_CHR;  break;
      case 'integer': $oracle_data_type = SQLT_INT;  break;
      case 'float':   $oracle_data_type = SQLT_FLT;  break;
      case 'long':    $oracle_data_type = SQLT_LNG;  break;
      // Date is redundant since default is varchar,
      //  but it's here for clarity.
      case 'date':    $oracle_data_type = SQLT_CHR;  break;
      case 'row_id':  $oracle_data_type = SQLT_RDD;  break;
      case 'clob':    $oracle_data_type = SQLT_CLOB; break;
      case 'blob':    $oracle_data_type = SQLT_BLOB; break;
      default:        $oracle_data_type = SQLT_CHR;  break;
    }

    if (@oci_bind_by_name($parsed, $place_holder, $data, $length, $oracle_data_type)) {
      return true;
    } else {
      $this->handle_error('binding', oci_error());
      return false;
    }
  }
  // Same as above, but variable is parsed by reference to allow for correct functioning
  //  of the 'RETURNING' sql statement. Annoying, but putting it in two seperate functions
  //  allows the user to pass string literals into bind_param without a fatal error.
  public function return_param($parsed, $place_holder, &$data, $data_type = 'string', $length = -1) {
    switch ($data_type) {
      case 'string':  $oracle_data_type = SQLT_CHR;  break;
      case 'integer': $oracle_data_type = SQLT_INT;  break;
      case 'float':   $oracle_data_type = SQLT_FLT;  break;
      case 'long':    $oracle_data_type = SQLT_LNG;  break;
      // Date is redundant since default is varchar,
      //  but it's here for clarity.
      case 'date':    $oracle_data_type = SQLT_CHR;  break;
      case 'row_id':  $oracle_data_type = SQLT_RDD;  break;
      case 'clob':    $oracle_data_type = SQLT_CLOB; break;
      case 'blob':    $oracle_data_type = SQLT_BLOB; break;
      default:        $oracle_data_type = SQLT_CHR;  break;
    }

    if (@oci_bind_by_name($parsed, $place_holder, $data, $length, $oracle_data_type)) {
      return true;
    } else {
      $this->handle_error('binding', oci_error());
      return false;
    }
  }
  public function exec($parsed) {
    // OCI_DEFAULT == DO NOT COMMIT!!!
    if (@oci_execute($parsed, OCI_DEFAULT)) {
      return true;
    } else {
      $this->handle_error('executing', oci_error($parsed));
      return false;
    }
  }
  public function commit() {
    if (@oci_commit($this->db_handle)) {
      return true;
    } else {
      $this->handle_error('commit', oci_error($this->db_handle));
      return false;
    }
  }
  public function rollback() {
    if (@oci_rollback($this->db_handle)) {
      return true;
    } else {
      $this->handle_error('rollback', oci_error($this->db_handle));
      return false;
    }
  }
  public function free($parsed) {
    if (@oci_free_statement($parsed)) {
      return true;
    } else {
      $this->handle_error('free', oci_error($this->db_handle));
      return false;
    }
  }

  /**
   * *************************
   *   Template function
   *   - common functions we require
   */
  public function simple_select($sql, $fields = array()) {
    $stmt = $this->prepare($sql);
    foreach ($fields as $field => $datum) {
      list($column, $type) = split(":", $field);
      $this->bind_param($stmt, ":".$column, $datum, $type);
    }

    if ($this->exec($stmt)) {
      oci_fetch_all($stmt, $return_array, 0, -1, OCI_FETCHSTATEMENT_BY_ROW);
      $this->free($stmt);
      return $return_array;
    } else {
      $this->last_error_fields = $fields;
      $this->free($stmt);
      return false;
    }
  }
  public function simple_delete($table, $fields = array()) {
    $types   = array();
    $data    = array();
    $clauses = array();

    // Split all the fields up into arrays
    foreach ($fields as $field => $datum) {
      list($column, $type) = split(":", $field);
      $types[$column] = $type;
      $data[$column]  = $datum;
      $clauses[]      = "$column = :$column";
    }

    // Prepare the SQL for child table - turn the columns in placeholders for the bind
    $sql  = "DELETE FROM $table WHERE ".join(" AND ", $clauses);
    $delete = $this->prepare($sql);

    // Bind Variables
    foreach (array_keys($data) as $column) {
      $this->bind_param($delete, ":".$column, $data[$column], $types[$column]);
    }

    // Execute
    if ($this->exec($delete)) {
      $this->commit();
      $this->free($delete);
      return true;
    } else {
      $this->last_error_fields = $fields;
      $this->free($delete);
      return false;
    }
  }
  public function simple_insert($table, $fields = array()) {
    $types   = array();
    $data    = array();
    $columns = array();
    $values  = array();

    // Split all the fields up into arrays
    foreach ($fields as $field => $datum) {
      $tmp = split(":", $field);
      $column = array_shift($tmp);

      // For binding
      $types[$column] = array_shift($tmp);
      $data[$column]  = $datum;

      // For building the sql
      $columns[]      = $column;
      // Dates are special
      if (count($tmp) > 0 && !is_null($datum)) {
        $values[] = "TO_DATE(:$column, '".join(":", $tmp)."')";
      } else {
        $values[] = ":$column";
      }
    }

    $sql  = "INSERT INTO $table (".join(", ", $columns).") VALUES (".join(", ", $values).")";
    $insert = $this->prepare($sql);

    // Bind Variables
    foreach (array_keys($data) as $column) {
      $this->bind_param($insert, ":".$column, $data[$column], $types[$column]);
    }

    // Execute
    if ($this->exec($insert)) {
      $this->commit();
      $this->free($insert);
      return true;
    } else {
      $this->last_error_fields = $fields;
      $this->free($insert);
      return false;
    }
  }
  public function simple_sql($sql, $fields = array()) {
    $stmt = $this->prepare($sql);
    foreach ($fields as $field => $datum) {
      list($column, $type) = split(":", $field);
      $this->bind_param($stmt, ":".$column, $datum, $type);
    }
    if ($this->exec($stmt)) {
      $this->commit();
      $this->free($stmt);
      return true;
    } else {
      $this->last_error_fields = $fields;
      $this->free($stmt);
      return false;
    }
  }

  /**
   * *************************
   *   Error Handling
   */
  private function clear_error() {
    $this->last_error        = null;
    $this->last_error_type   = null;
    $this->last_error_fields = null;
    $this->last_sql          = null;
  }
  private function handle_error($type, $error, $sql = '') {
    // All we are doing at the moment is storing it
    $this->last_error        = $error;
    $this->last_error_type   = $type;
    $this->last_sql          = $sql;
  }

  /**
   * *************************
   *   Error Retrieval
   */
  // User can retrieve the raw error data
  public function get_last_error()      {return $this->last_error;}
  public function get_last_error_type() {return $this->last_error_type;}
  public function get_last_sql()        {return $this->last_sql;}

  // Or request it formatted as html output
  public function get_html_error() {
    if ($this->last_error == null) return "No error found!";

    // Generic stuff
    $output  = "<b>ORACLE ERROR</b><br/>\n";
    $output .= "Oracle '".$this->last_error_type."' Error<br />\n";
    $output .= "=============<br />\n";
    foreach($this->last_error as $key => $value) {$output .= "($key) => $value<br />\n";}

    // Anything special for this error type?
    switch ($this->last_error_type) {
      case 'parsing':
        $output .= "=============<br />\n";
        $output .= "Offset into SQL:<br />\n";
        $output .= substr($this->last_error['sqltext'], $this->last_error['offset'])."\n";
        break;
      case 'executing':
        $output .= "=============<br />\n";
        $output .= "Offset into SQL:<br />\n";
        $output .= substr($this->last_error['sqltext'], $this->last_error['offset'])."<br />\n";
        if (count($this->last_error_fields) > 0) {
          $output .= "=============<br />\n";
          $output .= "Bind Variables:<br />\n";
          foreach ($this->last_error_fields as $k => $l) {
            if (is_array($l)) {
              $output .= "$k => (".join(", ", $l).")<br />\n";
            } else {
              $output .= "$k => $l<br />\n";
            }
          }
        }
        break;
    }

    $this->clear_error();
    return $output;
  }
}
?>
