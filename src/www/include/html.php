<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\Layout\HeaderConfiguration;

function html_feedback_top($feedback)
{
    echo $GLOBALS['HTML']->feedback($GLOBALS['feedback']);
}

function html_feedback_bottom($feedback)
{
    echo $GLOBALS['HTML']->feedback($GLOBALS['feedback']);
}

function html_image($src, $args, $display = 1)
{
    global $img_size;
    $return   = ('<IMG src="' . util_get_dir_image_theme() . $src . '"');
    $purifier = Codendi_HTMLPurifier::instance();
    foreach ($args as $k => $v) {
        $return .= ' ' . $purifier->purify($k) . '="' . $purifier->purify($v) . '"';
    }

    // ## insert a border tag if there isn't one
    if (! isset($args['border']) || ! $args['border']) {
        $return .= (" border=0");
    }

    // ## if no height AND no width tag, insert em both
    if (
        (! isset($args['height']) || ! $args['height']) &&
            (! isset($args['width']) || ! $args['width'])
    ) {
     /* Check to see if we've already fetched the image data */
        if ($img_size) {
            if ((! isset($img_size[$src]) || ! $img_size[$src]) && is_file(ForgeConfig::get('sys_urlroot') . util_get_dir_image_theme() . $src)) {
                $img_size[$src] = @getimagesize(ForgeConfig::get('sys_urlroot') . util_get_dir_image_theme() . $src);
            }
        } else {
            if (is_file(ForgeConfig::get('sys_urlroot') . util_get_dir_image_theme() . $src)) {
                $img_size[$src] = @getimagesize(ForgeConfig::get('sys_urlroot') . util_get_dir_image_theme() . $src);
            }
        }
        if (isset($img_size[$src][0], $img_size[$src][1])) {
            $return .= ' width="' . $img_size[$src][0] . '" height="' . $img_size[$src][1] . '"';
        }
    }

    // ## insert alt tag if there isn't one
    if (! isset($args['alt']) || ! $args['alt']) {
        $return .= ' alt="' . $purifier->purify($src) . '"';
    }

    $return .= ('>');
    if ($display) {
        print $return;
    } else {
        return $return;
    }
}

/**
 * html_get_language_popup() - Pop up box of supported languages
 *
 * @param        object    BaseLanguage object
 * @param        string    The title of the popup box
 * @param        string    Which element of the box is to be selected
 */
function html_get_language_popup($Language, $title = 'language_id', $selected = 'xzxzxz')
{
    $hp               = Codendi_HTMLPurifier::instance();
    $language_factory = new BaseLanguageFactory();

    $html = '<select name="' . $hp->purify($title) . '">';
    foreach ($language_factory->getAvailableLanguages() as $code => $lang) {
        $select = ($selected == $code) ? 'selected="selected"' : '';
        $html  .= '<option value="' .  $hp->purify($code, CODENDI_PURIFIER_CONVERT_HTML)  . '" ' . $select . '>';
        $html  .= $hp->purify($lang, CODENDI_PURIFIER_CONVERT_HTML);
        $html  .= '</option>';
    }
    $html .= '</select>';
    return $html;
}


function html_build_list_table_top($title_arr, $links_arr = false, $mass_change = false, $full_width = true, $id = null, $class = null, $cellspacing = 1, $cellpadding = 2)
{
    /*
        Takes an array of titles and builds
        The first row of a new table

        Optionally takes a second array of links for the titles
    */
    $purifier = Codendi_HTMLPurifier::instance();
    $return   = '
       <TABLE data-test="table-test"';
    if ($full_width) {
        $return .= 'WIDTH="100%" ';
    }
    if ($id) {
        $return .= 'id="' . $purifier->purify($id) . '"';
    }
    if ($class) {
        $return .= ' class="' . $purifier->purify($class) . '" ';
    }
    $return .= 'BORDER="0" CELLSPACING="' . $purifier->purify($cellspacing) . '" CELLPADDING="' . $purifier->purify($cellpadding) . '">
		<TR class="boxtable">';

    if ($mass_change) {
        $return .= '<TD class="boxtitle">Select?</TD>';
    }
    $count = count($title_arr);
    if ($links_arr) {
        for ($i = 0; $i < $count; $i++) {
            if (empty($links_arr[$i])) {
                $return .= '<td class="boxtitle">' . $purifier->purify($title_arr[$i]) . '</td>';
            } else {
                $return .= '
			        <TD class="boxtitle"><a class=sortbutton href="' . $purifier->purify($links_arr[$i]) . '">' . $purifier->purify($title_arr[$i]) . '</A></TD>';
            }
        }
    } else {
        for ($i = 0; $i < $count; $i++) {
            $return .= '
			<TD class="boxtitle">' . $purifier->purify($title_arr[$i]) . '</TD>';
        }
    }
    return $return . '</TR>';
}

//deprecated
function util_get_alt_row_color($i)
{
    return html_get_alt_row_color($i);
}

//function util_get_alt_row_color ($i) {
function html_get_alt_row_color($i)
{
    global $HTML;
    if ($i % 2 == 0) {
        return 'boxitem';
    } else {
        return 'boxitemalt';
    }
}

function html_build_select_box_from_array($vals, $select_name, $checked_val = 'xzxz', $samevals = 0)
{
    /*
        Takes one array, with the first array being the "id" or value
        and the array being the text you want displayed

        The second parameter is the name you want assigned to this form element

        The third parameter is optional. Pass the value of the item that should be checked
    */

    $purifier = Codendi_HTMLPurifier::instance();
    $return   = '
		<SELECT NAME="' . $purifier->purify($select_name) . '" id="' . $purifier->purify($select_name) . '">';

    foreach ($vals as $value => $label) {
        if ($samevals) {
            $return .= '<OPTION VALUE="' . $purifier->purify($label) . '""';
            if ($label == $checked_val) {
                $return .= ' SELECTED';
            }
        } else {
            $return .= '<OPTION VALUE="' . $purifier->purify($value) . '"';
            if ($value == $checked_val) {
                $return .= ' SELECTED';
            }
        }
        $return .= '>' . $purifier->purify($label) . '</OPTION>';
    }
    $return .= '
		</SELECT>';

    return $return;
}

/**
 * @deprecated This function miss some purifications voluntary. Please, DO NOT USE it anymore !
 */
function html_build_select_box_from_arrays(
    $vals,
    $texts,
    $select_name,
    $checked_val = 'xzxz',
    $show_100 = true,
    $text_100 = '',
    $show_any = false,
    $text_any = '',
    $show_unchanged = false,
    $text_unchanged = '',
    $purify_level = CODENDI_PURIFIER_CONVERT_HTML,
    $show_unknown_value = true,
) {
    global $Language;
        $return           = '';
        $isAValueSelected = false;
        $hp               = Codendi_HTMLPurifier::instance();

    /*

        The infamous '100 row' has to do with the
            SQL Table joins done throughout all this code.
        There must be a related row in users, categories, etc, and by default that
            row is 100, so almost every pop-up box has 100 as the default
        Most tables in the database should therefore have a row with an id of 100 in it
            so that joins are successful

        There is now another infamous row called the Any row. It is not
        in any table as opposed to 100. it's just here as a convenience mostly
        when using select boxes in queries (bug, task,...). The 0 value is reserved
        for Any and must not be used in any table.

        Params:

        Takes two arrays, with the first array being the "id" or value
        and the other array being the text you want displayed

        The third parameter is the name you want assigned to this form element

        The fourth parameter is optional. Pass the value of the item that should be checked

        The fifth parameter is an optional boolean - whether or not to show the '100 row'

        The sixth parameter is optional - what to call the '100 row' defaults to none
        The 7th parameter is an optional boolean - whether or not to show the 'Any row'

        The 8th parameter is optional - what to call the 'Any row' defaults to nAny    */

        // Position default values for special menu items
    if ($text_100 == '') {
        $text_100 = $Language->getText('global', 'none');
    }
    if ($text_any == '') {
        $text_any = $Language->getText('global', 'any');
    }
    if ($text_unchanged == '') {
        $text_unchanged = $Language->getText('global', 'unchanged');
    }

    if (is_array($checked_val)) {
        $return .= '
			<SELECT id="' . $select_name . '" NAME="' . $select_name . '[]" MULTIPLE SIZE="6">';
    } else {
        $return .= '
			<SELECT id="' . $select_name . '" NAME="' . $select_name . '">';
    }

    /*
        Put in the Unchanged box
    */
    if ($show_unchanged) {
        $return           .= '<OPTION VALUE="' . $hp->purify($text_unchanged) . '" SELECTED>' . $hp->purify($text_unchanged, $purify_level) . '</OPTION>';
         $isAValueSelected = true;
    }

    //we don't always want the default any  row shown
    if ($show_any) {
        if (is_array($checked_val)) {
            if (in_array(0, $checked_val)) {
                $selected         = "SELECTED";
                $isAValueSelected = true;
            } else {
                $selected = "";
            }
        } else {
            $selected = ( $checked_val == 0 ? 'SELECTED' : '');
            if ($checked_val == 0) {
                $isAValueSelected = true;
            }
        }
        $return .= '<OPTION VALUE="0" ' . $selected . '>' . $hp->purify($text_any, $purify_level) . '</OPTION>';
    }

    //we don't always want the default 100 row shown
    if ($show_100) {
        if (is_array($checked_val)) {
            if (in_array(100, $checked_val)) {
                $selected         = "SELECTED";
                $isAValueSelected = true;
            } else {
                $selected = "";
            }
        } else {
            $selected = ( $checked_val == 100 ? 'SELECTED' : '');
            if ($checked_val == 100) {
                $isAValueSelected = true;
            }
        }
        $return .= '<OPTION VALUE="100" ' . $selected . '>' . $hp->purify($text_100, $purify_level) . '</OPTION>';
    }

    $rows = count($vals);
    if (count($texts) != $rows) {
        $return .= 'ERROR - uneven row counts';
    }

    for ($i = 0; $i < $rows; $i++) {
        //  uggh - sorry - don't show the 100 row and Any row
        //  if it was shown above, otherwise do show it
        if (
            (($vals[$i] != '100') && ($vals[$i] != '0')) ||
            ($vals[$i] == '100' && ! $show_100) ||
            ($vals[$i] == '0' && ! $show_any)
        ) {
            $return .= '
				<OPTION VALUE="' . $hp->purify($vals[$i]) . '"';
            if (is_array($checked_val)) {
                if (in_array($vals[$i], $checked_val)) {
                    $return          .= ' SELECTED';
                    $isAValueSelected = true;
                }
            } else {
                if ($vals[$i] == $checked_val) {
                    $return          .= ' SELECTED';
                    $isAValueSelected = true;
                }
            }
            $return .= '>' . $hp->purify($texts[$i], $purify_level) . '</OPTION>';
        }
    }
    if ($show_unknown_value && ($checked_val && $checked_val != 'xzxz' && ! $isAValueSelected)) {
        $return .= '<OPTION VALUE="' . $hp->purify($checked_val) . '" SELECTED>' . $hp->purify($Language->getText('include_html', 'unknown_value'), $purify_level) . '</OPTION>';
    }
    $return .= '
		</SELECT>';
    return $return;
}

function html_build_select_box(
    $result,
    $name,
    $checked_val = "xzxz",
    $show_100 = true,
    $text_100 = '',
    $show_any = false,
    $text_any = '',
    $show_unchanged = false,
    $text_unchanged = '',
    $purify_level = CODENDI_PURIFIER_CONVERT_HTML,
    $show_unknown_value = true,
) {
        global $Language;
    /*
        Takes a result set, with the first column being the "id" or value
        and the second column being the text you want displayed

        The second parameter is the name you want assigned to this form element

        The third parameter is optional. Pass the value of the item that should be checked

        The fourth parameter is an optional boolean - whether or not to show the '100 row'

        The fifth parameter is optional - what to call the '100 row' defaults to none
    */

        // Position default values for special menu items
    if ($text_100 == '') {
        $text_100 = $Language->getText('global', 'none');
    }
    if ($text_any == '') {
        $text_any = $Language->getText('global', 'any');
    }
    if ($text_unchanged == '') {
        $text_unchanged = $Language->getText('global', 'unchanged');
    }

    /** @psalm-suppress DeprecatedFunction */
    return html_build_select_box_from_arrays(
        util_result_column_to_array($result, 0),
        util_result_column_to_array($result, 1),
        $name,
        $checked_val,
        $show_100,
        $text_100,
        $show_any,
        $text_any,
        $show_unchanged,
        $text_unchanged,
        $purify_level,
        $show_unknown_value
    );
}

function html_build_multiple_select_box($result, $name, $checked_array, $size = '8', $show_100 = true, $text_100 = '', $show_any = false, $text_any = '', $show_unchanged = false, $text_unchanged = '', $show_value = true, $purify_level = CODENDI_PURIFIER_CONVERT_HTML, $disabled = false)
{
    if (is_array($result)) {
        $array =& $result;
    } else {
        $array = [];
        while ($row = db_fetch_array($result)) {
            $array[] = ['value' => $row[0], 'text' => $row[1]];
        }
    }
    return html_build_multiple_select_box_from_array($array, $name, $checked_array, $size, $show_100, $text_100, $show_any, $text_any, $show_unchanged, $text_unchanged, $show_value, $purify_level, $disabled);
}
function html_build_multiple_select_box_from_array($array, $name, $checked_array, $size = '8', $show_100 = true, $text_100 = '', $show_any = false, $text_any = '', $show_unchanged = false, $text_unchanged = '', $show_value = true, $purify_level = CODENDI_PURIFIER_CONVERT_HTML, $disabled = false)
{
        global $Language;
    /*
        Takes a result set, with the first column being the "id" or value
        and the second column being the text you want displayed

        The second parameter is the name you want assigned to this form element

        The third parameter is an array of checked values;

        The fourth parameter is optional. Pass the size of this box

        Fifth to eigth params determine whether to show None and Any

        Ninth param determine whether to show numeric values next to
        the menu label (default true for backward compatibility
    */
        $hp = Codendi_HTMLPurifier::instance();

        // Position default values for special menu items
    if ($text_100 == '') {
        $text_100 = $Language->getText('global', 'none');
    }
    if ($text_any == '') {
        $text_any = $Language->getText('global', 'any');
    }
    if ($text_unchanged == '') {
        $text_unchanged = $Language->getText('global', 'unchanged');
    }
        $disabled = $disabled ? 'disabled="disabled"' : '';

    $id     = str_replace('[]', '', $name);
    $return = '
		<SELECT NAME="' . $hp->purify($name) . '" id="' . $hp->purify($id) . '" MULTIPLE SIZE="' . $hp->purify($size) . '" ' . $disabled . '>';

    /*
        Put in the Unchanged box
    */
    if ($show_unchanged) {
        $return .= "\n" . '<OPTION VALUE="' . $hp->purify($text_unchanged) . '" SELECTED>' . $hp->purify($text_unchanged, $purify_level) . '</OPTION>';
    }

    /*
        Put in the Any box
    */
    if ($show_any) {
        $return .= '
		<OPTION VALUE="0"';
        if (is_iterable($checked_array)) {
            foreach ($checked_array as $value) {
                if ($value == '0') {
                    $return .= ' SELECTED';
                }
            }
        }
        $return .= '>' . $hp->purify($text_any, $purify_level) . '</OPTION>';
    }

    /*
        Put in the default NONE box
    */
    if ($show_100) {
        $return .= '
		<OPTION VALUE="100"';
        if (is_iterable($checked_array)) {
            foreach ($checked_array as $value) {
                if ($value == '100') {
                    $return .= ' SELECTED';
                }
            }
        }
        $return .= '>' . $hp->purify($text_100, $purify_level) . '</OPTION>';
    }

    foreach ($array as $row) {
        $val = $row['value'];
        if ($val != '100') {
            $return .= '
				<OPTION VALUE="' . $hp->purify($val) . '"';
            /*
                Determine if it's checked
            */
            if (is_iterable($checked_array)) {
                foreach ($checked_array as $array_value) {
                    if ($array_value == $val) {
                        $return .= ' SELECTED';
                    }
                }
            }
            $return .= '>' . $hp->purify(($show_value ? $val . '-' : '') . substr($row['text'], 0, 60), $purify_level) . '</OPTION>';
        }
    }
    $return .= '
		</SELECT>';
    return $return;
}

/*!     @function site_user_header
        @abstract everything required to handle security and
                add navigation for user pages like /my/ and /account/
        @param params array() must contain $user_id
        @result text - echos HTML to the screen directly
*/
function site_header(HeaderConfiguration|array $params): void
{
    global $HTML;

    if (is_array($params) && isset($params['project'])) {
        if ($params['project']->isTemplate()) {
            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('include_layout', 'template_warning'));
        }
    }
    echo $HTML->header($params);
    echo html_feedback_top($GLOBALS['feedback']);
}

function site_footer($params)
{
    global $HTML;
    echo html_feedback_bottom($GLOBALS['feedback']);
    $HTML->footer($params);
}


/*!     @function site_project_header
    @abstract everything required to handle security and state checks for a project web page
    @param params array() must contain $toptab and $group
    @result text - echos HTML to the screen directly
*/
function site_project_header(Project $project, HeaderConfiguration|array $params)
{
    /*
        Check to see if active
        Check to see if private (if private check if user_ismember)
    */

    //group doesn't exist
    if ($project->isError()) {
        exit_error($GLOBALS['Language']->getText('include_html', 'invalid_g'), $GLOBALS['Language']->getText('include_html', 'g_not_exist'));
    }

    //for dead projects must be member of admin project
    if (! $project->isActive()) {
        HTTPRequest::instance()->checkUserIsSuperUser();
    }

    if (is_array($params)) {
        $params['project'] = $project;
    } elseif ($params->in_project === null) {
        throw new Exception("site_project_header is supposed to be called in a project context");
    }

    $is_asking_for_printer_version = is_array($params) && isset($params['pv']) && $params['pv']
        || $params instanceof HeaderConfiguration && $params->printer_version;
    if ($is_asking_for_printer_version && isset($GLOBALS['HTML']) && $GLOBALS['HTML'] instanceof Layout) {
        // Printer version: no right column, no tabs...
        $GLOBALS['HTML']->pv_header($params);
    } else {
        site_header($params);
    }
}

/*!     @function site_project_footer
    @abstract currently a simple shim that should be on every project page,
        rather than a direct call to site_footer() or theme_footer()
    @param params array() empty
    @result text - echos HTML to the screen directly
*/
function site_project_footer($params)
{
    if (! isset($GLOBALS['HTML']) || ! ($GLOBALS['HTML'] instanceof \Tuleap\Layout\BaseLayout)) {
        return;
    }
    if (isset($params['pv']) && $GLOBALS['HTML'] instanceof Layout && $params['pv'] != 0) {
        // Printer version
        $GLOBALS['HTML']->pv_footer();
    } else {
        echo html_feedback_bottom($GLOBALS['feedback']);
        $GLOBALS['HTML']->footer($params);
    }
}

function html_trash_link($link, $warn, $alt)
{
    $purifier = Codendi_HTMLPurifier::instance();
    return '<a data-test="html_trash_link" href="' . $link . '" onClick="return confirm(\'' . $purifier->purify($warn, CODENDI_PURIFIER_JS_QUOTE) . '\')">' .
        '<img src="' . util_get_image_theme("ic/trash.png") . '" ' . 'height="16" width="16" border="0" alt="' . $purifier->purify($alt) . '" title="' . $purifier->purify($alt) . '">' .
        '</a>';
}

/**
 *
 *  Returns a date operator field
 *
 *  @param value: initial value
 *  @param ro: if true, the field is read-only
 *
 *    @return    string
 */
function html_select_operator($name = '', $value = '', $ro = false)
{
    if ($ro) {
        $html = htmlspecialchars($value);
    } else {
        $html = '<select name="' . $name . '">' .
        '<option value="1"' . (($value == '1') ? 'selected="selected"' : '') . '>&gt;</option>' .
        '<option value="0"' . (($value == '0') ? 'selected="selected"' : '') . '>=</option>' .
        '<option value="-1"' . (($value == '-1') ? 'selected="selected"' : '') . '>&lt;</option>' .
        '</select>';
    }
    return($html);
}

/**
 *  Returns a date field
 *
 *  @param value: initial value
 *  @param size: the field size
 *  @param maxlength: the max field size
 *  @param ro: if true, the field is read-only
 *
 *    @return    string
 */
function html_field_date(
    $field_name = '',
    $value = '',
    $ro = false,
    $size = '10',
    $maxlength = '10',
    $form_name = 'artifact_form',
    $today = false,
) {
    if ($ro) {
        $html = $value;
    } else {
        $html = $GLOBALS['HTML']->getDatePicker('field_' . $field_name, $field_name, $value, $size, $maxlength);
    }
    return($html);
}
