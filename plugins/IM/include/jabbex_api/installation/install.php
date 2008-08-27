#!/usr/local/bin/php -q
<?php

class JabbeXInstaller {

	private $argc; // Command line ...
	private $argv; // ... arguments.

	private $mode = "help"; // help, automatic, or interactive
	private $arguments;		// Arguments parsed and organized.


	public function __construct($argc = null, $argv = null) {
		$this->argc = $argc;
		$this->argv = $argv;
	}
	
	function _db_connect($username, $pwd, $url, $port = null, $db){
		if(!$port) $port = "3306";

		mysql_connect($url.":".$port, $username, $pwd) or die(mysql_error());
		mysql_select_db($db) or die(mysql_error());

	}

	function _db_close(){
		mysql_close();
	}

	function get_mode(){
		return $this->mode;
	}

	/*
	 * Opens a file pointer to Standard In (/dev/stdin on Linux) and reads anything
	 * from this pointer up to 255 bytes, newline, or EOF. In this case a newline is
	 * most likely to occur. It then closes the file pointer and returns the data.
	 */
	function read() {
		$fp=fopen("/dev/stdin", "r");
		$input=fgets($fp, 255);
		fclose($fp);
		return str_replace("\n", "", $input);
	}

	/*
	 * Print usage help.
	 */
	function print_usage(){
		$usage_msg = 	"\n".
						"**************************************************************************************************************\n".
						"* Usage: php ". $this->argv[0] ." [-h|-i|-a [OPTIONS]] \n".
						"* Install Openfire Jabber server and configure it to integrate with CodeX.\n".
						"* \n".
						"* Mandatory arguments:\n".
						"* 	-h	Show this help message.\n".
						"* 	OR \n".
						"* 	-i	Execute in interactive mode and guide the user through the installation proccess.\n".
						"* 	OR \n".
						"* 	-a [OPTIONS]	Execute in automatic mode.\n".
						"* 	Mandatory arguments for automatic mode:\n".
						"* 		-orp	Root password for Openfire's DB server.\n".
						"* 		-uod	Username Openfire will use to access its DB.\n".
						"* 		-pod	Password for this user.\n".
						"* 		-ucd	Username Openfire will use to access CodeX's DB.\n".
						"* 		-pcd	Password for this user.\n".
						"* 		-odb	Openfire's DB complete JDBC URL (eg: jdbc:mysql://<db_server_url>:<db_port>/<db_name>).\n".
						"* 		-cdb	CodeX's DB complete JDBC URL (eg: jdbc:mysql://<db_server_url>:<db_port>/<db_name>).\n".
						"* 		-ouri	Openfire server URI (used by clients to connect to the server).\n".
						"* 		-gjx	CodeX group of the IM admins.\n".
						"* 		-ujx	Default JabbeX user's username (must be a member of the gjx).\n".
						"* 		-pjx	Password for this user.\n".
						"* 		-pmuc	Password for managing the MUC services.\n".
						"*\n".
						"*	Optional arguments for automatic mode:\n".
						"*		-dir	Openfire's installation directory (default: /opt/openfire).\n".
						"*		-etcdir	Openfire's installation directory (default: /etc/codex/plugins/IM/etc/).\n".
						"*		-ap		Unsecure web admin port (default: 9090).\n".
						"*		-aps	Secure web admin port (default: 9091).\n".
						"*		-odd	Openfire's DB driver (default: com.mysql.jdbc.Driver).\n".
						"*		-cdd	CodeX's DB driver (default: com.mysql.jdbc.Driver).\n".
						"*		-op		Jabber port (default: 5222).\n".
						"*		-hbot	Helga bot username (default: bot).\n".
						"*		-hsv	Helga service identifier (default: helga).\n".
						"*		-shrg	Activate shared group management [0|1] (default: 1).\n".
						"*		-rdb	Root username for DB servers (default: root).\n".
						"**************************************************************************************************************\n\n\n";
		/*
		 * 
		 */
		exit($usage_msg);
	}

	/*
	 * Creates the Openfire configuration file (conf/openfire.xml) based on the templated resources/openfire.tpl.xml. 
	 * This function uses the global parameters so you want to run comand_line_args and get_openfire_conf_path 
	 * prior to calling it.
	 */
	function create_of_conf_file(){
		
		print("Creating Openfire conf/openfire.xml configuration file...\n");
		
		$parameters = $this->arguments;
		$conf_file = $this->arguments["OF_XML_CONF"];

		$curent_dir = dirname(__FILE__);
		$template_file = $curent_dir."/resources/openfire.tpl.xml";

		if(!$xml = simplexml_load_file($template_file)){
			throw new Exception("Unable to load configuration file $template_file.",3017);
		}
		else{
			$xml->adminConsole->addChild('port', $parameters["ADMIN_PORT"]);
			$xml->adminConsole->addChild('securePort', $parameters["ADMIN_SEC_PORT"]);

			$xml->database->defaultProvider->addChild('driver',$parameters["OF_DB_DRIVER"]);
			$xml->database->defaultProvider->addChild('serverURL',$parameters["OF_DB_URI"]);
			$xml->database->defaultProvider->addChild('username',$parameters["USER_OF_DB"]);
			$xml->database->defaultProvider->addChild('password',$parameters["PWD_OF_DB"]);

			$xml->jdbcProvider->addChild('driver',$parameters["CD_DB_DRIVER"]);
			$xml->jdbcProvider->addChild('connectionString',$parameters["CD_DB_URI"]."?user=".$parameters["USER_CD_DB"]."&amp;password=".$parameters["PWD_CD_DB"]);

			if( $xml->asXML($conf_file) ){

				// Granting permission -rw-r--r-- 1 daemon daemon
				`chmod 644 $conf_file`;
				`chown daemon:daemon $conf_file`;
				//---

				print("\n");
				print("**********************************************\n");
				print("* The openfire.xml configuration file was    *\n");
				print("* successfully created inside the Openfire's *\n");
				print("* configuration directory.                   *\n");
				print("**********************************************\n");

			}
			else{
				exit("\nERROR: Unable to create the Openfire's configuration file.\n");
			}
		}
	}

	/*
	 * Parses an URI to extract the host URL, the port, and the DB name.
	 * Saves this info in the arguments global var.
	 */
	function explode_uri(){

		print("Parsing server information...\n");
		
		$databases = array("OF","CD");

		foreach($databases as $key => $value){

			$err_msg = "ERROR: Invalid URI ". $this->arguments[$value."_DB_URI"] .".\n";
				
			// Pattern: jdbc:mysql://<db_server_url>:<db_port>/<db_name>
			$uri = explode("//",$this->arguments[$value."_DB_URI"]);
				
			$uri = isset($uri[1]) ? $uri[1] : exit($err_msg);
			$uri = explode(":",$uri);
			$this->arguments[$value."_DB_HOST"] = isset($uri[0]) ? $uri[0] : exit($err_msg);
				
			$uri = isset($uri[1]) ? $uri[1] : exit($err_msg);
			$uri = explode("/",$uri);
			$this->arguments[$value."_DB_PORT"] = isset($uri[0]) ? $uri[0] : exit($err_msg);
			$this->arguments[$value."_DB_NAME"] = isset($uri[1]) ? $uri[1] : exit($err_msg);
		}
	}

	/*
	 * Used when mode=interactive in order to read users parameters.
	 */
	function read_parameters(){
		print("\nThe port used for unsecured Admin Console access [9090]:\n");
		$this->arguments["ADMIN_PORT"] = $this->read();
		if(empty($this->arguments["ADMIN_PORT"])) $this->arguments["ADMIN_PORT"] = "9090";

		print("\nThe port used for secured Admin Console access [9091]:\n");
		$this->arguments["ADMIN_SEC_PORT"] = $this->read();
		if(empty($this->arguments["ADMIN_SEC_PORT"])) $this->arguments["ADMIN_SEC_PORT"] = "9091";

		print("\nOpenfire's DB JDBC Driver [com.mysql.jdbc.Driver]:\n");
		$this->arguments["OF_DB_DRIVER"] = $this->read();
		if(empty($this->arguments["OF_DB_DRIVER"])) $this->arguments["OF_DB_DRIVER"] = "com.mysql.jdbc.Driver";

		print("\nOpenfire's DB complete URL (eg: jdbc:mysql://<db_server_url>:<db_port>/<db_name>):\n");
		$this->arguments["OF_DB_URI"] = $this->read();

		print("\nRoot password for Openfire's DB:\n");
		$this->arguments["ROOT_OF_DB"] = $this->read();

		print("\nUsername for accessing the Openfire's DB:\n");
		$this->arguments["USER_OF_DB"] = $this->read();

		print("\nThe password for this username (for the Openfire's DB):\n");
		$this->arguments["PWD_OF_DB"] = $this->read();

		print("\nCodeX's DB JDBC Driver [com.mysql.jdbc.Driver]:\n");
		$this->arguments["CD_DB_DRIVER"] = $this->read();
		if(empty($this->arguments["CD_DB_DRIVER"])) $this->arguments["CD_DB_DRIVER"] = "com.mysql.jdbc.Driver";

		print("\nCodeX's DB complete URL (eg: jdbc:mysql://<db_server_url>:<db_port>/<db_name>):\n");
		$this->arguments["CD_DB_URI"] = $this->read();

		print("\nUsername for accessing the CodeX's DB:\n");
		$this->arguments["USER_CD_DB"] = $this->read();

		print("\nThe password for this username (for the CodeX's DB):\n");
		$this->arguments["PWD_CD_DB"] = $this->read();

		print("\nJabber server URI:\n");
		$this->arguments["OF_JABBER_URI"] = $this->read();

		print("\nJabber server port [5222]:\n");
		$this->arguments["OF_PORT"] = $this->read();
		if(empty($this->arguments["OF_PORT"])) $this->arguments["OF_PORT"] = "5222";

		print("\nJabbex default user:\n");
		$this->arguments["USER_JABBEX"] = $this->read();

		print("\nThe password for this user:\n");
		$this->arguments["PWD_JABBEX"] = $this->read();

		print("\nThe CodeX group of IM admins:\n");
		$this->arguments["GROUP_JABBEX"] = $this->read();
		
		print("\nThe password for MUC management:\n");
		$this->arguments["PWD_MUC"] = $this->read();

		print("\nHelga bot's name [bot]:\n");
		$this->arguments["HELGA_BOT"] = $this->read();
		if(empty($this->arguments["HELGA_BOT"])) $this->arguments["HELGA_BOT"] = "bot";

		print("\nHelga service [helga]:\n");
		$this->arguments["HELGA_SERV"] = $this->read();
		if(empty($this->arguments["HELGA_SERV"])) $this->arguments["HELGA_SERV"] = "helga";

		print("\nActivate shared group management? (0/1) [1]:\n");
		$this->arguments["SHRG_ACTIVE"] = $this->read();
		if(empty($this->arguments["SHRG_ACTIVE"])) $this->arguments["SHRG_ACTIVE"] = "1";

		return true;
	}

	/*
	 * Checks if Openfire is running and if so shuts it down.
	 * Returns false and displays a warning msg if it couldn't retrieve the status.
	 * Returns true if success.
	 */
	function openfire_status(){
		$current_version = `rpm -qa | grep openfire`;
		if(empty($current_version)){
			exit("\nERROR: Openfire was not found. Please make sure Openfire is properly installed before running this script.\n");
		}
		else{
			 $of_dir = `rpm -qs openfire | grep 'openfire$' | grep -v etc | grep -v documentation`;
			 $of_dir = explode("normal",$of_dir);
			 $this->arguments["OPENFIRE_DIR"] = trim($of_dir[1]);
		}
		
		print("Checking Openfire current status...\n");
		
		$status = `/etc/init.d/openfire status 2>&1`;
		$status = trim($status);

		if(!strcmp($status,"openfire is running")){
			print `/etc/init.d/openfire stop`;
			return true;
		}
		else if(!strcmp($status,"openfire is not running")){
			return true;
		}
		else{
			if($this->mode === "interactive"){
				print("Unable to detect the Openfire current status. [C]ontinue if you are sure that Openfire is not running or [A]bort and shut it down.\n");
				do{
					$option = $this->read();

					if($option == "C"){
						$valid_option = true;
					}
					else if($option == "A"){
						exit("\nOperation aborted!\n");
					}
					else{
						$valid_option = false;
						print("\nPlease choose C or A\n");
					}
				} while(!$valid_option);
			}
			else{
				exit("\nERROR: Unable to detect Openfire current status.\n");
				return false;
			}
		}
	}

	/*
	 * Parse command line arguments.
	 */
	function command_line_args(){
		$clarg_ar = $this->argv;
		$script_name = array_shift($clarg_ar);
		$clarg = array_shift($clarg_ar);

		if($clarg === "-i"){
			$this->mode = "interactive";
		}
		else if($clarg === "-a"){
			$this->mode = "automatic";
			$arguments = array("-orp","-uod","-pod","-ucd","-pcd","-odb","-cdb"
			,"-ouri","-gjx","-ujx","-pjx","-pmuc");  // Requested arguments.
			$opt_arguments = array("-dir","-etcdir","-ap","-aps","-odd","-cdd"
			,"-op","-hbot","-hsv","-shrg");  // Optional arguments.

			$default_val = array(
						"-dir" => "/opt/openfire",
						"-etcdir" => "/etc/codex/plugins/IM/etc/",
						"-ap" => "9090",
						"-aps" => "9091",
						"-odd" => "com.mysql.jdbc.Driver",
						"-cdd" => "com.mysql.jdbc.Driver"
						
						,"-op" => "5222",
						"-hbot" => "bot",
						"-hsv" => "helga",
						"-shrg" => "1",

						"-rdb" => "root" /* root user for Openfire SGDB*/
						);

						// Fetch requested arguments
						foreach($arguments as $needle){
							$arg_key = array_search($needle,$clarg_ar);
							if( ($arg_key === null) || !isset($clarg_ar[$arg_key+1]) ){
								print("Argument $needle not found.\n");
								$this->print_usage();
							}
							else{
								$arguments_ar[$needle] = $clarg_ar[$arg_key+1];
								unset($clarg_ar[$arg_key]);
								unset($clarg_ar[$arg_key+1]);
							}
						}

						// Fetch optional arguments
						foreach($opt_arguments as $needle){
							$arg_key = array_search($needle,$clarg_ar);

							// Didn't found the arg, so use the default value.
							if( ($arg_key == null) || !isset($clarg_ar[$arg_key+1]) ){
								$arguments_ar[$needle] = $default_val[$needle];
							}
							else{
								$arguments_ar[$needle] = $clarg_ar[$arg_key+1];
								unset($clarg_ar[$arg_key]);
								unset($clarg_ar[$arg_key+1]);
							}
						}

						// Name the arguments with more meaningful names
						// *********************************************
						$mapping = array(	"-dir" => "OPENFIRE_DIR",
						"-etcdir" => "ETC_DIR",
						"-orp" => "ROOT_OF_DB",
						"-uod" => "USER_OF_DB",
						"-pod" => "PWD_OF_DB",
						"-ucd" => "USER_CD_DB",
						"-pcd" => "PWD_CD_DB",
						"-ap" => "ADMIN_PORT",
						"-aps" => "ADMIN_SEC_PORT",
						"-odd" => "OF_DB_DRIVER",
						"-odb" => "OF_DB_URI",
						"-cdd" => "CD_DB_DRIVER",
						"-cdb" => "CD_DB_URI"

						,"-ouri" => "OF_JABBER_URI",
						"-gjx" => "GROUP_JABBEX",
						"-ujx" => "USER_JABBEX",
						"-pjx" => "PWD_JABBEX",
						"-pmuc" => "PWD_MUC",
						"-op" => "OF_PORT",
						"-hbot" => "HELGA_BOT",
						"-hsv" => "HELGA_SERV",
						"-shrg" => "SHRG_ACTIVE"
					);
							
						foreach($mapping as $key => $value){
							$this->arguments[$value] = $arguments_ar[$key];
						}
						// ***********************************************

		}
		else{
			$this->mode = "help";
			$this->print_usage();
		}

		return true;
	}

	/*
	 * Used when mode=interactive to make the user aware of what s/he's gonna need
	 * to perform the installation process.
	 */
	function print_interactive_msg(){

		print(	"************************************************************************************************\n");
		print(	"* Before starting the installation process please make sure you have the following   \n");
		print(	"* information on hand:                                                               \n");
		print(	"* 	- Root password for Openfire's DB server.\n".
				"* 	- Username Openfire will use to access its DB.\n".
				"* 	- Password for this user.\n".
				"* 	- Username Openfire will use to access CodeX's DB.\n".
				"* 	- Password for this user.\n".
				"* 	- Openfire's DB complete URL (eg: jdbc:mysql://<db_server_url>:<db_port>/<db_name>).\n".
				"* 	- CodeX's DB complete URL (eg: jdbc:mysql://<db_server_url>:<db_port>/<db_name>).\n".
				"* 	- Openfire server URI (used by clients to connect to the server).\n".
				"* 	- CodeX group of the IM admins.\n".
				"* 	- Default JabbeX user's username (must be a member of the gjx).\n".
				"* 	- Password for this user.\n".
				"* 	- Password for managing the MUC services.\n");
		print(	"************************************************************************************************\n");
		print(	"\nPlease hit \"Enter\" to continue...\n");
		$this->read();

		return true;
	}

	/*
	 * Given the Openfire installation directory path, retrieves the openfire.xml path.
	 */
	function get_openfire_conf_path(){

		$openfire_dir = $this->arguments["OPENFIRE_DIR"];  // Used when mode = automatic

		do{
			$conf_xml_path = `find $openfire_dir | grep '/conf/openfire.xml$'`;
			$conf_dir = `find $openfire_dir | grep '/conf$'`;

			/*
			 * When mode = automatic
			 */
			if($this->mode === "automatic"){
				if($conf_dir){
					$conf_dir = trim($conf_dir);
					$this->arguments["OF_XML_CONF"] = $conf_dir."/openfire.xml";
					
					if($conf_xml_path){
						$conf_xml_path = trim($conf_xml_path);
						$result = `mv $conf_xml_path $conf_xml_path".bkp" 2>&1`;
						if($result){
							exit("\nERROR: Unable to rename $conf_xml_path to $conf_xml_path.bkp.\n $result\n");
						}
						else{
							return true;
						}
					}
				}
				else{
					exit("\nERROR: Unable to find the Openfire's conf/ directory.\n");
				}
			}
			/*
			 * END of automatic mode operations.
			 */

			/*
			 * Interactive mode.
			 */
			$custom_dir = false;
			$read_dir = false;

			// Didn't find the configuration file
			if(!$conf_dir){
				print("\nThe sub-directory \"conf\" was not found in the Openfire's installation directory.\n");
				print("Would you like to enter another Openfire's installation directory [D], to create the configuration file in a custom directory [C] or to abort the operation [A] ?\n");

				do{
					$option = $this->read();

					$valid_option = true;

					if($option == "C"){

						$custom_dir = true;

						do{
							print("\nPlease enter the complete path for the directory you want to use:\n");
							$conf_dir = $this->read();
							$conf_dir = trim($conf_dir);
							if(!file_exists($conf_dir)){
								print("This directory does not exist or is inaccessible!\n");
								$conf_dir = "";
							}
						} while(empty($conf_dir));

						$read_dir = false;

					}
					else if($option == "A"){
						exit("\nOperation aborted!\n");
					}
					else if($option == "D"){
						$read_dir = true;
					}
					else{
						$valid_option = false;
						print("\nPlease choose D, C or A\n");
					}
				} while(!$valid_option);
			}
			else{
				$read_dir = false;
				$conf_dir = trim($conf_dir);

				if($conf_xml_path){
					$conf_xml_path = trim($conf_xml_path);
					$result = `mv $conf_xml_path $conf_xml_path".bkp" 2>&1`;
					if($result){
						exit("\nERROR: Unable to rename $conf_xml_path to $conf_xml_path.bkp.\n $result\n");
					}
					else{
						$this->arguments["OF_XML_CONF"] = $conf_xml_path;
						return true;
					}
				}
			}
				
		} while ($read_dir);
	}

	/*
	 * Installs the Helga plug-in. Run install_openfire prior to calling this function.
	 */
	/*
	function install_helga(){
		
		print("Installing Helga plug-in...\n");
		
		$curent_dir = dirname(__FILE__);
		$openfire_dir = $this->arguments["OPENFIRE_DIR"];
		
		$helga_file = $curent_dir."/resources/helga/helga.jar";

		$plugins_path = `find $openfire_dir | grep '/plugins$'`;
		$plugins_path = trim($plugins_path);
		$result = `cp -u $helga_file $plugins_path  2>&1`;

		if(!$result){
			print("Helga plugin was successfully installed.\n");
		}
		else {
			print("ERROR: Unable to copy Helga plugin to the Openfire's plugins directory.");
			return false;
		}

		return true;
	}
	*/
	
	/*
	 * Checks whether a given property is set on the Openfire's DB.
	 * Returns the property value if so and FALSE if it doesn't exist.
	 */
	function _property_exists($property){
		$this->_db_connect($this->arguments["USER_OF_DB"],$this->arguments["PWD_OF_DB"],$this->arguments["OF_DB_HOST"],$this->arguments["OF_DB_PORT"],$this->arguments["OF_DB_NAME"]);
		
		$query = "SELECT * FROM `jiveProperty` WHERE `name` LIKE '$property'";
		$result = mysql_query($query) or die('ERROR: Unable to query DB.\n');
		return mysql_fetch_array($result);
		
	}

	/*
	 * Adds/updates a property's value to the Openfire's DB.
	 */
	function _add_property($property,$value){
		$this->_db_connect($this->arguments["USER_OF_DB"],$this->arguments["PWD_OF_DB"],$this->arguments["OF_DB_HOST"],$this->arguments["OF_DB_PORT"],$this->arguments["OF_DB_NAME"]);
		
		if( ! $this->_property_exists($property) ){
			$query = "INSERT INTO `openfire`.`jiveProperty` (`name`, `propValue`) VALUES ('$property', '$value')";
			mysql_query($query) or die('ERROR: Unable to insert property '.$property.' into the Openfire\'s DB.\n');
		}
		else{
			$query = "UPDATE `openfire`.`jiveProperty` SET `propValue` = '$value' WHERE `jiveProperty`.`name` = '$property'";
			mysql_query($query) or die('ERROR: Unable to update property '.$property.' in the Openfire\'s DB.\n');
		}
	}

	
	/*
	 * Adds the necessary properties to allow JabbeX's default user to manage
	 * Openfire.
	 */
	function configure_jabbex_user(){
		
		print("Configuring JabbeX default user...\n");
		
		// Add plugin.helga.group.admin = <JABBER_ADMIN_GROUP> to Openfire's properties.
		$this->_add_property("plugin.helga.group.admin",$this->arguments["GROUP_JABBEX"]);
		
		// Add xmpp.muc.create.jid = <JABBEX_USER_JID>
		$this->_add_property("xmpp.muc.create.jid",$this->arguments["USER_JABBEX"]."@".$this->arguments["OF_JABBER_URI"]);
		
		// Add xmpp.muc.sysadmin.jid = <JABBEX_USER_JID>
		$this->_add_property("xmpp.muc.sysadmin.jid",$this->arguments["USER_JABBEX"]."@".$this->arguments["OF_JABBER_URI"]);
		
		// Only admins can create MUC: xmpp.muc.create.anyone = true
		$this->_add_property("xmpp.muc.create.anyone","true");
	}

	/*
	 * The presence plugin is a service that provides simple presence information over HTTP.
	 * It is used to display an online status icon for a user on a web page.
	 */
/*
	function install_presence_plugin(){
				
		print("Installing Presence plug-in...\n");
		
		$curent_dir = dirname(__FILE__);
		$openfire_dir = $this->arguments["OPENFIRE_DIR"];
		
		$presence_file = $curent_dir."/resources/presence/presence.jar";

		$plugins_path = `find $openfire_dir | grep '/plugins$'`;
		$plugins_path = trim($plugins_path);
		$result = `cp -u $presence_file $plugins_path  2>&1`;

		if(!$result){
			print("Presence plugin was successfully installed.\n");
		}
		else {
			print("ERROR: Unable to copy Presence plugin to the Openfire's plugins directory.");
			return false;
		}

		return true;
	}
*/
	function configure_presence_plugin(){
		
		print("Configuring presence plug-in...\n");
		
		$this->_add_property("plugin.presence.public","true");
		$this->_add_property("plugin.presence.unavailable.status","Unavailable");
		
	}
	/*
	function install_subscription_plugin(){
				
		print("Installing Subscription plug-in...\n");
		
		$curent_dir = dirname(__FILE__);
		$openfire_dir = $this->arguments["OPENFIRE_DIR"];
		
		$plugin_file = $curent_dir."/resources/subscription/subscription.jar";

		$plugins_path = `find $openfire_dir | grep '/plugins$'`;
		$plugins_path = trim($plugins_path);
		$result = `cp -u $plugin_file $plugins_path  2>&1`;

		if(!$result){
			print("Subscription plugin was successfully installed.\n");
		}
		else {
			print("ERROR: Unable to copy Subscription plugin to the Openfire's plugins directory.");
			return false;
		}

		return true;
	}
*/
	function configure_subscription_plugin(){
		
		print("Configuring Subscription plug-in...\n");
		
		$this->_add_property("plugin.subscription.whiteList",$this->arguments["USER_JABBEX"]."@".$this->arguments["OF_JABBER_URI"]);
		$this->_add_property("plugin.subscription.type","accept");
		$this->_add_property("plugin.subscription.level","local");
		
	}	
	
	/*
	 * Creates the JabbeX configuration file jabbex_api/etc/jabbex_conf.xml based on
	 * the template file resources/jabbex_conf.tpl.xml.
	 */
	function configure_jabbex(){
		
		print("Configuring JabbeX properties...\n");
		
		$curent_dir = dirname(__FILE__);
		$template_file = $curent_dir."/resources/jabbex_conf.tpl.xml";

		$conf_str = file_get_contents($template_file);

		$values["__SERVER_URI__"] = $this->arguments["OF_JABBER_URI"];
		$values["__SERVER_PORT__"] = $this->arguments["OF_PORT"];
		$values["__USERNAME__"] = $this->arguments["USER_JABBEX"];
		$values["__USER_PWD__"] = $this->arguments["PWD_JABBEX"];
		$values["__LOCKMUC_PWD__"] = $this->arguments["PWD_MUC"];
		$values["__HELGA_BOT__"] = $this->arguments["HELGA_BOT"];
		$values["__HELGA_SERVICE__"] = $this->arguments["HELGA_SERV"];
		$values["__SHAREDGRP_ACTIVE__"] = $this->arguments["SHRG_ACTIVE"];
		$values["__UNSECURE_PORT__"] = $this->arguments["ADMIN_PORT"];
		$values["__SECURE_PORT__"] = $this->arguments["ADMIN_SEC_PORT"];
		
		foreach($values as $key => $value){
			$conf_str = str_replace("{".$key."}",$value,$conf_str);
		}

		$target_dir =  $this->arguments["ETC_DIR"];
		$file = $target_dir."/jabbex_conf.xml";

		if( $fp =  fopen($file,'w') ){
			if( fwrite($fp,$conf_str) ){

				fclose($fp);

				// Granting permission -rw-r--r-- 1 codexadm codexadm
				`chmod 644 $file`;
				`chown codexadm:codexadm $file`;
				//---

				print("\n");
				print("***********************************************\n");
				print("* The jabbex_conf.xml configuration file was  *\n");
				print("* successfully created inside the JabbeX etc/ *\n");
				print("* directory.                                  *\n");
				print("***********************************************\n");

			}
			else{
				fclose($fp);
				exit("ERROR: Unable to create the JabbeX configuration file.");
			}
		}
		else{
			exit("ERROR: Unable to create file $file \n");
		}
	}

	/*
	 * Installs Openfire using the RPM found at resources/openfire/.
	 */
	/*
	function install_openfire(){
		$current_version = `rpm -qa | grep openfire`;
		$curent_dir = dirname(__FILE__);

		// If Openfire is already installed...
		if($current_version)
		{
			// Version to be installed
			$my_version = `rpm $curent_dir/resources/openfire/*.rpm`;

			// Which version is the most up to date?
			if(strcmp($current_version,$my_version) >= 0){
				print("\n".trim($current_version)." is already installed in your system.\n Skiping the Openfire installation.\n");
				return true;
			}
			else{
				print("\nUpdating from $current_version to $my_version \nThis process may take some minutes. Please be patient!\n...\n");
				$result = `rpm -U $curent_dir/resources/openfire/*.rpm`;
			}
		}
		else{
			print("\nInstalling Openfire...\nThis process may take some minutes. Please be patient!\n...\n");
			$result = `rpm -ivh $curent_dir/resources/openfire/*.rpm`;
		}

		// Check if there was any error
		if( !(strpos($result,"error") === false) ){
			exit("ERROR: Unable to install Openfire.\n");
		}
		else{
			$this->arguments["OPENFIRE_DIR"] = "/opt/openfire";
			print("Openfire was properly installed! Thanks for your patience!\n");
			return true;
		}
	}
	*/
	
	/*
	 * Creates the Openfire's DB and its tables structure.
	 * This function uses global parameters so you want to run command_line_arguments prior to calling it.
	 */
	function create_openfire_db(){

		print("Creating Openfire DB tables...\n");
		
		$root = "root";
		$pwd = $this->arguments["ROOT_OF_DB"];

		$db_name = $this->arguments["OF_DB_NAME"];
		$openfire_dir = $this->arguments["OPENFIRE_DIR"];  // Used when mode = automatic
		$openfire_db_host = $this->arguments["OF_DB_HOST"];

		// Check if the DB already exists.
		$curr_db = `mysql --user=$root --password=$pwd --host=$openfire_db_host -e "show databases" | grep "^$db_name$"`;

		if( !(strpos($curr_db,"error") === false) ){
			exit("ERROR: Unable to create Openfire's DB.\n $result");
		}


		if( empty($curr_db) ){
			// Create db
			$result = `mysqladmin --user=$root --password=$pwd --host=$openfire_db_host create $db_name 2>&1`;
			// Check if there was any error
			if( !(strpos($result,"error") === false) ){
				exit("ERROR: Unable to create Openfire's DB.\n $result");
			}

			// Create tables
			$sql_script = `find $openfire_dir | grep '/resources/database/openfire_mysql.sql$'`;

			if($sql_script){
				$sql_script = trim($sql_script);
				$result = `cat $sql_script | mysql --user=$root --password=$pwd --host=$openfire_db_host  $db_name 2>&1`;
			}
			else{
				exit("ERROR: Unable to find Openfire's DB configuration script at resources/database/openfire_mysql.sql.\n");
			}

			// Check if there was any error
			if( !(strpos($result,"error") === false) ){
				exit("ERROR: Unable to create Openfire's DB tables.\n $result");
			}
			else{
				print("Openfire's DB tables were properly created!\n");
				return true;
			}
		}
		else{
			print("WARNING: The database $db_name already exists. Skipping DB creation.\n");
			return false;
		}
	}

	function configure_openfire(){
		print("Configuring Openfire properties...\n");
		
		$this->_add_property("xmpp.domain",$this->arguments["OF_JABBER_URI"]);
		$this->_add_property("xmpp.server.socket.active","false"); // Remote servers are not allowed to exchange packets with this server.
		$this->_add_property("register.inband","false"); // Users can not automatically create new accounts.
		$this->_add_property("register.password","false"); // Users are not allowed to change their password.
		$this->_add_property("xmpp.auth.anonymous","false"); // Only registered users may login.
		
		// The properties below were craping the performance of the jabber server.
		// It's better to do a fine tune manually depending on the system the server is running over.
		
		//$this->_add_property("cache.group.size","0"); // Shorten cache to increase response time when.
		//$this->_add_property("cache.userCache.size","0"); // DB modifications take place.
		//$this->_add_property("cache.userGroup.size","0"); //
		//$this->_add_property("cache.username2roster.size","0"); //
	}
}


$installer = new JabbeXInstaller($argc,$argv);

$installer->command_line_args();

$mode = $installer->get_mode();
if($mode === "interactive"){
	$installer->print_interactive_msg();
	$installer->read_parameters();
}
else if($mode === "automatic"){
}
else $installer->print_usage();

$installer->explode_uri();
//$installer->install_openfire();
$installer->openfire_status();
//$installer->install_helga();
//$installer->install_presence_plugin();
//$installer->install_subscription_plugin();

$installer->create_openfire_db();
$installer->configure_openfire();

$installer->configure_jabbex_user();

$installer->configure_presence_plugin();
$installer->configure_subscription_plugin();

$installer->get_openfire_conf_path();
$installer->create_of_conf_file();


$installer->configure_jabbex();

print "Reloading Openfire...\n This operation may take some seconds. Please be patient!\n";
print `/etc/init.d/openfire reload`;

?>
