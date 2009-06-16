<?php

if (class_exists('PHP_CodeSniffer_CommentParser_ClassCommentParser', true) === false) {
    $error = 'Class PHP_CodeSniffer_CommentParser_ClassCommentParser not found';
    throw new PHP_CodeSniffer_Exception($error);
}

require_once dirname(__FILE__).'/FileCommentSniff.php';

if (class_exists('Codendi_Sniffs_Commenting_FileCommentSniff', true) === false) {
    $error = 'Class Codendi_Sniffs_Commenting_FileCommentSniff not found';
    throw new PHP_CodeSniffer_Exception($error);
}
class Codendi_Sniffs_Commenting_ClassCommentSniff extends Codendi_Sniffs_Commenting_FileCommentSniff
{
}//end class

?>
