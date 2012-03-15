<?php
require_once 'UnknownAuthenticationMethodException.php';

class AuthenticationFactory {
	
	static function initAuthentication($authNHandler){
		switch($authNHandler){
			case "Shibboleth":
			    require_once 'ShibbolethAuthentication.php';
				return new ShibbolethAuthentication();
			case "LDAP":
			    require_once 'LDAPAuthentication.php';
				return new LDAPAuthentication();
            case "DB":
                require_once 'DatabaseAuthentication.php';
                return new DatabaseAuthentication();
            case "SIP2":
                require_once 'SIPAuthentication.php';
                return new SIPAuthentication();
            case "ILS":
                require_once 'ILSAuthentication.php';
                return new ILSAuthentication();
			default:
				throw new UnknownAuthenticationMethodException('Authentication handler ' + $authNHandler + 'does not exist!');	
		}
	}
}
?>