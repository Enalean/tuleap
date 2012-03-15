<?php
require_once 'sys/SIP2.php';
require_once 'Authentication.php';

class SIPAuthentication implements Authentication {
    
    public function authenticate() {
        global $configArray;
        
        if (isset($_POST['username']) && isset($_POST['password'])) {
            $username = $_POST['username'];
            $password = $_POST['password'];
            if ($username != '' && $password != '') {
            // Attempt SIP2 Authentication

                $mysip = new sip2;
                $mysip->hostname = $configArray['SIP2']['host'];
                $mysip->port = $configArray['SIP2']['port'];

                if ($mysip->connect()) {
                //send selfcheck status message
                    $in = $mysip->msgSCStatus();
                    $msg_result = $mysip->get_message($in);

                    // Make sure the response is 98 as expected
                    if (preg_match("/^98/", $msg_result)) {
                        $result = $mysip->parseACSStatusResponse($msg_result);

                        //  Use result to populate SIP2 setings
                        $mysip->AO = $result['variable']['AO'][0]; /* set AO to value returned */
                        $mysip->AN = $result['variable']['AN'][0]; /* set AN to value returned */

                        $mysip->patron = $username;
                        $mysip->patronpwd = $password;

                        $in = $mysip->msgPatronStatusRequest();
                        $msg_result = $mysip->get_message($in);

                        // Make sure the response is 24 as expected
                        if (preg_match("/^24/", $msg_result)) {
                            $result = $mysip->parsePatronStatusResponse( $msg_result );

                            if (($result['variable']['BL'][0] == 'Y') and ($result['variable']['CQ'][0] == 'Y')) {
                            // Success!!!
                                $user = $this->processSIP2User($result, $username, $password);

                                // Set login cookie for 1 hour
                                $user->password = $password; // Need this for Metalib
                            } else {
                                $user = new PEAR_Error('authentication_error_invalid');
                            }
                        } else {
                            $user = new PEAR_Error('authentication_error_technical');
                        }
                    } else {
                        $user = new PEAR_Error('authentication_error_technical');
                    }
                    $mysip->disconnect();

                } else {
                    $user = new PEAR_Error('authentication_error_technical');
                }
            } else {
                $user = new PEAR_Error('authentication_error_blank');
            }
        } else {
            $user = new PEAR_Error('authentication_error_blank');
        }

        return $user;
    }

    /**
     * Process SIP2 User Account
     *
     * @param   array   $info           An array of user information
     * @param   array   $username       The user's ILS username
     * @param   array   $password       The user's ILS password
     * @access  public
     * @author  Bob Wicksall <bwicksall@pls-net.org>
     */
    private function processSIP2User($info, $username, $password){
        require_once "services/MyResearch/lib/User.php";

        $user = new User();
        $user->username = $info['variable']['AA'][0];
        if ($user->find(true)) {
            $insert = false;
        } else {
            $insert = true;
        }

        // This could potentially be different depending on the ILS.  Name could be Bob Wicksall or Wicksall, Bob.
        // This is currently assuming Wicksall, Bob
        $user->firstname = trim(substr($info['variable']['AE'][0], 1 + strripos($info['variable']['AE'][0], ',')));
        $user->lastname = trim(substr($info['variable']['AE'][0], 0, strripos($info['variable']['AE'][0], ',')));
        // I'm inserting the sip username and password since the ILS is the source.
        // Should revisit this.
        $user->cat_username = $username;
        $user->cat_password = $password;
        $user->email = 'email';
        $user->major = 'null';
        $user->college = 'null';

        if ($insert) {
            $user->created = date('Y-m-d');
            $user->insert();
        } else {
            $user->update();
        }

        return $user;
    }
}
?>
