<?php
/**
 * 
 * Originally written by Nicolas TERRAY, 2008.
 * 
 * This file is a part of CodeX.
 * 
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * 
 */

/**
 * Clean-up string for http header output.
 *
 * This class aims to purify the header to prevent header injections
 */
class CodeX_HTTPPurifier {
    /**
     * Singleton access.
     *
     * @access: static
     */
    function &instance() {
        static $__codex_httppurifier_instance;
        if(!$__codex_httppurifier_instance) {
            $__codex_httppurifier_instance = new CodeX_HTTPPurifier();
        }
        return $__codex_httppurifier_instance;
    }
    
    function purify($s) {
        $clean = preg_replace('/(\n|\r|\0).*/', '', $s);
        return $clean;
    }
}

?>
