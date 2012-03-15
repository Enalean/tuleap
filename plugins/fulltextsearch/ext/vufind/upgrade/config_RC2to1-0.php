<?php

/**
 * Till Kinstler, kinstler@gmail.com, 03.11.2009
 * Demian Katz, demian.katz@villanova.edu, 01.07.2010
 *
 * command line arguments:
 *   * path to RC2 installation
 *   * path to 1.0 config.ini file (optional)
 */

// Make sure required parameter is present:
$old_config_path = $argv[1];
if (empty($old_config_path)) {
    die("Usage: {$argv[0]} [RC2 base path] [1.0 config.ini file (optional)]\n");
}

// Build input file paths:
$old_config_input = str_replace(array('//',"\\"), '/', $old_config_path . '/web/conf/config.ini');
$old_facets_input = str_replace(array('//',"\\"), '/', $old_config_path . '/web/conf/facets.ini');
$old_search_input = str_replace(array('//',"\\"), '/', $old_config_path . '/web/conf/searches.ini');
$new_config_input = empty($argv[2]) ? 'web/conf/config.ini' : $argv[2];
$new_facets_input = 'web/conf/facets.ini';
$new_search_input = 'web/conf/searches.ini';

// Check for existence of old config files:
$files = array($old_config_input, $old_facets_input, $old_search_input);
foreach($files as $file) {
    if (!file_exists($file)) {
        die("Error: Cannot open file {$file}.\n");
    }
}

// Check for existence of new config files:
$files = array($new_config_input, $new_facets_input, $new_search_input);
foreach($files as $file) {
    if (!file_exists($file)) {
        die("Error: Cannot open file {$file}.\n" .
            "Please run this script from the root of your VuFind installation.\n");
    }
}

// Display introductory banner:
?>

#### configuration file upgrade ####

This script will upgrade some of your configuration files in web/conf/. It
reads values from your RC2 files and puts them into the new 1.0 versions.
This is an automated process, so the results may require some manual cleanup.

**** PROCESSING FILES... ****
<?php

fix_config_ini($old_config_input, $new_config_input);
fix_facets_ini($old_facets_input, $new_facets_input);
fix_search_ini($old_search_input, $new_search_input);

// Display parting notes now that we are done:
?>

**** DONE PROCESSING. ****

Please check all of the output files (web/conf/*.new) to make sure you are
happy with the results.

Known issues to watch for:

- Disabled settings may get turned back on.  You will have to comment
  them back out again.
- Comments from your RC2 files will be lost and replaced with the new
  default comments from the 1.0 files -- if you have important
  information embedded there, you will need to merge it by hand.
- Boolean "true" will map to "1" and "false" will map to "".  This
  is functionally equivalent, but you may want to adjust the file
  for readability.

When you have made the necessary corrections, just copy the *.new files
over the equivalent *.ini files (i.e. replace config.ini with config.ini.new).
<?php

/* Process the config.ini file:
 */
function fix_config_ini($old_config_input, $new_config_input)
{
    $old_config = parse_ini_file($old_config_input, true);
    $new_config = parse_ini_file($new_config_input, true);
    $new_comments = read_ini_comments($new_config_input);
    $new_config_file = 'web/conf/config.ini.new';

    // override new version's defaults with matching settings from old version:
    foreach($old_config as $section => $subsection) {
        foreach($subsection as $key => $value) {
            $new_config[$section][$key] = $value;
        }
    }

    // patch some values manually -- the [COinS] and [OpenURL] sections have changed:
    if (isset($old_config['COinS']['identifier'])) {
        $new_config['OpenURL']['rfr_id'] = $old_config['COinS']['identifier'];
    }
    unset($new_config['COinS']);
    unset($new_config['OpenURL']['api']);
    if (isset($new_config['OpenURL']['url'])) {
        $new_config['OpenURL']['resolver'] = 
            stristr($new_config['OpenURL']['url'], 'sfx') ? 'sfx' : 'other';
        list($new_config['OpenURL']['url']) = explode('?', $new_config['OpenURL']['url']);
    }

    // save the file
    if (!write_ini_file($new_config, $new_comments, $new_config_file)) {
        die("Error: Problem writing to {$new_config_file}.");
    }

    // report success
    echo "\nInput:  {$old_config_input}\n";
    echo "Output: {$new_config_file}\n";
}

/* Process the facets.ini file:
 */
function fix_facets_ini($old_facets_input, $new_facets_input)
{
    $old_config = parse_ini_file($old_facets_input, true);
    $new_config = parse_ini_file($new_facets_input, true);
    $new_comments = read_ini_comments($new_facets_input);
    $new_config_file = 'web/conf/facets.ini.new';

    // override new version's defaults with matching settings from old version:
    foreach($old_config as $section => $subsection) {
        foreach($subsection as $key => $value) {
            $new_config[$section][$key] = $value;
        }
    }

    // we want to retain the old installation's various facet groups
    // exactly as-is
    $new_config['Results'] = $old_config['Results'];
    $new_config['ResultsTop'] = $old_config['ResultsTop'];
    $new_config['Advanced'] = $old_config['Advanced'];
    $new_config['Author'] = $old_config['Author'];

    // save the file
    if (!write_ini_file($new_config, $new_comments, $new_config_file)) {
        die("Error: Problem writing to {$new_config_file}.");
    }

    // report success
    echo "\nInput:  {$old_facets_input}\n";
    echo "Output: {$new_config_file}\n";
}

/* Process the searches.ini file:
 */
function fix_search_ini($old_search_input, $new_search_input)
{
    $old_config = parse_ini_file($old_search_input, true);
    $new_config = parse_ini_file($new_search_input, true);
    $new_comments = read_ini_comments($new_search_input);
    $new_config_file = 'web/conf/searches.ini.new';

    // override new version's defaults with matching settings from old version:
    foreach($old_config as $section => $subsection) {
        foreach($subsection as $key => $value) {
            $new_config[$section][$key] = $value;
        }
    }

    // we want to retain the old installation's Basic/Advanced search settings
    // exactly as-is
    $new_config['Basic_Searches'] = $old_config['Basic_Searches'];
    $new_config['Advanced_Searches'] = $old_config['Advanced_Searches'];

    // save the file
    if (!write_ini_file($new_config, $new_comments, $new_config_file)) {
        die("Error: Problem writing to {$new_config_file}.");
    }

    // report success
    echo "\nInput:  {$old_search_input}\n";
    echo "Output: {$new_config_file}\n";
}

// support function for write_ini_file -- format a value
function write_ini_file_format_value($e)
{
    if ($e === true) {
        return 'true';
    } else if ($e === false) {
        return 'false';
    } else if ($e == "") {
        return '';
    } else {
        return '"' . $e . '"';
    }
}

// support function for write_ini_file -- format a line
function write_ini_file_format_line($key, $value, $tab = 17)
{
    // Build a tab string so the equals signs line up attractively:
    $tabStr = '';
    for ($i = strlen($key); $i < $tab; $i++) {
        $tabStr .= ' ';
    }
    
    return $key . $tabStr . "= ". write_ini_file_format_value($value);
}

// write an ini file, adapted from http://php.net/manual/function.parse-ini-file.php
function write_ini_file($assoc_arr, $comments, $path) {
    $content = "";
    foreach ($assoc_arr as $key=>$elem) {
        if (isset($comments['sections'][$key]['before'])) {
            $content .= $comments['sections'][$key]['before'];
        }
        $content .= "[".$key."]";
        if (!empty($comments['sections'][$key]['inline'])) {
            $content .= "\t" . $comments['sections'][$key]['inline'];
        }
        $content .= "\n";
        foreach ($elem as $key2=>$elem2) {
            if (isset($comments['sections'][$key]['settings'][$key2])) {
                $settingComments = $comments['sections'][$key]['settings'][$key2];
                $content .= $settingComments['before'];
            } else {
                $settingComments = array();
            }
            if (is_array($elem2)) {
                for ($i = 0; $i < count($elem2); $i++) {
                    $content .= 
                        write_ini_file_format_line($key2 . "[]", $elem2[$i]) . "\n";
                }
            } else {
                $content .= write_ini_file_format_line($key2, $elem2);
            }
            if (!empty($settingComments['inline'])) {
                $content .= "\t" . $settingComments['inline'];
            }
            $content .= "\n";
        }
    }
    
    $content .= $comments['after'];
    
    if (!$handle = fopen($path, 'w')) {
        return false;
    }
    if (!fwrite($handle, $content)) {
        return false;
    }
    fclose($handle);
    return true;
}

/**
 * read_ini_comments
 *
 * Read the specified file and return an associative array of this format
 * containing all comments extracted from the file:
 *
 * array =>
 *   'sections' => array
 *     'section_name_1' => array
 *       'before' => string ("Comments found at the beginning of this section")
 *       'inline' => string ("Comments found at the end of the section's line")
 *       'settings' => array
 *         'setting_name_1' => array
 *           'before' => string ("Comments found before this setting")
 *           'inline' => string ("Comments found at the end of the setting's line")
 *           ...
 *         'setting_name_n' => array (same keys as setting_name_1)
 *        ...
 *      'section_name_n' => array (same keys as section_name_1)
 *   'after' => string ("Comments found at the very end of the file")
 *
 * @param   string  $filename       Name of ini file to read.
 * @return  array                   Associative array as described above.
 */
function read_ini_comments($filename)
{
    $lines = file($filename);
    
    // Initialize our return value:
    $comments = array('sections' => array(), 'after' => '');
    
    // Initialize variables for tracking status during parsing:
    $currentSection = '';
    $currentSetting = '';
    $currentComments = '';
    
    foreach($lines as $line) {
        // To avoid redundant processing, create a trimmed version of the current line:
        $trimmed = trim($line);
        
        // Is the current line a comment?  If so, add to the currentComments string.
        // Note that we treat blank lines as comments.
        if (substr($trimmed, 0, 1) == ';' || empty($trimmed)) {
            $currentComments .= $line;
        // Is the current line the start of a section?  If so, create the appropriate
        // section of the return value:
        } else if (substr($trimmed, 0, 1) == '[' && 
            ($closeBracket = strpos($trimmed, ']')) > 1) {
            $currentSection = substr($trimmed, 1, $closeBracket - 1);
            if (!empty($currentSection)) {
                // Grab comments at the end of the line, if any:
                if (($semicolon = strpos($trimmed, ';')) !== false) {
                    $inline = trim(substr($trimmed, $semicolon));
                } else {
                    $inline = '';
                }
                $comments['sections'][$currentSection] = array(
                    'before' => $currentComments, 
                    'inline' => $inline, 
                    'settings' => array());
                $currentComments = '';
            }
        // Is the current line a setting?  If so, add to the return value:
        } else if (($equals = strpos($trimmed, '=')) !== false) {
            $currentSetting = trim(substr($trimmed, 0, $equals));
            $currentSetting = trim(str_replace('[]', '', $currentSetting));
            if (!empty($currentSection) && !empty($currentSetting)) {
                // Grab comments at the end of the line, if any:
                if (($semicolon = strpos($trimmed, ';')) !== false) {
                    $inline = trim(substr($trimmed, $semicolon));
                } else {
                    $inline = '';
                }
                // Currently, this data structure doesn't support arrays very well,
                // since it can't distinguish which line of the array corresponds
                // with which comments.  For now, we just append all the preceding
                // and inline comments together for arrays.  Since we don't actually
                // use arrays in the config.ini file, this isn't a big concern, but
                // we should improve it if we ever need to.
                if (!isset($comments['sections'][$currentSection]['settings'][$currentSetting])) {
                    $comments['sections'][$currentSection]['settings'][$currentSetting] =
                        array('before' => $currentComments, 'inline' => $inline);
                } else {
                    $comments['sections'][$currentSection]['settings'][$currentSetting]['before'] .= 
                        $currentComments;
                    $comments['sections'][$currentSection]['settings'][$currentSetting]['inline'] .=
                        "\n" . $inline;
                }
                $currentComments = '';
            }
        }
    }
    
    // Store any leftover comments following the last setting:
    $comments['after'] = $currentComments;
    
    return $comments;
}

?>
