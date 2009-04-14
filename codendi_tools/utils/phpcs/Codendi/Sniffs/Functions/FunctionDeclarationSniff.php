<?php
class Codendi_Sniffs_Functions_FunctionDeclarationSniff extends PEAR_Sniffs_Functions_FunctionDeclarationSniff
{


    /**
     * Processes single-line declarations.
     *
     * Just uses the Generic BSD-Allman brace sniff.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     * @param array                $tokens    The stack of tokens that make up
     *                                        the file.
     *
     * @return void
     */
    public function processSingleLineDeclaration(PHP_CodeSniffer_File $phpcsFile, $stackPtr, $tokens)
    {
        if (class_exists('Generic_Sniffs_Functions_OpeningFunctionBraceKernighanRitchieSniff', true) === false) {
            throw new PHP_CodeSniffer_Exception('Class Generic_Sniffs_Functions_OpeningFunctionBraceKernighanRitchieSniff not found');
        }

        $sniff = new Generic_Sniffs_Functions_OpeningFunctionBraceKernighanRitchieSniff();
        $sniff->process($phpcsFile, $stackPtr);

    }//end processSingleLineDeclaration()


}//end class

?>
