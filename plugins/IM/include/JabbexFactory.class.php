<?php
/**
 * @copyright Copyright (c) Xerox Corporation, CodeX / Codendi Team, 2001-2008. All rights reserved
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 *
 * JabbexFactory
 */

class JabbexFactory {
    
    var $_jabbex_instance;
    
    function JabbexFactory() {
    }
    
    function getJabbexInstance() {
        static $_jabbex_instance;
        if (!$_jabbex_instance) {
            try {
                require_once("jabbex_api/Jabbex.php");
                $_jabbex_instance = new Jabbex(session_hash());
            } catch (Exception $e) {
                $GLOBALS['Response']->addFeedback('error', 'Jabbex require_once error:'.$e->getMessage().' ### ');
                return null;
            }
        }
        return $_jabbex_instance;
    }
    
}
