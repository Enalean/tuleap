<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier
 * Name:     truncate_html
 * Purpose:  Tag-aware variant of standard truncate modifier
 * Note:     Adapted from substrws() function found at:
 *                http://php.net/manual/en/function.substr.php
 * -------------------------------------------------------------
 */
function smarty_modifier_truncate_html($text, $len, $suffix = '') {
    if ((strlen($text) > $len)) {
        $whitespaceposition = strpos($text, " ", $len) - 1;
        
        if ($whitespaceposition > 0) {
            $text = substr($text, 0, ($whitespaceposition + 1));
        }

        // strip trailing partial tags
        $text = preg_replace('/<[^>]*$/Um', '', $text);

        // close unclosed html tags
        if (preg_match_all("|<([a-zA-Z]+)[^>]*>|", $text, $aBuffer)) {
            $openers = array();
            if (!empty($aBuffer[1])) {
                $selfClosing = array('br', 'img');
                foreach ($aBuffer[1] as $current) {
                    if (!in_array(strtolower($current), $selfClosing)) {
                        $openers[] = $current;
                    }
                }
            }
            if (!empty($openers)) {
                preg_match_all("|</([a-zA-Z]+)>|", $text, $aBuffer2);
                if (count($openers) != count($aBuffer2[1])) {
                    foreach($openers as $index => $tag) {                    
                        if (empty($aBuffer2[1][$index]) || 
                            $aBuffer2[1][$index] != $tag) {
                            $text .= '</'.$tag.'>';
                        }
                    }
                }
            }
        }
        $text .= $suffix;
    }
    
    return $text; 
}
?>