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
    if (!isset($args['border']) || !$args['border']) {
        $return .= (" border=0");
    }

    // ## if no height AND no width tag, insert em both
    if (
        (!isset($args['height']) || !$args['height']) &&
            (!isset($args['width'])  || !$args['width'])
    ) {
     /* Check to see if we've already fetched the image data */
        if ($img_size) {
            if ((!isset($img_size[$src]) || !$img_size[$src]) && is_file($GLOBALS['sys_urlroot'] . util_get_dir_image_theme() . $src)) {
                $img_size[$src] = @getimagesize($GLOBALS['sys_urlroot'] . util_get_dir_image_theme() . $src);
            }
        } else {
            if (is_file($GLOBALS['sys_urlroot'] . util_get_dir_image_theme() . $src)) {
                $img_size[$src] = @getimagesize($GLOBALS['sys_urlroot'] . util_get_dir_image_theme() . $src);
            }
        }
        $return .= ' width="' . $img_size[$src][0] . '" height="' . $img_size[$src][1] . '"';
    }

    // ## insert alt tag if there isn't one
    if (!isset($args['alt']) || !$args['alt']) {
        $return .= ' alt="' . $purifier->purify($src) . '"';
    }

    $return .= ('>');
    if ($display) {
        print $return;
    } else {
        return $return;
    }
}

function html_get_timezone_popup($selected = 0)
{
    $GLOBALS['HTML']->includeFooterJavascriptFile('/scripts/jstimezonedetect/jstz.min.js');
    $GLOBALS['HTML']->includeFooterJavascriptFile('/scripts/tuleap/timezone.js');
    $renderer = TemplateRendererFactory::build()->getRenderer(ForgeConfig::get('codendi_dir') . '/src/templates/account/');
    return $renderer->renderToString('timezone', new Account_TimezoneSelectorPresenter($selected));
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
    $hp   = Codendi_HTMLPurifier::instance();
    $language_factory = new BaseLanguageFactory();

    $html = '<select name="' . $hp->purify($title) . '">';
    foreach ($language_factory->getAvailableLanguages() as $code => $lang) {
        $select = ($selected == $code) ? 'selected="selected"' : '';
        $html .= '<option value="' .  $hp->purify($code, CODENDI_PURIFIER_CONVERT_HTML)  . '" ' . $select . '>';
        $html .= $hp->purify($lang, CODENDI_PURIFIER_CONVERT_HTML);
        $html .= '</option>';
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
			        <TD class="boxtitle"><a class=sortbutton href="' . $links_arr[$i] . '">' . $purifier->purify($title_arr[$i]) . '</A></TD>';
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
    $show_unknown_value = true
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
        $return .= '<OPTION VALUE="' . $hp->purify($text_unchanged) . '" SELECTED>' . $hp->purify($text_unchanged, $purify_level) . '</OPTION>';
         $isAValueSelected = true;
    }

    //we don't always want the default any  row shown
    if ($show_any) {
        if (is_array($checked_val)) {
            if (in_array(0, $checked_val)) {
                $selected = "SELECTED";
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
                $selected = "SELECTED";
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
            ($vals[$i] == '100' && !$show_100) ||
            ($vals[$i] == '0' && !$show_any)
        ) {
            $return .= '
				<OPTION VALUE="' . $hp->purify($vals[$i]) . '"';
            if (is_array($checked_val)) {
                if (in_array($vals[$i], $checked_val)) {
                    $return .= ' SELECTED';
                    $isAValueSelected = true;
                }
            } else {
                if ($vals[$i] == $checked_val) {
                    $return .= ' SELECTED';
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
    $show_unknown_value = true
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
        $array = array();
        while ($row = db_fetch_array($result)) {
            $array[] = array('value' => $row[0], 'text' => $row[1]);
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

    $checked_count = count($checked_array);
//      echo '-- '.$checked_count.' --';
    $id = str_replace('[]', '', $name);
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
        for ($j = 0; $j < $checked_count; $j++) {
            if ($checked_array[$j] == '0') {
                    $return .= ' SELECTED';
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
        for ($j = 0; $j < $checked_count; $j++) {
            if ($checked_array[$j] == '100') {
                    $return .= ' SELECTED';
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
            for ($j = 0; $j < $checked_count; $j++) {
                if ($val == $checked_array[$j]) {
                    $return .= ' SELECTED';
                }
            }
            $return .= '>' . $hp->purify(($show_value ? $val . '-' : '') . substr($row['text'], 0, 60), $purify_level) . '</OPTION>';
        }
    }
    $return .= '
		</SELECT>';
    return $return;
}

function html_buildpriority_select_box($name = 'priority', $checked_val = '5')
{
    /*
        Return a select box of standard priorities.
        The name of this select box is optional and so is the default checked value
    */
    global $Language;
    $purifier = Codendi_HTMLPurifier::instance();
    ?>
    <SELECT NAME="<?php echo $purifier->purify($name); ?>">
    <OPTION VALUE="1"<?php if ($checked_val == "1") {
        echo " SELECTED";
                     } ?>>1 - <?php echo $Language->getText('include_html', 'lowest'); ?></OPTION>
    <OPTION VALUE="2"<?php if ($checked_val == "2") {
        echo " SELECTED";
                     } ?>>2</OPTION>
    <OPTION VALUE="3"<?php if ($checked_val == "3") {
        echo " SELECTED";
                     } ?>>3</OPTION>
    <OPTION VALUE="4"<?php if ($checked_val == "4") {
        echo " SELECTED";
                     } ?>>4</OPTION>
    <OPTION VALUE="5"<?php if ($checked_val == "5") {
        echo " SELECTED";
                     } ?>>5 - <?php echo $Language->getText('include_html', 'medium'); ?></OPTION>
    <OPTION VALUE="6"<?php if ($checked_val == "6") {
        echo " SELECTED";
                     } ?>>6</OPTION>
    <OPTION VALUE="7"<?php if ($checked_val == "7") {
        echo " SELECTED";
                     } ?>>7</OPTION>
    <OPTION VALUE="8"<?php if ($checked_val == "8") {
        echo " SELECTED";
                     } ?>>8</OPTION>
    <OPTION VALUE="9"<?php if ($checked_val == "9") {
        echo " SELECTED";
                     } ?>>9 - <?php echo $Language->getText('include_html', 'highest'); ?></OPTION>
    </SELECT>
    <?php
}

/*!     @function site_user_header
        @abstract everything required to handle security and
                add navigation for user pages like /my/ and /account/
        @param params array() must contain $user_id
        @result text - echos HTML to the screen directly
*/
function site_header($params)
{
    global $HTML;
    /*
                Check to see if active user
                Check to see if logged in
    */

    if (isset($params['group'])) {
        $pm = ProjectManager::instance();
        $project = $pm->getProject($params['group']);
        if ($project->isTemplate()) {
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
function site_project_header($params)
{
    global $HTML, $Language;

    /*
        Check to see if active
        Check to see if private (if private check if user_ismember)
    */

    $group_id = $params['group'];

    //get the project object
    $pm = ProjectManager::instance();
    $project = $pm->getProject($group_id);

    //group doesn't exist
    if ($project->isError()) {
        exit_error($Language->getText('include_html', 'invalid_g'), $Language->getText('include_html', 'g_not_exist'));
    }

    //group is private
    if (! $project->isPublic() && isset($params['user_has_special_access']) && ! $params['user_has_special_access']) {
     //if its a private group, you must be a member of that group
        session_require(array('group' => $group_id));
    }

    //for dead projects must be member of admin project
    if (!$project->isActive()) {
        HTTPRequest::instance()->checkUserIsSuperUser();
    }

    if (isset($params['pv']) && $params['pv'] != 0) {
        // Printer version: no right column, no tabs...
        echo $HTML->pv_header($params);
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
    global $HTML;

    if (isset($params['pv']) && $params['pv'] != 0) {
        // Printer version
        echo $HTML->pv_footer($params);
    } else {
        echo html_feedback_bottom($GLOBALS['feedback']);
        echo $HTML->footer($params);
    }
}


function html_display_boolean($value, $true_value = 'Yes', $false_value = 'No')
{
    global $Language;

    // Position default values for special menu items
    if (!isset($true_value)) {
        $true_value = $Language->getText('global', 'yes');
    }
    if (!isset($false_value)) {
        $false_value = $Language->getText('global', 'no');
    }
    if (($value == 1) || ($value == true)) {
        echo $true_value;
    } else {
        echo $false_value;
    }
}

function html_trash_image($alt)
{
    $purifier = Codendi_HTMLPurifier::instance();
    return '<img src="' . util_get_image_theme("ic/trash.png") . '" ' .
        'height="16" width="16" border="0" alt="' . $purifier->purify($alt) . '" title="' . $purifier->purify($alt) . '">';
}

function html_trash_link($link, $warn, $alt)
{
    $purifier = Codendi_HTMLPurifier::instance();
    return '<a href="' . $link . '" onClick="return confirm(\'' . $purifier->purify($warn, CODENDI_PURIFIER_JS_QUOTE) . '\')">' . html_trash_image($alt) . '</a>';
}

/**
 * @deprecated
 */
function html_trash_link_fontawesome($link, $warn)
{
    $purifier = Codendi_HTMLPurifier::instance();
    return '<a href="' . $link . '" onClick="return confirm(\'' . $purifier->purify($warn, CODENDI_PURIFIER_JS_QUOTE) . '\')"><i class="fa fa-trash-o"></i></a>';
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
    $today = false
) {
    if ($ro) {
        $html = $value;
    } else {
        $html = $GLOBALS['HTML']->getDatePicker('field_' . $field_name, $field_name, $value, $size, $maxlength);
    }
    return($html);
}

function html_time_ago($time, $include_seconds = false)
{
    return DateHelper::timeAgoInWords($time, $include_seconds, true);
}

?>
