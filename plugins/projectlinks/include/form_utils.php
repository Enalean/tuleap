<?php
//Copyright � STMicroelectronics, 2006. All Rights Reserved.
//
//Originally written by Dave Kibble, 2006.
//
//This file is a part of Codendi.
//
//Codendi is free software; you can redistribute it and/or modify
//it under the terms of the GNU General Public License as published by
//the Free Software Foundation; either version 2 of the License, or
//(at your option) any later version.
//
//Codendi is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
//GNU General Public License for more details.
//
//You should have received a copy of the GNU General Public License
//along with Codendi; if not, write to the Free Software
//Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//
// HTML form library translated and simplified from PRATS
//
// for a usage guide, look at the examples in the following form library check
/********************************************************
The form is bounded by form_Start() and form_End. An HTML table is built to contain the elements within the form. refer to the example below.

Most programmer errors are caught with error messages.

form_Start($serviceURI = "")
    $serviceURI - (optional) defaults to the current page

form_HiddenParams($params) - use passed arry to create for hidden params - typically used to control which database items to update

form_End($SubmitLegend = NULL, $HaveResetButton = FORM_NO_RESET_BUTTON)
    $SubmitLegend:
        a value used as the legend on the submit button - NULL becomes "update"
        use FORM_NO_SUBMIT_BUTTON to have no submit button at form end

    $HaveResetButton:
        FORM_HAVE_RESET_BUTTON
        FORM_NO_RESET_BUTTON (default)


Additional submit buttong can be created:
form_genSubmit($SubmitLegend)
form_genSubmitImg($ImageFileHTMLPath)


the form is built using the following form items. In all cases the $ParamName, $Caption and $DefaultValue parameters
are the form item name and printed caption and the current value of the selection or text control.

form_genTextBox($ParamName, $Caption, $DefaultValue = "", $Width = FORM_TEXT_AREA_WIDTH, $MaxInputLength = 0)
    if the default value is prefixed with "COF:" (clear on focus) the box is displayed with the DefaultValue as
    text, and the text vanishes when the box is clicked

form_genShowBox($Caption, $Value, $UpdateURL = NULL)
    not a form control, just displays the information with an optional button to pop-up another window to update the information

form_genFileBox($ParamName, $Caption, $DefaultValue = "", $AcceptableMimeTypes = NULL)

form_genCheckbox($ParamName, $Caption, $ValueIfChecked, $DefaultValue = "", $SubmitOnChange = NO_SUBMIT_ON_CHANGE)

form_SelectAllCheckbox($GroupName = "", $isChecked=False) - controls javascript to check all controls in the form, or those on the named group

form_genJSButton($Caption, $JavaScript, $ImageFileHTMLPath = NULL) - creates a button that runs some javaScript
        - can be called outside a form

=====================
Each of the form items can include valildation, run as javascript before form submit:

form_Validation($ParamName, $Tests, $OptionalParam = NULL)
    $Tests - simple value or array of value=>parameter - parameter should be null if not required
        FORM_VAL_IS_EMAIL - is a valid email address
        FORM_VAL_IS_NOT_ZERO_LENGTH - is present (not empty)
        FORM_VAL_IS_NUMBER - is a number
        FORM_VAL_IS_EQ - string identical with Parameter
        FORM_VAL_IS_LT - numerically less than Parameter
        FORM_VAL_IS_GT - numerically greater than Parameter
        FORM_VAL_IS_CHECKED - checkbox must be checked (ticked)



form_FocusFirstTextBox() - set focus to first text item in the form
form_SetFocusItem($ParamName) - set the item for focus - defaults to the first in the form if this is not called



Formatting:

form_NewRow() - stops putting caption-item on a row and starts a new row

form_Heading($Heading)

form_Text($Text, $SectionSpan = 1) - displays the text spanning the two columns of caption and control

form_SkipColumn() - skips two columns in the form table


form_SectionStart($Text = NULL) - closes the existing table and starts another with the optional heading
form_SectionEnd()

form_GroupStart($Caption="", $GroupHeading="", $ColSpan=1) - typically used to group radio buttons
form_GroupEnd()

form_JS_ElementRef($Elementname) - returns the javascript to reference the named element in the current form

nz($item, $default) - if $item is not null or an empty string, return $item, else $default

mkAH($caption, $URL, $title = "", $params = NULL) - make a href anchor
    $caption - visible text or graphic
    $URL - link
    $title - title
    $params - array of additional parameters, e.g. onclick=>"javascript"

graphicForBoolean($bool) - returns tick or cross

update_database($tableName, $items, $selectCriteria = "") - easy way to handle insert/update
    $tableName - table name to update
    $items - array of name=>value
    $selectCriteria - optional - SQL WHERE string to identify the items to update, omit for an INSERT
**/
/********************************************************
echo "<hr><h2>form library check</h2>\n\n\n";
form_Start();
form_heading("here's a heading");
form_GenTextBox("name", "email", "string", 20, 10);
form_Validation("name", FORM_VAL_IS_NOT_ZERO_LENGTH);
form_Validation("name", array(FORM_VAL_IS_NOT_ZERO_LENGTH=>NULL, FORM_VAL_IS_EMAIL=>NULL));
form_newRow();
form_SectionStart("Section Start");
form_text("some text for the form");
form_GenTextBox("number", "number", "number", 20, 10);
form_Validation("number", array(FORM_VAL_IS_NUMBER => NULL, FORM_VAL_IS_EQ => 5, FORM_VAL_IS_LT => 5, FORM_VAL_IS_GT => 5));

form_SectionStart();

form_genCheckbox("check", "caption", "value1", "value0");
form_Validation("check", FORM_VAL_IS_CHECKED);
form_SkipColumn();
form_genCheckbox("check1", "check1", "value2", "value1");
form_SectionEnd();

form_GroupStart("group Caption", "GROUP HEADING", 5);
form_genRadioButton("button", array("option 1" =>1, "option 2" =>2), "");
form_FocusFirstTextBox();
form_genSubmitImg(util_get_image_theme("ic/check.png"));
form_SelectAllCheckbox();
form_genJSButton("do JS", "alert('cooee')");
form_genShowBox("Show Caption", "Value", "url");
form_genFileBox("file", "fil cap");
form_SetFocusItem("number");
form_End();
*************************/

//=============================================================================
// Options for form_End() parameters:
// $SubmitLegend
define("FORM_NO_SUBMIT_BUTTON", "");
define("DEFAULT_SUBMIT_BUTTON", null);
// $HaveResetButton:
define("FORM_HAVE_RESET_BUTTON", 1);
define("FORM_NO_RESET_BUTTON", 0);

// FormValidation
define("FORM_VAL_IS_EMAIL", 10);           // is a valid email address
define("FORM_VAL_IS_NOT_ZERO_LENGTH", 11); // is present (not empty)
define("FORM_VAL_IS_NUMBER", 12);          // is a number
define("FORM_VAL_IS_EQ", 13);              // string identical with OptionalParameter
define("FORM_VAL_IS_LT", 14);              // numerically less than OptionalParameter
define("FORM_VAL_IS_GT", 15);              // numerically greater than OptionalParameter
define("FORM_VAL_IS_CHECKED", 16);         // checkbox must be checked (ticked)


//=============================================================================
// internal stuff

define("FORM_CAPTION_STYLE", " style='font-weight: bold;'");
define("FORM_BUTTON_STYLE", " class='button'");
define("SUBMIT_BUTTON_NAME", "SubmitButton");    // don't call it "submit" - it over-rides the form.submit method!

//-----------------------------------------------------------------------------------
define("FORM_TEXT_AREA_WIDTH", 70);
define("FORM_TEXT_AREA_DEPTH", 7);
define("FORM_MAX_TEXT_AREA_ROWS", 60);        // maximum number of rows in a text area, unless user specifies more
define("FORM_STD_COL_WIDTH", 25);   // used to calculate col span to help keep form neat!
define("FORM_URL_WIDTH", 120);        // standard maxlen for a URL

//globals
$gFormID               = "";             // counter to identify each form uniquely
$gFormName             = "";           // to identify each form uniquely by name
$gInForm               = false;          // to control form errors
$gFirstFormTextBox     = "";   // used to place the focus in the first text item box
$gFormSectionLevel     = 0;   // used to ensure form sections are controlled properly
$gFormGroupLevel       = 0;   // used to ensure form groups are controlled properly
$gFormHiddenParams     = "";   // to accumulate the values of hidden parameters
$gFormCaptions         = "";
$gValidationCollection = []; // validation control

//=============================================================================
function form_Start($serviceURI = "")
{
    global $gInForm, $gFormID, $gFormName, $gFormUsedDateBox, $gFirstFormTextBox,
        $gFormHiddenParams, $gFormSectionLevel, $gFormGroupLevel,
        $gValidationCollection, $gPageDateCodeWritten, $gFormCaptions;

    $gFormID              += 1;
    $gFormName             = "Form" . $gFormID;
    $gFormUsedDateBox      = false;
    $gFirstFormTextBox     = "";
    $gFormHiddenParams     = [];
    $gFormCaptions         = [];
    $gValidationCollection = [];

    if ($gInForm) {
        trigger_error("Nested forms - form_start() inside form");
    } else {
        $gInForm = true;
    }
    if (strlen($serviceURI) <= 0) {
        $serviceURI = "?";
    }
    print "\n<Form ID='$gFormName' Action='$serviceURI' Method='post' enctype='multipart/form-data' onsubmit='return Validate" . $gFormName . "()'>\n";
    form_TableStart(0);
    $gFormSectionLevel = 0;
    $gFormGroupLevel   = 0;
}

//=============================================================================
function form_End(
    $SubmitLegend = DEFAULT_SUBMIT_BUTTON,
    $HaveResetButton = FORM_HAVE_RESET_BUTTON,
) {
    global $gInForm, $gFormName, $gFormUsedDateBox, $gFormHiddenParams,
        $gValidationCollection, $gFormSectionLevel, $gFormGroupLevel,
        $gPageDateCodeWritten, $gFormCaptions, $Language;

    if (! $gInForm) {
        trigger_error("Nested forms - form_End() outside of form");
    }
    if ($gFormSectionLevel < 0) {
        trigger_error("Unbalanced form_SectionStart/End()");
    }
    if ($gFormGroupLevel < 0) {
        trigger_error("Unbalanced form_GroupStart/End()");
    }
    if (is_null($SubmitLegend)) {
        // passing an empty string is different from defaulting to standard button
        $SubmitLegend = $Language->getText('global', 'btn_update');
    }
    if ((strlen($SubmitLegend) > 0) || ($HaveResetButton)) {
        form_NewRow();
        if (strlen($SubmitLegend) > 0) {
            form_genSubmit($SubmitLegend);
        }
        if ($HaveResetButton) {
            print "<td><INPUT TYPE='reset' Value='" .
                dgettext('tuleap-projectlinks', 'Cancel Changes') . "'></td>\n";
        }
    }
    while ($gFormGroupLevel > 0) {
        form_GroupEnd();
    }
    while ($gFormSectionLevel > 0) {
        form_SectionEnd();
    }
    form_TableEnd();

    // place them here to avoid silly form formatting problem when a table is used
    foreach ($gFormHiddenParams as $key => $value) {
        print "<INPUT TYPE='hidden' NAME='$key' VALUE='$value'>\n";
    }

    print "</form>\n";

    // write date code if needed, but only once
    if ($gFormUsedDateBox && (! $gPageDateCodeWritten)) {
        $gPageDateCodeWritten = true;
    }
    // Write form validation code
    $script         = "function Validate" . $gFormName . "()\n";
    $script        .= "{\n";
    $script        .= "var result = true;\n";
    $EmitEmailCode  = false;
    $EmitNumberCode = false;
    foreach ($gValidationCollection as $valItemKey => $valItem) {
        if (! isset($gFormCaptions[$valItem->ParamName])) {
            trigger_error("Validation item '" .
                $valItem->ParamName . "' is not a form item ($valItemKey => $valItem)");
        }
        $jsItemRef      = form_JS_ElementRef($valItem->ParamName) . ".value";
        $jsItemErrStart = "{result=false;alert('" . addslashes($gFormCaptions[$valItem->ParamName] ?? '') . ": ";
        $jsItemErrEnd   = "');}\n";
        $jsItemPresent  = "if ($jsItemRef == '')"
                    . $jsItemErrStart
                    . dgettext('tuleap-projectlinks', 'must be completed')
                    . $jsItemErrEnd;
        $jsItemNumeric  = "if (!isNumber($jsItemRef))"
                    . $jsItemErrStart
                    . dgettext('tuleap-projectlinks', 'must be a number')
                    . $jsItemErrEnd;
        switch ($valItem->Test) {
            case FORM_VAL_IS_NOT_ZERO_LENGTH:
                $script .= $jsItemPresent;
                break;
            case FORM_VAL_IS_EMAIL:
                $EmitEmailCode = true;
                $script       .= $jsItemPresent;
                $script       .= "else if (!isEmailAddr($jsItemRef))"
                    . $jsItemErrStart
                    . dgettext('tuleap-projectlinks', 'must be a valid email address')
                    . $jsItemErrEnd;
                break;
            case FORM_VAL_IS_NUMBER:
                $EmitNumberCode = true;
                $script        .= $jsItemPresent . " else " . $jsItemNumeric;
                break;
            case FORM_VAL_IS_LT:
                $EmitNumberCode = true;
                $script        .= $jsItemPresent . " else " . $jsItemNumeric . " else if ($jsItemRef>=" . $valItem->Param . ")"
                    . $jsItemErrStart
                    . sprintf(dgettext('tuleap-projectlinks', 'must be less than %1$s'), $valItem->Param)
                    . $jsItemErrEnd;
                break;
            case FORM_VAL_IS_GT:
                $EmitNumberCode = true;
                $script        .= $jsItemPresent . " else " . $jsItemNumeric . " else if ($jsItemRef<=" . $valItem->Param . ")"
                    . $jsItemErrStart
                    . sprintf(dgettext('tuleap-projectlinks', 'must be greater than %1$s'), $valItem->Param)
                    . $jsItemErrEnd;
                break;
            case FORM_VAL_IS_EQ:
                $cmp     = is_string($valItem->Param) ? "'" . $valItem->Param . "'" : $valItem->Param;
                $script .= "if ($jsItemRef!=$cmp)"
                    . $jsItemErrStart
                    . sprintf(dgettext('tuleap-projectlinks', 'must be %1$s'), $valItem->Param)
                    . $jsItemErrEnd;
                break;
            case FORM_VAL_IS_CHECKED:
                $script .= "if (!" . form_JS_ElementRef($valItem->ParamName) . ".checked)"
                    . $jsItemErrStart
                    . dgettext('tuleap-projectlinks', 'must be checked')
                    . $jsItemErrEnd;
                break;
            default:
                trigger_error("Can't handle requested validation: " . $valItem->ParamName . " (" . $valItem->Test . ")");
                break;
        }
    }
    $script .= "return result;\n";
    $script .= "}\n";
    if ($EmitEmailCode) {
        $script .= "function isEmailAddr(str)\n";
        $script .= "{\n";
        $script .= "    return str.match(/^[\w-]+(\.[\w-]+)*@([\w-]+\.)+[a-zA-Z]{2,7}$/);\n";
        $script .= "}\n";
    }
    if ($EmitNumberCode) {
        $script .= "function isNumber(str)\n";
        $script .= "{\n";
        $script .= "    return str.match(/^[+-]?[0-9]+[\.]?[0-9]*$/);\n";
        $script .= "}\n";
    }
    $GLOBALS['Response']->includeFooterJavascriptSnippet($script);
    $gInForm = false;
}

//=============================================================================
// FormValidation
//
//-----------------------------------------------------------------------------------
// Validation
class tValidation
{
    public $ParamName;
    public $Test;
    public $Param;
}

function form_Validation($ParamName, $Tests, $OptionalParam = null)
{
    global $gValidationCollection, $gInForm;

    if (! $gInForm) {
        trigger_error("form_Validation() outside of form");
    }
    if (! is_array($Tests)) {
        $Tests = [$Tests => $OptionalParam];
    }
    foreach ($Tests as $Test => $OptionalParam) {
        $valItem            = new tValidation();
        $valItem->ParamName = $ParamName;
        switch ($Test) {
            case FORM_VAL_IS_NOT_ZERO_LENGTH:
            case FORM_VAL_IS_EMAIL:
            case FORM_VAL_IS_NUMBER:
            case FORM_VAL_IS_CHECKED:
                $valItem->Param = null;
                break;

            case FORM_VAL_IS_LT:
            case FORM_VAL_IS_GT:
            case FORM_VAL_IS_EQ:
                $valItem->Param = $OptionalParam;
                break;

            default:
                trigger_error("form_Validation: Can't handle requested validation: " . $ParamName . " (" . $Test . ")");
                break;
        }
        $valItem->Test           = $Test;
        $gValidationCollection[] = $valItem;
    }
}
//=============================================================================
function form_FocusFirstTextBox()
{
    global $gFormName, $gFirstFormTextBox, $gInForm;

    if (! $gInForm) {
        trigger_error("form_FocusFirstTextBox() outside of form");
    }
    if (strlen($gFirstFormTextBox) > 0) {
        $GLOBALS['Response']->includeFooterJavascriptSnippet(form_JS_ElementRef($gFirstFormTextBox) . ".focus();");
    }
}

//=============================================================================
function form_SetFocusItem($ParamName)
{
    global $gFirstFormTextBox, $gInForm;

    if (! $gInForm) {
        trigger_error("form_SetFocusItem() outside of form");
    }
    $gFirstFormTextBox = $ParamName;
    form_FocusFirstTextBox();
}

//=============================================================================
//=== form rows, columns and submit buttons ==================================
//=============================================================================

//=============================================================================
function form_NewRow()
{
    global $gInForm;

    if (! $gInForm) {
        trigger_error("Form item outside form: Form_NewRow()");
    }
    print "</tr>\n<tr style='vertical-align: top;'>\n";
}

//=============================================================================
function form_Heading($Heading)
{
    global $gInForm;

    if (! $gInForm) {
        trigger_error("Form item outside form: form_Heading()");
    }
    print "</tr>\n<tr><th colspan='2'>" . nz($Heading, "&nbsp;") . "</th></tr>\n<tr>\n";
}

//=============================================================================
function form_Text($Text, $SectionSpan = 1)
{
    global $gInForm;
    if (! $gInForm) {
        trigger_error("Form item outside form: form_Text()");
    }
    print "<td colspan='" . (ceil($SectionSpan) * 2) . "'>" . nz($Text, "&nbsp;") .
        "</td>\n";
}

//=============================================================================
function form_SkipColumn()
{
    global $gInForm;
    if (! $gInForm) {
        trigger_error("Form item outside form: form_SkipColumn()");
    }
    print "<td>&nbsp;</td><td>&nbsp;</td>\n";
}

//=============================================================================
function form_genSubmit($SubmitLegend)
{
    global $gInForm;
    if (! $gInForm) {
        trigger_error("Form item outside form: form_genSubmit: " . SubmitLegend);
    }
    print "<td align='center'>
        <input type=submit name='" . SUBMIT_BUTTON_NAME . "'
        value='" . addslashes($SubmitLegend) . "'></td>\n";
}

//=============================================================================
function form_genSubmitImg($ImageFileHTMLPath)
{
    global $gInForm;
    if (! $gInForm) {
        trigger_error("Form item outside form: form_genSubmitImg: " . ImageFileHTMLPath);
    }
    print "<td align='center'>
        <input type='image'" . FORM_BUTTON_STYLE . " src='" . $ImageFileHTMLPath . "'
        value='" . SUBMIT_BUTTON_NAME . "' alt='Submit'
        name='" . SUBMIT_BUTTON_NAME . "' /></td>\n";
}

//=============================================================================
function form_SectionStart($Text = null)
{
    global $gInForm, $gFormSectionLevel;
    print "\n<!-- form_SectionStart (start) -->\n";
    if (! $gInForm) {
        trigger_error("Form item outside form: form_SectionStart()");
    }
    form_TableEnd();
    form_TableStart(1);
    print "<td>" . (is_null($Text) ?
        "" : "<span style='font-weight: bold;'>$Text</span>") . "\n";
    form_TableStart(0);
    $gFormSectionLevel += 1;
    print "\n<!-- form_SectionStart (end) -->\n";
}

//=============================================================================
function form_SectionEnd()
{
    global $gInForm, $gFormSectionLevel;
    print "\n<!-- form_SectionEnd (start) -->\n";
    if (! $gInForm) {
        trigger_error("Form item outside form: form_SectionEnd()");
    }
    if ($gFormSectionLevel <= 0) {
        trigger_error("form_SectionEnd when no section started");
    }
    form_TableEnd();
    print "</td>\n";
    form_TableEnd();
    form_TableStart(0);
    $gFormSectionLevel -= 1;
    print "\n<!-- form_SectionEnd (end) -->\n";
}

//=============================================================================
function form_GroupStart($Caption = "", $GroupHeading = "", $ColSpan = 1)
{
    global $gInForm, $gFormGroupLevel;

    if (! $gInForm) {
        trigger_error("Form item outside form: form_GroupStart()");
    }
    if (strlen($Caption) > 0) {
        print "<td" . FORM_CAPTION_STYLE . ">$Caption</td>";
    }
    if ($ColSpan > 2) {
        print "<td Colspan='" . ($ColSpan - 1) . "'>\n";
    } else {
        print "<td>\n";
    }
    print "<fieldset>\n";
    if (strlen($GroupHeading) > 0) {
        print "<legend>" . $GroupHeading . "</legend>\n";
    }
    form_TableStart(0);
    $gFormGroupLevel += 1;
}

//=============================================================================
function form_GroupEnd()
{
    global $gInForm, $gFormGroupLevel;

    if (! $gInForm) {
        trigger_error("Form item outside form: form_GroupEnd()");
    }
    if ($gFormGroupLevel <= 0) {
        trigger_error("form_GroupEnd when no group started");
    }
    form_TableEnd();
    print "</fieldset>\n";
    print "</td>\n";
    $gFormGroupLevel -= 1;
}

//=============================================================================
//=== form control and boxes =================================================
//=============================================================================

//=============================================================================
function form_SelectAllCheckbox($GroupName = "", $isChecked = false)
{
    global $gInForm, $gFormName, $Language;
    static $gFormSelectAllCodeWritten = false;

    if (! $gInForm) {
        trigger_error("Form item outside form: form_SelectAllCheckbox()");
    }
    if (! $gFormSelectAllCodeWritten) {
        $gFormSelectAllCodeWritten = true;

        $script  = "function formSelectDeselectAll(_element, formname, elementname)\n";
        $script .= "{\n";
        $script .= " var el = document.forms[formname].elements;\n";
        $script .= " for (var i = 0; i < el.length; i++) {\n";
        $script .= "  if (el[i] != _element && el[i].type == 'checkbox' && ((el[i].name == elementname) ||(elementname == ''))) {\n";
        $script .= "   el[i].checked = _element.checked;\n";
        $script .= "  }\n";
        $script .= " }\n";
        $script .= "}\n";
        $GLOBALS['Response']->includeFooterJavascriptSnippet($script);
    }
    print "<INPUT onclick=\"formSelectDeselectAll(this, '$gFormName', '$GroupName');\" type='checkbox'" . ($isChecked ? " CHECKED" : "") . " Title='" . dgettext('tuleap-projectlinks', 'Select All') . "'>\n";
}

//=============================================================================
function form_HiddenParams($params)
{
    global $gInForm, $gFormHiddenParams;

    if (! $gInForm) {
        trigger_error("HiddenParams called outside of form");
    }
    foreach ($params as $name => $value) {
        if (isset($gFormHiddenParams[$name])) {
            $gFormHiddenParams[$name] .= "," . $value;
        } else {
            $gFormHiddenParams[$name] = $value;
        }
    }
}

//=============================================================================
function form_genJSButton($Caption, $JavaScript, $ImageFileHTMLPath = null)
{
// JavaScript must use only single quotes, since the double quotes are used to enclose it
    global $gInForm;

    $CaptionReplaced = addslashes(strip_tags(nz($Caption, "Go")));
    if (stristr($JavaScript, '"')) {
        trigger_error("form_GenJSButton: JavaScript must not use double quotes");
    }
    if ($gInForm) {
        print "<td>";
    }
    print "<button type='button'" . FORM_BUTTON_STYLE . " onclick=\"javascript:$JavaScript;\" title='$CaptionReplaced' name='$CaptionReplaced'>";
    if (is_null($ImageFileHTMLPath)) {
        print $Caption;
    } else {
        print "<img src='$ImageFileHTMLPath' alt='$Caption'>";
    }
    print "</button>";
    if ($gInForm) {
        print "</td>\n";
    }
}

//=============================================================================
define("SUBMIT_ON_CHANGE", 1);
define("NO_SUBMIT_ON_CHANGE", 0);

//=============================================================================
define("NO_BLANK_ROW_PREFIX", false);
define("BLANK_ROW_PREFIX", true);

//=============================================================================
function form_genTextBox($ParamName, $Caption, $DefaultValue = "", $Width = FORM_TEXT_AREA_WIDTH, $MaxInputLength = 0)
{
// if the default value is prefixed with "COF:" (clear on focus) {
// the box is displayed with the DefaultValue as help text, and the text
// vanishes when the box is clicked
    global $gInForm, $gFormCaptions, $gFirstFormTextBox;

    if (! $gInForm) {
        trigger_error("Form item outside form: form_genTextBox()");
    }
    if (isset($gFormCaptions[$ParamName])) {
        trigger_error("Form item name is reused: $ParamName");
    }
    $gFormCaptions[$ParamName] = $Caption;
    print "<td" . FORM_CAPTION_STYLE . ">$Caption</td>";
    if ($Width > FORM_STD_COL_WIDTH) {
        print "<td colspan=" . ceil($Width / FORM_STD_COL_WIDTH) . ">";
    } else {
        print "<td>";
    }
    switch (strtoupper(substr($DefaultValue, 0, 4))) {
        case "COF:":    // clear on focus
            $DefaultValue = substr($DefaultValue, 4);
            $Script       = " onfocus=\"JavaScript:if (this.value == '" . addslashes($DefaultValue) . "') this.value='';\"";
            break;
        default:
            $Script = "";
            break;
    }
    print "<input type='text'" . $Script . " name='$ParamName'" . ($Width > 0 ? " size=" . $Width : "") . ($MaxInputLength > 0 ? " maxlength=" . $MaxInputLength : "") .
        " value='" . addslashes($DefaultValue) . "'>";
    print "</td>\n";
    if ($gFirstFormTextBox == "") {
        $gFirstFormTextBox = $ParamName;
    }
}

//=============================================================================
function form_genShowBox($Caption, $Value, $UpdateURL = null)
{
// just display the information
    global $gInForm;

    if (! $gInForm) {
        trigger_error("Form item outside form: form_genShowBox()");
    }
    if (strlen($Caption) > 0) {
        print "<td" . FORM_CAPTION_STYLE . ">$Caption</td><td>";
    } else {
        print "<td colspan=2>";
    }
    print nz($Value, "&nbsp;");
    if (! is_null($UpdateURL)) {
        print "&nbsp;<span style='font-size: 80%;'" . mkAH("-&gt;&gt;", $UpdateURL) . "</span>";
    }
    print "</td>\n";
}

//=============================================================================
function form_genFileBox($ParamName, $Caption, $DefaultValue = "", $AcceptableMimeTypes = null)
{
    global $gInForm, $gFormCaptions, $gFirstFormTextBox;
    if (! $gInForm) {
        trigger_error("Form item outside form: form_genFileBox()");
    }
    if (isset($gFormCaptions[$ParamName])) {
        trigger_error("Form item name is reused: $ParamName");
    }
    $gFormCaptions[$ParamName] = $Caption;
    print "<td" . FORM_CAPTION_STYLE . ">$Caption</td><td><input type='file' name='$ParamName'";
    if (! is_null($AcceptableMimeTypes)) {
        print " accept='$AcceptableMimeTypes'";
    }
    print " value='" . addslashes($DefaultValue) . "' size='50%'></td>\n";    //note that most browsers see default here as a security issue and ignore it
    if ($gFirstFormTextBox == "") {
        $gFirstFormTextBox = $ParamName;
    }
}

//=============================================================================
function form_genCheckbox($ParamName, $Caption, $ValueIfChecked, $DefaultValue = "", $SubmitOnChange = NO_SUBMIT_ON_CHANGE)
{
    global $gInForm, $gFormCaptions;
    if (! $gInForm) {
        trigger_error("Form item outside form: form_genCheckbox()");
    }
    if (isset($gFormCaptions[$ParamName])) {
        // only a problem is the array notification is not used
        if (substr($ParamName, -2) <> "[]") {
            trigger_error("Form item name is reused without array[] identifier: $ParamName");
        }
    }
    $gFormCaptions[$ParamName] = $Caption;
    print "<td colspan=2><input type='Checkbox' name='$ParamName'" . ($SubmitOnChange ? " onchange='this.form.submit();'" : "");
    if (strlen($ValueIfChecked) > 0) {
        print " value='" . addslashes($ValueIfChecked) . "'";
    }
    if (is_bool($DefaultValue)) {
        if ($DefaultValue) {
            print " Checked";
        }
    } elseif ($DefaultValue == $ValueIfChecked) {
        print " Checked";
    }
    if (strlen($Caption) > 0) {
        print ">&nbsp;<span" . FORM_CAPTION_STYLE . ">$Caption</span>\n";
    } else {
        print ">\n";
    }
    print "</td>\n";
}

/*
//=============================================================================
define("DATE_DEFAULT_STR", "d/m[/yy[yy]]");

Function DefaultInputDate($DateStr, $DefaultDate)
{
    $DateStr = trim($DateStr);
    if (strcasecmp($DateStr, DATE_DEFAULT_STR) == 0) {
        $DateStr = "";
    }
    if (strlen($DateStr) <= 0) {
        $DefaultInputDate = $DefaultDate;
    } elseif  (isDate($DateStr)) {
        $DefaultInputDate = DateValue($DateStr);
    } else {
        $DefaultInputDate = $DefaultDate;
    }
}

//=============================================================================
$gFormUsedDateBox = False;    // global indicating if form used form_genDateBox - if it did, JS code needs to be written on form {
$gPageDateCodeWritten = False; // ... but only once per page, no matter how many forms

function form_genDateBox($ParamName, $Caption, $DefaultValue = "")
{
// if the default value is prefixed with "COF:" (clear on focus) {
// the box is displayed with the DefaultValue (as help text), and the text
// vanishes when the box is clicked
    global $gInForm, $gFormCaptions;
    if (! $gInForm) {
        trigger_error("Form item outside form: form_genDateBox()");
    }
    if (isset($gFormCaptions[$ParamName])) {
        trigger_error("Form item name is reused: $ParamName");
    }
    $gFormCaptions[$ParamName] = $Caption;
    print "<td".FORM_CAPTION_STYLE.">Caption</td><td>";

    switch(strtoupper(substr($DefaultValue, 0, 4))) {
        case "COF:":    // clear on focus
            $DefaultValue = substr($DefaultValue, 4);
            $Script = " onFocus=\"JavaScript:if (this.value == '".addslashes(DefaultValue). "') this.value='';\"";
    break;
       default:
            $Script = "";
    break;
    }
    print "<input type='text' name='$ParamName' size=8 maxlength=10 value='".addslashes($DefaultValue)."'$Script>";
    print "<a href='javascript:DateChooser(\"$gFormName\", \"$ParamName\");'
        onMouseOver='status=\"Choose a date\"; return true;' onMouseOut='status=\"\"'>
        <img src='/images/cal.gif' width='16' height='16' style='border:0' alt='Date Chooser'></a>\n";
    $gFormUsedDateBox = True;
    print "</td>\n";
}
*/

//=============================================================================
function form_genRadioButton($ParamName, $options, $DefaultValue = "")
{
    global $gInForm, $gFormCaptions;

    if (! $gInForm) {
        trigger_error("Form item outside form: form_genRadioButton()");
    }
    foreach ($options as $Caption => $ValueIfChecked) {
        if (isset($gFormCaptions[$ParamName])) {
            $gFormCaptions[$ParamName] .= "; " . $Caption;
        } else {
            $gFormCaptions[$ParamName] = $Caption;
        }
        print "<td><input type='Radio' name='$ParamName'";
        if (strlen($ValueIfChecked) > 0) {
            print " value='" . addslashes($ValueIfChecked) . "'";
        }
        if (is_bool($DefaultValue)) {
            if ($DefaultValue) {
                print " Checked";
            }
        } elseif ($DefaultValue == $ValueIfChecked) {
            print " Checked";
        }
        print "><span" . FORM_CAPTION_STYLE . ">$Caption</span></td>\n";
    }
}

//=============================================================================
function form_genTextArea($ParamName, $Caption, $DefaultValue = "", $MinRows = FORM_TEXT_AREA_DEPTH, $Cols = FORM_TEXT_AREA_WIDTH)
{
    global $gInForm, $gFormCaptions, $gFirstFormTextBox;

    if (! $gInForm) {
        trigger_error("Form item outside form: form_genTextArea()");
    }
    if (isset($gFormCaptions[$ParamName])) {
        trigger_error("Form item name is reused: $ParamName");
    }
    $gFormCaptions[$ParamName] = $Caption;
    if ($Cols < 10) {
        $Cols = 10;
    }

    // calculate number of rows to give best chance of not scrolling
    $Rows = ceil((strlen($DefaultValue) / $Cols) * 1.6) + 2;
    if ($Rows < $MinRows) {
        $Rows = $MinRows;
    } elseif (($Rows > FORM_MAX_TEXT_AREA_ROWS) && ($MinRows <= FORM_MAX_TEXT_AREA_ROWS)) {
        $Rows = FORM_MAX_TEXT_AREA_ROWS;
    }
    print "<td" . FORM_CAPTION_STYLE . ">$Caption</td>";
    if ($Cols > FORM_STD_COL_WIDTH) {
        print "<td colspan=" . ceil($Cols / FORM_STD_COL_WIDTH) . ">";
    } else {
        print "<td>";
    }
    print "<TextArea name='$ParamName' rows=$Rows cols=$Cols wrap='soft'>"
        . $DefaultValue
        . "</textarea></td>\n";
    if ($gFirstFormTextBox == "") {
        $gFirstFormTextBox = $ParamName;
    }
}

//=============================================================================
function form_TableStart($BorderWidth)
{
    print "\n<table border='$BorderWidth' cellspacing='1' cellpadding='2'><tr style='vertical-align: top;'>\n";
}

//=============================================================================
function form_TableEnd()
{
    print "</tr>\n</table>\n";
}

//=============================================================================
function form_JS_ElementRef($Elementname)
{
    // Used to reference a (current) form item in javascript, e.g.:
    global $gFormName, $gInForm;

    if (! $gInForm) {
        trigger_error("Form item outside form: form_JS_ElementRef()");
    }
    return "document.forms['$gFormName'].elements['$Elementname']";
}

//=============================================================================
/**
 * @psalm-pure
 */
function nz($item, $default)
{
    if (is_null($item) || ($item == "")) {
        return $default;
    } else {
        return $item;
    }
}

//=============================================================================
function mkAH($caption, $URL, $title = "", $params = null)
{
    // $params = array("param" => "value"); e.g. $params = array("onclick" => "function()") - NOTE: additional parameters must not use double quotes
    return "<a href='" . str_replace("'", "&#039;", $URL) . "'" .
        parseAdditionalParams($params) .
        ((strlen($title) <= 0) ? "" : " title='" . str_replace("'", "&#039;", $title) . "'") .
        ">$caption</a>";
}

//=============================================================================
function parseAdditionalParams($params = null)
{
    $str = "";
    if (! is_null($params)) {
        foreach ($params as $item => $value) {
            if (stristr($value, '"')) {
                trigger_error("additional parameters must not use double quotes");
            }
            $str .= " " . $item . "=\"" . $value . "\"";
        }
    }
    return $str;
}

//=============================================================================
function graphicForBoolean($bool)
{
    if ($bool) {
        return '<img src="' . util_get_image_theme("ic/check.png") . '" border=0 alt="Y">';
    } else {
        return '<span style="font-weight: bold">X</span>';
    }
}

/**
this is not completed, but here as a starting point - problems:
- uri like "/projects/project-name" is not handeled correctly - needs a trainig slash
- should parse the existing parameters and replace them if changed by the argument
//=============================================================================
function repeat_uri($new_params)
{
    $uri = $_SERVER['REQUEST_URI'];
    $separator = stristr($uri, "?")?"&amp;":"?";
    foreach ($new_params as $name => $value) {
        $uri .= $separator.$name."=".$value;
        $separator = "&amp;";
    }
}
**/

//=============================================================================
function update_database($tableName, $items, $selectCriteria = "")
{
    // database utitlity to insert./update DB record
    if (stristr($selectCriteria, ";")) {
        exit_error(
            "update_database: Select criteria contains illegal character",
            "$tableName: " . htmlentities($selectCriteria)
        );
    }
    $sql = "";
    if (strlen($selectCriteria) <= 0) {
        // Insert
        $SQLitems  = "";
        $SQLValues = "";
        foreach ($items as $name => $value) {
            $SQLitems  .= (strlen($SQLitems) ? "," : "") . " $name";
            $SQLValues .= (strlen($SQLValues) ? "," : "") . " $value";
        }
        $sql = "INSERT INTO $tableName ($SQLitems) VALUES ($SQLValues);";
    } else {
        // Update
        $SQLstr = "";
        foreach ($items as $name => $value) {
            $SQLstr .= (strlen($SQLstr) ? ", " : "") . " $name=$value";
        }
        $sql = "UPDATE $tableName SET $SQLstr WHERE ($selectCriteria);";
    }
    return db_query($sql);
}
