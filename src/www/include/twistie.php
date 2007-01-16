<?php
//Copyright © STMicroelectronics, 2006. All Rights Reserved.
//
//Originally written by Dave Kibble, 2006.
//
//This file is a part of CodeX.
//
//CodeX is free software; you can redistribute it and/or modify
//it under the terms of the GNU General Public License as published by
//the Free Software Foundation; either version 2 of the License, or
//(at your option) any later version.
//
//CodeX is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
//GNU General Public License for more details.
//
//You should have received a copy of the GNU General Public License
//along with CodeX; if not, write to the Free Software
//Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//
//
// twisties allow data to be grouped in a display under a toggle heading - clicking the heading hides or shows the data "within" the twistie. Twisties can be nested.
//
// twistie_Start($HeaderText, $SectionName = "", $isOpen = NULL)
//    $HeaderText    : (required) teh text that is printed alongside the twistie arrow indicator
//    $SectionName    : (optional) Internal unique name for the section - open/closed is remembered as a personal preference if a name is supplied
//    $isOpen        : NULL means use personal preference, otherwise true or false (which does not change the personal preference, only the current display)
//
// {list of data, etc.}
//
// twistie_end()

//=============================================================================
function twistie_Start($HeaderText, $SectionName = "", $isOpen = NULL)
{
    static $TwistieSectionID = 0;
    static $sectionNameCheck = array();

    twistie_emitJavascript();    // make sure the javascript is there
    $TwistieSectionID = $TwistieSectionID + 1;
    if (strlen($SectionName) <= 0) {
        // do not use stored preference for auto-named sections - too many will clash
        $SectionName = $TwistieSectionID;
        if (is_null($isOpen)) {
            $isOpen = FALSE;
        }
    } else {
        // $SectionName is used to remember preference
        if (is_null($isOpen)) {
            $isOpen = (user_get_preference("twistie_".$SectionName) == "open");
        }
    }
    if (isset($sectionNameCheck[$SectionName])) {
        trigger_error("Duplicated section name in page: $SectionName");
    }
    $sectionNameCheck[$SectionName] = 1;    // remember we used this section name
    $SectionName = "twistie_".$SectionName;    //prefix to avoid user_preference clash with other functions
    print "\n<!-- twistie start --><br><span style='white-space: nowrap;'><a onclick=\"TwistieSection('".$SectionName."')\"><img src='".util_get_image_theme("pointer_down.png")."' border='0' alt='toggle' id='timg_".$SectionName."'><span style='text-decoration: none; font-weight: bold;'>".$HeaderText."</span></a></span>";
    print "\n<div id='".$SectionName."' style='position:relative; margin-left: 16px;'>";
    print "<script type='text/javascript' language='javascript'>";
    If (! $isOpen) {
        print "HideSection('".$SectionName."');";
    }
    print "TwistieImg('".$SectionName."');</script>\n<!-- twistie contents start -->\n";
    return TRUE;
}

//=============================================================================
function twistie_end()
{
    print "</div><!-- twistie & contents ends -->";
    return TRUE;
}

//=============================================================================
function twistie_emitJavascript()
{
    static $twistie_emitJavascriptDone = False;
    if (! $twistie_emitJavascriptDone) {
        $twistie_emitJavascriptDone = True;
?>
<script type='text/javascript' src='/scripts/prototype/prototype.js'></script>
<script type='text/javascript' language='javascript'>
function getObjById(name)
{
    if (document.getElementById) {
        return document.getElementById(name);
    }
    else if (document.all) {
        return document.all[name];
    }
    else if (document.layers) {
        return document.layers[name];
    }
    else {
        return false;
    }
}

function getObjStyle(name)
{
    if (document.getElementById) {
        return document.getElementById(name).style;
    }
    else if (document.all) {
        return document.all[name].style;
    }
    else if (document.layers) {
        return document.layers[name];
    }
    else {
        return false;
    }
}

function HideSection(sectionName)
{
    if (getObjStyle(sectionName)) {
        getObjStyle(sectionName).visibility = "hidden";
        getObjStyle(sectionName).display = "none";
    }
}

function ShowSection(sectionName)
{
    if (getObjStyle(sectionName)) {
        getObjStyle(sectionName).visibility = "inherit";
        getObjStyle(sectionName).display = "block";
    }
}

function ShowHideSection(sectionName)
{
    if (getObjStyle(sectionName)) {
        if (getObjStyle(sectionName).visibility == "hidden") {
            ShowSection(sectionName);
        }
        else {
            HideSection(sectionName);
        }
    }
}

function TwistieSection(sectionName)
{
    var ajVal;
    if (getObjStyle(sectionName)) {
        if (getObjStyle(sectionName).visibility == "hidden") {
            ShowSection(sectionName);
            ajVal = "open";
        }
        else {
            HideSection(sectionName);
            ajVal = "closed";
        }
        TwistieImg(sectionName);
        new Ajax.Request('/include/twistie_ajax.php?action=uup&item='+sectionName+'&val='+ajVal, {method: 'get', asynchronous: true});
    }
}

var TwistieImgOpen = '<?php echo util_get_image_theme("pointer_down.png"); ?>';
var TwistieImgClosed = '<?php echo util_get_image_theme("pointer_right.png"); ?>';

function TwistieImg(sectionName)
{
    var img;

    if (getObjStyle(sectionName)) {
        if (getObjStyle(sectionName).visibility == "hidden") {
            img = TwistieImgClosed;
        }
        else {
            img = TwistieImgOpen;
        }
        getObjById('timg_'+sectionName).src = img;
    }
}
</script>
<?php
    }    // end of "if ($twistie_emitJavascriptDone)"
}
?>