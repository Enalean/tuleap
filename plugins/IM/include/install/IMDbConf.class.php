<?php
define("XML_FILE",dirname(__FILE__)."/../../etc/IM.inc.dist.xml");
class IMDbConf {

    private $codex_db_name;
    private $openfire_db_name;
    private $openfire_host_name;
    private $openfire_db_password;
    
    function IMDbConf($conf_xml_file=XML_FILE) {
    	$this->load_db_conf($conf_xml_file);
    }
    
    private function load_db_conf($conf_xml_file){

		if(!$xml = @simplexml_load_file($conf_xml_file)){
			//throw new Exception("Unable to load data base configuration $conf_xml_file.");
			echo "Unable to load data base configuration $conf_xml_file.";
			return;
		}
		else{

			$this->codex_db_name = "".$xml->codex_db->name;
			
			$this->openfire_db_name = "".$xml->openfire_db->name;
			$this->openfire_host_name = "".$xml->openfire_db->host;
			$this->openfire_db_password =  "".$xml->openfire_db->password;
			
		}
	}
	public function get_codex_db_name () {
		return $this->codex_db_name;
	}
	
	public function get_openfire_db_name () {
		return $this->openfire_db_name;
	}
	
	public function get_openfire_host_name () {
		return $this->openfire_host_name;
	}
	
	public function get_openfire_db_password () {
		return $this->openfire_db_password;
	}
}
?>