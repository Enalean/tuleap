<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier
 * Name:     getSummonFormatClass
 * Purpose:  Get a class to display an icon for a Summon format
 * -------------------------------------------------------------
 */
function smarty_modifier_getSummonFormatClass($format) {
    switch($format) {
        case 'Audio Recording':
            return 'audio';
        case 'Book':
        case 'Book Chapter':
            return 'book';
        case 'Computer File':
        case 'Web Resource':
            return 'electronic';
        case 'Dissertation':
        case 'Manuscript':
        case 'Paper':
        case 'Patent':
            return 'manuscript';
        case 'eBook':
            return 'ebook';
        case 'Kit':
            return 'kit';
        case 'Image':
        case 'Photograph':
            return 'photo';
        case 'Music Score':
            return 'musicalscore';
        case 'Newspaper Article':
            return 'newspaper';
        case 'Video Recording':
            return 'video';
        default:
            return 'journal';
    }
}
?>