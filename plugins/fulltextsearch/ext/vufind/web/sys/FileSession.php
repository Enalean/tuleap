<?php

require_once 'SessionInterface.php';

class FileSession extends SessionInterface {

    static private $path;
    
    public function init($lt) {
        global $configArray;
        
        // Set defaults if nothing set in config file.
        self::$path= isset($configArray['Session']['file_save_path']) ?
            $configArray['Session']['file_save_path'] : '/tmp/vufind_sessions';

        // Die if the session directory does not exist and cannot be created.
        if (!file_exists(self::$path) || !is_dir(self::$path)) {
            if (!@mkdir(self::$path)) {
                PEAR::raiseError(new PEAR_Error("Cannot access session save path: " .
                    self::$path));
            }
        }
                        
        // Call standard session initialization from this point.
        parent::init($lt);
    }

    static public function read($sess_id)
    {
        $sess_file = self::$path . '/sess_' . $sess_id;
        return (string) @file_get_contents($sess_file);
    }
   
    static public function write($sess_id, $data)
    {
        $sess_file = self::$path . '/sess_' . $sess_id;
        if ($fp = @fopen($sess_file, "w")) {
            $return = fwrite($fp, $data);
            fclose($fp);
            return $return;
        } else {
            return(false);
        }
    }
    
    static public function destroy($sess_id)
    {
        // Perform standard actions required by all session methods:
        parent::destroy($sess_id);
        
        // Perform file-specific cleanup:
        $sess_file = self::$path . '/sess_' . $sess_id;
        return(@unlink($sess_file));
    }
    
    static public function gc($maxlifetime)
    {
        foreach (glob(self::$path . "/sess_*") as $filename) {
            if (filemtime($filename) + $maxlifetime < time()) {
                @unlink($filename);
            }
        }
        return true;
    }
}

?>
