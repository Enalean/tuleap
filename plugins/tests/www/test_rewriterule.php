<?php
error_reporting(E_ALL);
require(getenv('CODENDI_LOCAL_INC')?getenv('CODENDI_LOCAL_INC'):'/etc/codendi/conf/local.inc');

// {{{ workaround for 'Invalid cross-device link' error
@unlink($sys_custom_themeroot . '/pass.png');
@unlink($sys_custom_themeroot . '/fail.png');
copy(dirname(__FILE__) .'/pass.png', $sys_custom_themeroot . '/pass.png');
copy(dirname(__FILE__) .'/fail.png', $sys_custom_themeroot . '/fail.png');

function get_path_image($index, $image) {
    if ($index > 1) {
        return dirname(__FILE__) .'/'. $image;
    }
    return $GLOBALS['sys_custom_themeroot'] .'/'. $image;
}
// }}}

$urls = array(
    '/custom/Tuleap/images/',    // A
    '/custom/common/images/',    // B
    '/themes/Tuleap/images/',    // C
    '/themes/common/images/',    // D
);

$paths = array(
    $sys_custom_themeroot .'/Tuleap/images/',    // 1
    $sys_custom_themeroot .'/common/images/',    // 2
    $sys_themeroot .'/Tuleap/images/',           // 3
    $sys_themeroot .'/common/images/',           // 4
);

/*
A = /custom/Tuleap/images/
B = /custom/common/images/
...
1 = /etc/.../Tuleap/.../
2 = /etc/.../common/.../
...

The array below shows which file is expected to be return depending on:
 * what is asked
 * what exists on the filesystem.

Example:
 - The user uses the theme Tuleap
 - The user will request for an image /themes/Tuleap/images/org_logo.png 
 => we are in case 'C'
 
 - in /u/s/c/.../themes/common/images/ there is org_logo.png => Main logo available for all themes
 - in /u/s/c/.../themes/Tuleap/images/ there is org_logo.png => It is redefined because Tuleap theme needs a B&W logo
 - in /etc/codendi/themes/common/images/ there is also org_logo.png => The site admin put its enterprise logo, available for all themes
 - in /etc/codendi/themes/Tuleap/images/ there is *NO* org_logo.png (It is forbidden to modify the colors of enterprise logo, say the Comunication Manager)
 => We are in the subcase '2'
 
 ===> the image which will be returned is given by (case, subcase) => (c, 2) => 2
      The user asks /themes/Tuleap/images/org_logo.png and we return /etc/codendi/themes/common/images/org_logo.png
 
*/
$expected = array(
    'A' => array(1 => 1, 2 => 2, 3 => 3, 4 => 4),
    'B' => array(1 => 2, 2 => 2, 3 => 4, 4 => 4),
    'C' => array(1 => 1, 2 => 2, 3 => 3, 4 => 4),
    'D' => array(1 => 2, 2 => 2, 3 => 4, 4 => 4),
);

function get_filename($ku, $kp) {
    // C3.png
    return 'test_rewriterule_'. chr(65 + $ku) . ($kp + 1) .'.png';
}

$create = true;
if (isset($_REQUEST['clean'])) {
    $create = false;
}
// init fake images: create false images which links to pass.png or fail.png
// <view-source> in your browser to see what links to what
if ($create) {
    echo '<!-- '."\n";
} else {
    echo '<pre>';
}
foreach ($urls as $ku => $url) {
    foreach ($paths as $kp => $path) {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        foreach ($paths as $kp2 => $path2) {
            if ($kp2 > $kp) {
                $fail = $path2 . get_filename($ku, $kp);
                @unlink($fail);
                print_r($fail .' => fail.png'."\n");
                if ($create) {
                    symlink(get_path_image($kp2, 'fail.png'), $fail);
                }
            }
        }
        $pass = $paths[$expected[chr(65 + $ku)][$kp + 1] - 1] . get_filename($ku, $kp);
        @unlink($pass);
        print_r($pass .' => pass.png'."\n");
        if ($create) {
            symlink(get_path_image($kp, 'pass.png'), $pass);
        }
    }
}
if ($create) {
    echo ' -->';
} else {
    die('Bye! <em>(or <a href="test_rewriterule.php">run the tests</a> again)</em>');;
}

// display the matrix to see the status in one glance
echo '<table>';
foreach ($urls as $ku => $url) {
    echo '<tr>';
    foreach ($paths as $kp => $path) {
        $filename = get_filename($ku, $kp);
        echo '<td>';
        echo '<img src="'. $url . $filename .'" />';
        echo '</td>';
    }
    echo '</tr>';
}
echo '</table>';

// Allow one to clean up the file system
echo '<p>Are you done? Please <a href="test_rewriterule.php?clean=1">clean up</a> before leaving.</p>';
?>
