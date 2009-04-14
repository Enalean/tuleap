<?php
if (class_exists('Generic_Sniffs_ControlStructures_InlineControlStructureSniff', true) === false) {
    $error = 'Class Generic_Sniffs_ControlStructures_InlineControlStructureSniff not found';
    throw new PHP_CodeSniffer_Exception($error);
}
class Codendi_Sniffs_ControlStructures_InlineControlStructureSniff extends Generic_Sniffs_ControlStructures_InlineControlStructureSniff
{

    /**
     * If true, an error will be thrown; otherwise a warning.
     *
     * @var bool
     */
    protected $error = true;

}//end class

?>
