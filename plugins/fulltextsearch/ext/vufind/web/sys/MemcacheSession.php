<?php

require_once 'SessionInterface.php';

class MemcacheSession extends SessionInterface {

    static private $connection;
    
    public function init($lt) {
        global $configArray;
        
        // Set defaults if nothing set in config file.
        $host = isset($configArray['Session']['memcache_host']) ?
            $configArray['Session']['memcache_host'] : 'localhost';
        $port = isset($configArray['Session']['memcache_port']) ?
            $configArray['Session']['memcache_port'] : 11211;
        $timeout = isset($configArray['Session']['memcache_connection_timeout']) ?
            $configArray['Session']['memcache_connection_timeout'] : 1;
        
        // Connect to Memcache:
        self::$connection = new Memcache();
        if (!@self::$connection->connect($host, $port, $timeout)) {
            PEAR::raiseError(new PEAR_Error("Could not connect to Memcache (host = {$host}, port = {$port})."));
        }
            
        // Call standard session initialization from this point.
        parent::init($lt);
    }

    static public function read($sess_id)
    {
        return self::$connection->get("vufind_sessions/{$sess_id}");
    }
   
    static public function write($sess_id, $data)
    {
        return self::$connection->set("vufind_sessions/{$sess_id}", $data, 0, self::$lifetime);
    }
    
    static public function destroy($sess_id)
    {
        // Perform standard actions required by all session methods:
        parent::destroy($sess_id);
        
        // Perform Memcache-specific cleanup:
        return self::$connection->delete("vufind_sessions/{$sess_id}");
    }
}


?>
