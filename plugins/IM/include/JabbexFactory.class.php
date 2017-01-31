<?php
/**
 * @copyright Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 *
 * JabbexFactory
 */

class JabbexFactory {
    
    private static $_jabbex_instance;
    
    public static function getJabbexInstance() {
        if ( ! self::$_jabbex_instance) {
            try {
                require_once("jabbex_api/Jabbex.php");
                self::$_jabbex_instance = new Jabbex(UserManager::instance()->getCurrentUser()->getSessionHash());
            } catch (Exception $e) {
                $GLOBALS['Response']->addFeedback('error', 'Jabbex require_once error:'.$e->getMessage());
                return null;
            }
        }
        return self::$_jabbex_instance;
    }
    
}

?>
