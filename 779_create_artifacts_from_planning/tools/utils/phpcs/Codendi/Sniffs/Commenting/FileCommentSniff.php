<?php
/**
 * Parses and verifies the doc comments for files.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @version   CVS: $Id: FileCommentSniff.php,v 1.32 2009/02/10 06:01:46 squiz Exp $
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

if (class_exists('PEAR_Sniffs_Commenting_FileCommentSniff', true) === false) {
    throw new PHP_CodeSniffer_Exception('Class PEAR_Sniffs_Commenting_FileCommentSniff not found');
}

class Codendi_Sniffs_Commenting_FileCommentSniff extends PEAR_Sniffs_Commenting_FileCommentSniff
{

    public function __construct() {
        foreach(array('category', 'package', 'author', 'license', 'link') as $tag) {
            $this->tags[$tag]['required'] = false;
        }
    }
    
    /**
     * No need to require the php version
     */
    protected function processPHPVersion($commentStart, $commentEnd, $commentText) {
    }


}//end class

?>
