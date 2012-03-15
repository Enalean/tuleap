<?php
require_once 'sys/ISBN.php';

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier
 * Name:     FormatISBN
 * Purpose:  Formats an ISBN number
 * -------------------------------------------------------------
 */
function smarty_modifier_formatISBN($isbn) {
    // Normalize ISBN to an array if it is not already.
    $isbns = is_array($isbn) ? $isbn : array($isbn);

    // Loop through the ISBNs, trying to find an ISBN-10 if possible, and returning
    // the first ISBN-13 encountered as a last resort:
    $isbn13 = false;
    foreach($isbns as $isbn) {
        // Strip off any unwanted notes:
        if ($pos = strpos($isbn, ' ')) {
            $isbn = substr($isbn, 0, $pos);
        }

        // If we find an ISBN-10, return it immediately; otherwise, if we find
        // an ISBN-13, save it if it is the first one encountered.
        $isbnObj = new ISBN($isbn);
        if ($isbn10 = $isbnObj->get10()) {
            return $isbn10;
        }
        if (!$isbn13) {
            $isbn13 = $isbnObj->get13();
        }
    }
    return $isbn13;
}
?>