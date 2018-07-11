#!/usr/bin/php -q
<?php

class JabbeXInstaller {

    /**
     * @var PDO
     */
    private $pdo;

	private $argc; // Command line ...
	private $argv; // ... arguments.

	private $mode = "help"; // help, automatic, or interactive
	private $arguments;		// Arguments parsed and organized.


	public function __construct($argc = null, $argv = null) {
		$this->argc = $argc;
		$this->argv = $argv;
	}
	
	function _db_connect($username, $pwd, $url, $port = null, $db)
    {
		if(! $port) {
		    $port = "3306";
        }

        $this->pdo = new PDO("mysql:host=$url;port=$port;dbname=$db;charset=utf8mb4", $username, $pwd);
        $this->pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
	}

	function _db_close()
    {
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
						"* Install Openfire Jabber server and configure it to integrate with Codendi.\n".
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
						"* 		-ucd	Username Openfire will use to access Codendi's DB.\n".
						"* 		-pcd	Password for this user.\n".
						"* 		-odb	Openfire's DB complete JDBC URL (eg: jdbc:mysql://<db_server_url>:<db_port>/<db_name>).\n".
						"* 		-cdb	Codendi's DB complete JDBC URL (eg: jdbc:mysql://<db_server_url>:<db_port>/<db_name>).\n".
						"* 		-ouri	Openfire server URI (used by clients to connect to the server).\n".
						"* 		-gjx	Codendi group of the IM admins.\n".
						"* 		-ujx	Default JabbeX user's username (must be a member of the gjx).\n".
						"* 		-pjx	Password for this user.\n".
						"* 		-pmuc	Password for managing the MUC services.\n".
						"*\n".
						"*	Optional arguments for automatic mode:\n".
						"*		-dir	Openfire's installation directory (default: /opt/openfire).\n".
						"*		-etcdir	Openfire's installation directory (default: /etc/codendi/plugins/IM/etc/).\n".
						"*		-ap		Unsecure web admin port (default: 9090).\n".
						"*		-aps	Secure web admin port (default: 9091).\n".
						"*		-odd	Openfire's DB driver (default: com.mysql.jdbc.Driver).\n".
						"*		-cdd	Codendi's DB driver (default: com.mysql.jdbc.Driver).\n".
						"*		-fdn 	Forge Database Name (default: codendi).\n".
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

        private function getMysqlConnectionString() {
            return "--host ".$this->arguments["OF_DB_HOST"]." -P ".$this->arguments["OF_DB_PORT"];
        }

        private function getMyslStringHostAndPort() {
            return $this->arguments["OF_DB_HOST"].':'.$this->arguments["OF_DB_PORT"];
        }

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

		print("\nCodendi's DB JDBC Driver [com.mysql.jdbc.Driver]:\n");
		$this->arguments["CD_DB_DRIVER"] = $this->read();
		if(empty($this->arguments["CD_DB_DRIVER"])) $this->arguments["CD_DB_DRIVER"] = "com.mysql.jdbc.Driver";

		print("\nCodendi's DB complete URL (eg: jdbc:mysql://<db_server_url>:<db_port>/<db_name>):\n");
		$this->arguments["CD_DB_URI"] = $this->read();

		print("\nUsername for accessing the Codendi's DB:\n");
		$this->arguments["USER_CD_DB"] = $this->read();

		print("\nThe password for this username (for the Codendi's DB):\n");
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

		print("\nThe Codendi group of IM admins:\n");
		$this->arguments["GROUP_JABBEX"] = $this->read();
		
		print("\nThe password for MUC management:\n");
		$this->arguments["PWD_MUC"] = $this->read();

		print("\nForge Database Name [codendi]:\n");
		$this->arguments["FORGE_DB_NAME"] = $this->read();
		if(empty($this->arguments["FORGE_DB_NAME"])) $this->arguments["FORGE_DB_NAME"] = "codendi";

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
			,"-op","-hbot","-hsv","-shrg","-fdn");  // Optional arguments.

			$default_val = array(
						"-dir" => "/opt/openfire",
						"-etcdir" => "/etc/codendi/plugins/IM/etc/",
						"-ap" => "9090",
						"-aps" => "9091",
						"-odd" => "com.mysql.jdbc.Driver",
						"-cdd" => "com.mysql.jdbc.Driver"
						
						,"-op" => "5222",
						"-hbot" => "bot",
						"-hsv" => "helga",
						"-shrg" => "1",

						"-fdn" => "codendi",
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
						"-shrg" => "SHRG_ACTIVE",
						"-fdn" => "FORGE_DB_NAME"
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
				"* 	- Username Openfire will use to access Codendi's DB.\n".
				"* 	- Password for this user.\n".
				"* 	- Openfire's DB complete URL (eg: jdbc:mysql://<db_server_url>:<db_port>/<db_name>).\n".
				"* 	- Codendi's DB complete URL (eg: jdbc:mysql://<db_server_url>:<db_port>/<db_name>).\n".
				"* 	- Openfire server URI (used by clients to connect to the server).\n".
				"* 	- Codendi group of the IM admins.\n".
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
		
		$query = "SELECT * FROM `ofProperty` WHERE `name` LIKE '$property'";
        $statement = $this->pdo->query($query);

        return $statement->fetch();
		
	}

	/*
	 * Adds/updates a property's value to the Openfire's DB.
	 */
	function _add_property($property,$value){
		$this->_db_connect($this->arguments["USER_OF_DB"],$this->arguments["PWD_OF_DB"],$this->arguments["OF_DB_HOST"],$this->arguments["OF_DB_PORT"],$this->arguments["OF_DB_NAME"]);
		
		if( ! $this->_property_exists($property) ){
			$query = "INSERT INTO `openfire`.`ofProperty` (`name`, `propValue`) VALUES ('$property', '$value')";
            $this->pdo->exec($query);
		}
		else{
			$query = "UPDATE `openfire`.`ofProperty` SET `propValue` = '$value' WHERE `ofProperty`.`name` = '$property'";
            $this->pdo->exec($query);
		}
	}

    /*
	 * Checks whether a given muc property is set on the Openfire's DB.
	 * Returns the muc property value if so and FALSE if it doesn't exist.
	 */
	function _muc_property_exists($property){
		$this->_db_connect($this->arguments["USER_OF_DB"],$this->arguments["PWD_OF_DB"],$this->arguments["OF_DB_HOST"],$this->arguments["OF_DB_PORT"],$this->arguments["OF_DB_NAME"]);
		
		$query = "SELECT * FROM `ofMucServiceProp` WHERE `serviceID` = 1 AND `name` LIKE '$property'";
        $statement = $this->pdo->query($query);
        return $statement->fetch();
		
	}

	/*
	 * Adds/updates a muc property's value to the Openfire's DB.
	 */
	function _add_muc_property($property,$value){
		$this->_db_connect($this->arguments["USER_OF_DB"],$this->arguments["PWD_OF_DB"],$this->arguments["OF_DB_HOST"],$this->arguments["OF_DB_PORT"],$this->arguments["OF_DB_NAME"]);
		
		if( ! $this->_muc_property_exists($property) ){
			$query = "INSERT INTO `openfire`.`ofMucServiceProp` (`serviceID`, `name`, `propValue`) VALUES (1, '$property', '$value')";
            $this->pdo->exec($query);
		}
		else{
			$query = "UPDATE `openfire`.`ofMucServiceProp` SET `propValue` = '$value' WHERE `serviceID` = 1 AND `ofMucServiceProp`.`name` = '$property'";
            $this->pdo->exec($query);
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
		$this->_add_muc_property("xmpp.muc.create.jid",$this->arguments["USER_JABBEX"]."@".$this->arguments["OF_JABBER_URI"]);
		
		// Add xmpp.muc.sysadmin.jid = <JABBEX_USER_JID>
		$this->_add_muc_property("xmpp.muc.sysadmin.jid",$this->arguments["USER_JABBEX"]."@".$this->arguments["OF_JABBER_URI"]);
		
		// Only admins can create MUC: xmpp.muc.create.anyone = true
		$this->_add_muc_property("xmpp.muc.create.anyone","true");      
        
	}
	
	function changeJabbexUserPasswordInCodendiDatabase() {

        print("Updating '".$this->arguments["USER_JABBEX"]."' user password in codendi database...\n");

        $root = "root";
        $pwd = $this->arguments["ROOT_OF_DB"];
        $db_name = $this->arguments["FORGE_DB_NAME"];
        $db_host_and_port = $this->getMysqlConnectionString();
        $jabbex_user_password = $this->arguments["PWD_JABBEX"];
        $jabbex_user_name = $this->arguments["USER_JABBEX"];

        $result = `mysql --user=$root --password=$pwd $db_host_and_port --database=$db_name -e "UPDATE user SET user_pw = MD5('$jabbex_user_password') WHERE user_name = '$jabbex_user_name'";  2>&1`;

        // Check if there was any error
        if ( !(strpos($result,"error") === false) ) {
            exit("ERROR: Unable to change '".$jabbex_user_name."' user password in codendi database.\n $result");
        } else {
            print("'".$jabbex_user_name."' user password was properly updated!\n");
            return true;
        }
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
	 * Creates the JabbeX configuration file /etc/codendi/plugins/IM/etc/jabbex_conf.xml based on
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

				// Granting permission -rw-r--r-- 1 codendiadm codendiadm
				`chmod 644 $file`;
				`chown codendiadm:codendiadm $file`;
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
	 * Creates the database configuration file /etc/codendi/plugins/IM/etc/database_im.inc based on
	 * the template file resources/database_im.tpl.inc.
	 */
	function configure_database_im() {
		
		print("Configuring IM database configuration file...\n");
		
		$curent_dir = dirname(__FILE__);
		$template_file = $curent_dir."/resources/database_im.tpl.inc";

		$conf_str = file_get_contents($template_file);

		$values["__OPENFIRE_DB_HOST__"] = $this->getMyslStringHostAndPort();
		$values["__OPENFIRE_DB_USER__"] = $this->arguments["USER_OF_DB"];
		$values["__OPENFIRE_DB_PWD__"] = $this->arguments["PWD_OF_DB"];
		$values["__OPENFIRE_DB_NAME__"] = $this->arguments["OF_DB_NAME"];
		
		foreach($values as $key => $value) {
			$conf_str = str_replace("{".$key."}",$value,$conf_str);
		}

		$target_dir =  $this->arguments["ETC_DIR"];
		$file = $target_dir."/database_im.inc";

		if ( $fp =  fopen($file,'w') ) {
			if ( fwrite($fp, $conf_str) ) {

				fclose($fp);

				// Granting permission -rw-r--r-- 1 codendiadm codendiadm
				`chmod 644 $file`;
				`chown codendiadm:codendiadm $file`;
				//---

				print("\n");
				print("**************************************************\n");
				print("* The database_im.inc configuration file was     *\n");
				print("* successfully created inside the IM plugin etc/ *\n");
				print("* directory.                                     *\n");
				print("**************************************************\n");

			} else {
				fclose($fp);
				exit("ERROR: Unable to create the IM database configuration file.");
			}
		} else {
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
	function create_openfire_db() {

		print("Creating Openfire DB tables...\n");
		
		$root = "root";
		$pwd = $this->arguments["ROOT_OF_DB"];

		$db_name = $this->arguments["OF_DB_NAME"];
		$openfire_dir = $this->arguments["OPENFIRE_DIR"];  // Used when mode = automatic
		$openfire_db_connection_string = $this->getMysqlConnectionString();

		// Check if the DB already exists.
		$curr_db = `mysql --user=$root --password=$pwd $openfire_db_connection_string -e "show databases" | grep "^$db_name$"`;

		if( !(strpos($curr_db,"error") === false) ){
			exit("ERROR: Unable to create Openfire's DB.\n $result");
		}


		if( empty($curr_db) ){
			// Create db
			$result = `mysqladmin --user=$root --password=$pwd $openfire_db_connection_string create $db_name 2>&1`;
			// Check if there was any error
			if( !(strpos($result,"error") === false) ){
				exit("ERROR: Unable to create Openfire's DB.\n $result");
			}

			// Create tables
			$sql_script = `find $openfire_dir | grep '/resources/database/openfire_mysql.sql$'`;

			if($sql_script){
				$sql_script = trim($sql_script);
				$result = `cat $sql_script | mysql --user=$root --password=$pwd $openfire_db_connection_string $db_name 2>&1`;
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
	
	function configure_openfire_for_webmuc() {
		print("Configuring Openfire properties for webmuc...\n");
		
		// Specific configuration for webmuc
		$this->_add_property("httpbind.enabled", "true"); // enable HTTP Bind
		//$this->_add_property("httpbind.port.plain", "7070"); // set HTTP Bind port to 7070 (default)
		//$this->_add_property("httpbind.port.secure", "7443"); // set HTTP Bind secure port to 7443 (default)
		$this->_add_property("xmpp.httpbind.client.requests.polling", "0");
		$this->_add_property("xmpp.httpbind.client.requests.wait", "10");
		$this->_add_property("xmpp.httpbind.scriptSyntax.enabled", "true");
		
		$this->_add_muc_property("xmpp.muc.history.type", "number");	// show 30 last messages when log on chat room
		$this->_add_muc_property("xmpp.muc.history.maxNumber", "30");
		
		// monitoring plugin
		$this->_add_property("conversation.idleTime", "11");
        $this->_add_property("conversation.maxTime", "240");
        $this->_add_property("conversation.messageArchiving", "false");
        $this->_add_property("conversation.metadataArchiving", "true");
        $this->_add_property("conversation.roomArchiving", "true");
	}
    
    /**
     * Configure Openfire for Codendi integration.
     * This was done before in openfire.xml configuration file.
     * This is now managed in database.
     * 
     * This function uses the global parameters so you want to run command_line_args and get_openfire_conf_path 
	 * prior to calling it.
     */
    function configure_openfire_for_codendi() {
        $parameters = $this->arguments;
        
        $this->_add_property("jdbcProvider.driver", $parameters["CD_DB_DRIVER"]);
        $this->_add_property("jdbcProvider.connectionString", $parameters["CD_DB_URI"]."?user=".$parameters["USER_CD_DB"]."&password=".$parameters["PWD_CD_DB"]);
        
        $this->_add_property("provider.auth.className", "org.jivesoftware.openfire.auth.CodendiJDBCAuthProvider");
        $this->_add_property("provider.group.className", "org.jivesoftware.openfire.group.JDBCGroupProvider");
        $this->_add_property("provider.user.className", "org.jivesoftware.openfire.user.JDBCUserProvider");
        
        $this->_add_property("jdbcAuthProvider.passwordSQL", "SELECT LOWER(user_pw) FROM user WHERE user_name=?");
        $this->_add_property("jdbcAuthProvider.passwordType", "md5");

        $this->_add_property("jdbcUserProvider.loadUserSQL", "SELECT realname , email FROM user WHERE user_name=?");
        $this->_add_property("jdbcUserProvider.userCountSQL", "SELECT COUNT(*) FROM user WHERE status = \'A\'");
        $this->_add_property("jdbcUserProvider.allUsersSQL", "SELECT LOWER(user_name) FROM user WHERE status = \'A\'");
        $this->_add_property("jdbcUserProvider.searchSQL", "SELECT LOWER(user_name) FROM user WHERE");
        $this->_add_property("jdbcUserProvider.usernameField", "user_name");
        $this->_add_property("jdbcUserProvider.nameField", "realname");
        $this->_add_property("jdbcUserProvider.emailField", "email");
        
        $this->_add_property("jdbcGroupProvider.groupCountSQL", "SELECT count(*) FROM groups WHERE status = \'A\'");
        $this->_add_property("jdbcGroupProvider.allGroupsSQL", "SELECT LOWER(unix_group_name) FROM groups WHERE status = \'A\'");
        $this->_add_property("jdbcGroupProvider.userGroupsSQL", "SELECT LOWER(groups.unix_group_name) FROM groups, user, user_group WHERE groups.group_id=user_group.group_id AND user_group.user_id=user.user_id AND groups.status = \'A\' AND user.user_name=?");
        $this->_add_property("jdbcGroupProvider.descriptionSQL", "SELECT short_description FROM groups WHERE unix_group_name=?");
        $this->_add_property("jdbcGroupProvider.loadMembersSQL", "SELECT LOWER(user.user_name) FROM user, groups, user_group WHERE groups.group_id=user_group.group_id AND user_group.user_id=user.user_id AND groups.unix_group_name=? AND user_group.admin_flags NOT LIKE \'A\' AND user.status = \'A\'");
        $this->_add_property("jdbcGroupProvider.loadAdminsSQL", "SELECT LOWER(user.user_name) FROM user, groups, user_group WHERE groups.group_id=user_group.group_id AND user_group.user_id=user.user_id AND groups.unix_group_name=? AND user_group.admin_flags=\'A\'");
        
    }
	
	function copyAuthenticationJarFile() {
		$curent_dir = dirname(__FILE__);
		$jar_source = $curent_dir."/resources/codendi_auth.jar";
		
		$jar_dest = $this->arguments["OPENFIRE_DIR"] . "/lib/codendi_auth.jar";
		
		if (copy($jar_source, $jar_dest)) { 
				// Granting permission -rw-r--r-- 1 codendiadm codendiadm
				`chmod 644 $jar_dest`;
				`chown codendiadm:codendiadm $jar_dest`;
				//---

				print("\n");
				print("***************************************\n");
				print("* The jar file codendi_auth.jar was   *\n");
				print("* successfully copied inside the      *\n");
				print("* /openfire/lib/ directory.           *\n");
				print("***************************************\n");

		} else {
			exit("ERROR: Unable to copy file $jar_source \n");
		}
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

// stop openfire if openfire is started
$installer->openfire_status();

//$installer->install_helga();
//$installer->install_presence_plugin();
//$installer->install_subscription_plugin();

// create openfire database
$installer->create_openfire_db();

// configure openfire properties
$installer->configure_openfire();
// configure specific openfire properties for webmuc
$installer->configure_openfire_for_webmuc();
// configure openfire properties
$installer->configure_jabbex_user();
// update Jabbex user password in Codendi database
$installer->changeJabbexUserPasswordInCodendiDatabase();

// configure specific openfire properties for plugins
$installer->configure_presence_plugin();
$installer->configure_subscription_plugin();

// find and backup openfire configuration file (openfire.xml)
$installer->get_openfire_conf_path();

$installer->create_of_conf_file();

// configure openfire for codendi integration
$installer->configure_openfire_for_codendi();


$installer->configure_jabbex();
$installer->configure_database_im();

$installer->copyAuthenticationJarFile();

print "Reloading Openfire...\n This operation may take some seconds. Please be patient!\n";
print `/etc/init.d/openfire reload`;

?>
