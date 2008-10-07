<?php

class JabberServerConf
{
	private $server;
	private $server_dns;
	private $server_port;
	private $webadmin_unsec_port;
	private $webadmin_sec_port;
	private $username;
	private $user_pwd;
	private $helga_bot;
	private $helga_service;
	private $lockroom_pwd;
	private $group_mng_active;

	/*
	 * Load Jabber Server config infos from the JabbeX XML configuration file.
	 */
	public function load_conf($conf_xml_file)
	{

		if( (!file_exists($conf_xml_file)) || (!$xml = simplexml_load_file($conf_xml_file)) ){
			throw new Exception("Unable to load configuration file $conf_xml_file.",3017);
		}
		else{

			$this->server = "".$xml->server["name"];
			$this->server_dns = "".$xml->server->server_uri;
			$this->server_port = "".$xml->server->server_port;

			$this->webadmin_unsec_port =  "".$xml->webadmin->unsecure_port;
			$this->webadmin_sec_port =  "".$xml->webadmin->secure_port;
			
			$this->username = "".$xml->auth->username;
			$this->user_pwd = "".$xml->auth->user_pwd;
			$this->lockmuc_pwd = "".$xml->auth->lockmuc_pwd;

			$this->helga_bot = "".$xml->helga->helga_bot;
			$this->helga_service = "".$xml->helga->helga_service;

			$this->group_mng_active = "".$xml->group_mng->active;
		}
	}

	public function get_server()
	{
		return $this->server;
	}

	public function get_server_dns()
	{
		return $this->server_dns;
	}

	public function get_server_port()
	{
		return $this->server_port;
	}

	public function get_webadmin_unsec_port(){
		return $this->webadmin_unsec_port;
	}

	public function get_webadmin_sec_port(){
		return $this->webadmin_sec_port;
	}
	
	public function get_username()
	{
		return $this->username;
	}

	public function get_user_pwd()
	{
		return $this->user_pwd;
	}

	public function get_helga_jid(){ // !!!This is exclusive for Openfire
		return "".$this->helga_bot."@".$this->helga_service.".".$this->server_dns;
	}

	public function get_lockmuc_pwd()
	{
		return $this->lockroom_pwd;
	}

	public function get_group_mng_active(){
		return $this->group_mng_active;
	}
}
?>