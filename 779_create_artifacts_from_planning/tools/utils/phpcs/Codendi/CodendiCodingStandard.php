<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

if (class_exists('PHP_CodeSniffer_Standards_CodingStandard', true) === false) {
    throw new PHP_CodeSniffer_Exception('Class PHP_CodeSniffer_Standards_CodingStandard not found');
}

/**
 * Codendi Coding Standard.
 *
 * @package   PHP_CodeSniffer
 */
class PHP_CodeSniffer_Standards_Codendi_CodendiCodingStandard extends PHP_CodeSniffer_Standards_CodingStandard
{


    /**
     * Return a list of external sniffs to include with this standard.
     *
     * The PHP_CodeSniffer standard combines the PEAR and Squiz standards
     * but removes some sniffs from the Squiz standard that clash with
     * those in the PEAR standard.
     *
     * @return array
     */
    public function getIncludedSniffs()
    {
        return array(
                'PEAR',
                //'Squiz',
               );

    }//end getIncludedSniffs()

    /**
     * Return a list of external sniffs to exclude from this standard.
     *
     * @return array
     */
    public function getExcludedSniffs()
    {
        return array(
                'PEAR/Sniffs/Commenting/FileCommentSniff.php',
                'PEAR/Sniffs/Commenting/ClassCommentSniff.php',
                'PEAR/Sniffs/Classes/ClassDeclarationSniff.php',
                'PEAR/Sniffs/Functions/FunctionDeclarationSniff.php',
                'PEAR/Sniffs/Files/IncludingFileSniff.php',
        );
    }
    
}//end class
?>
