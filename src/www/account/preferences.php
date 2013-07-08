<?php

require_once('pre.php');
require_once('common/event/EventManager.class.php');
require_once('www/my/my_utils.php');
require_once('common/include/CSRFSynchronizerToken.class.php');
require_once('common/mail/MailManager.class.php');

header("Cache-Control: no-store, no-cache, must-revalidate");

session_require(array('isloggedin'=>'1'));

$em = EventManager::instance();

my_header(array('title'=>$Language->getText('account_options', 'preferences')));

$user = UserManager::instance()->getCurrentUser();

// ############################# Preferences
echo '<h3>'. $Language->getText('account_options', 'preferences') .'</h3>';
?>
<FORM action="updateprefs.php" method="post">
<?php 
    $csrf = new CSRFSynchronizerToken('/account/preferences.php');
    echo $csrf->fetchHTMLInput();
?>
<table>
    <tr><td width="50%"></td><td></td></tr>
    <tr valign="top">
        <td>
            <fieldset>
                <legend><?php echo $Language->getText('account_preferences', 'email_settings'); ?></legend>
<p>
  <INPUT type="checkbox" name="form_mail_site" value="1" <?= $user->getMailSiteUpdates() ? 'checked="checked"' : '' ?> />
  <?= $Language->getText('account_register', 'siteupdate'); ?>
</p>

<p>
  <INPUT type="checkbox" name="form_mail_va" value="1"   <?= $user->getMailVA() ? 'checked="checked"' : '' ?> />
  <?= $Language->getText('account_register', 'communitymail'); ?>
</p>

<p>

<?php echo $Language->getText('account_preferences','tracker_mail_format'); ?>

<select name="<?= Codendi_Mail_Interface::PREF_FORMAT ?>">

<?php
$mailManager = new MailManager();
$u_trackermailformat = $mailManager->getMailPreferencesByUser($user);
foreach ($mailManager->getAllMailFormats() as $format) {
    print '<option value="'.$format.'"';
    if ($u_trackermailformat == $format) {
        print ' selected="selected"';
    }
    print '>'.$format.'</option>\n';
}
print "</select>\n";
?>
</p>

            </fieldset>
            <fieldset>
                <legend><?php echo $Language->getText('account_preferences', 'session'); ?></legend>
<p>
  <input type="checkbox"  name="form_sticky_login" value="1" <?= $user->getStickyLogin() ? 'checked="checked"' : '' ?> />
  <?= $Language->getText('account_options', 'remember_me', $GLOBALS['sys_name']) ?>
</p>
            </fieldset>
            <fieldset id="account_preferences_lab_features">
              <legend><?= $Language->getText('account_preferences', 'lab_features_title',  array($GLOBALS['sys_name']))?></legend>
              <p><?= $Language->getText('account_preferences', 'lab_features_description', array($GLOBALS['sys_name'])) ?></p>
              <p>
                <input type="checkbox" name="form_lab_features" id="form_lab_features" value="1" <?= $user->useLabFeatures() ? 'checked="checked"' : '' ?> />
                <label for="form_lab_features"><?= $Language->getText('account_preferences', 'lab_features_cblabel', $GLOBALS['sys_name']) ?></label>
              </p>
              <?php 
                  $labs = array();
                  $em->processEvent(Event::LAB_FEATURES_DEFINITION_LIST, array('lab_features' => &$labs));
                  if ($labs) {
                      echo '<table>';
                      foreach ($labs as $lab) {
                          if (isset($lab['image'])) {
                              $image = '<img src="'. $lab['image'] .'" width="150" height="92" />';
                          } else {
                              $image = $GLOBALS['HTML']->getImage('lab_features_default.png');
                          }
                          echo '<tr>';
                          echo '<td>'. $image. '</td>';
                          echo '<td>';
                          echo '<p class="account_preferences_lab_feature_title">'. $lab['title'] .'</p>';
                          echo '<p class="account_preferences_lab_feature_description">'. $lab['description'] .'</p>';
                          echo '</td>';
                          echo '</tr>';
                      }
                      echo '</table>';
                  }
              ?>
            </fieldset>
        </td>
        <td>
            <fieldset>
                <legend><?php echo $Language->getText('account_preferences', 'appearance'); ?></legend>
                <table>
                    <tr>
                        <td>

<?php echo $Language->getText('account_options', 'theme'); ?>: </td><td>
<?php
// see what current user them is
if ($user->getTheme() == "" || $user->getTheme() == "default") {
    $user_theme = $GLOBALS['sys_themedefault'];
} else {
    $user_theme = $user->getTheme();
}

// $theme_list is defined in /www/include/utils.php
print '<select name="user_theme">'."\n";
$theme_list = util_get_theme_list();
natcasesort($theme_list); //Sort an array using a case insensitive "natural order" algorithm
while (list(,$theme) = each($theme_list)) {
    print '<option value="'.$theme.'"';
    if ($theme==$user_theme){ print ' selected'; }
    print '>'.$theme;
    if ($theme==$GLOBALS['sys_themedefault']){ print ' ('.$Language->getText('global', 'default').')'; }
    print "</option>\n";
}
print "</select>\n";

?>

</td></tr>
<?php
$font_vals  = array(
    FONT_SIZE_BROWSER,
    FONT_SIZE_SMALL,
    FONT_SIZE_NORMAL,
    FONT_SIZE_LARGE
);
$font_texts = array(
    $Language->getText('account_options', 'font_size_browser'),
    $Language->getText('account_options', 'font_size_small'),
    $Language->getText('account_options', 'font_size_normal'),
    $Language->getText('account_options', 'font_size_large')
);
echo '<tr><td>'.$Language->getText('account_options', 'font_size').': </td>
          <td>'.html_build_select_box_from_arrays($font_vals, $font_texts, "user_fontsize", $user->getFontSize(), false).'</td>
      </tr>';
?>
                    <tr>
                        <td>
<?php echo $Language->getText('account_options', 'language'); ?>: </td><td>
<?php
// display supported languages
echo html_get_language_popup($Language,'language_id',UserManager::instance()->getCurrentUser()->getLocale());
?>
                    </tr>
                   <tr>
                        <td>
 	  	 <?php echo $Language->getText('account_options', 'username_display').':'; ?>
 	  	 </TD><TD>
 	  	 <?php
 	  	 // build the username_display select-box
 	  	 print '<select name="username_display">'."\n";
 	  	 $u_display = user_get_preference("username_display");
 	  	 print '<option value="'.UserHelper::PREFERENCES_NAME_AND_LOGIN.'"';
 	  	 if ($u_display == UserHelper::PREFERENCES_NAME_AND_LOGIN) {
 	  	     print ' selected="selected"';
 	  	 }
                 print '>'.$Language->getText('account_options','codendi_name_and_login').'</option>';
 	  	 print '<option value="'.UserHelper::PREFERENCES_LOGIN_AND_NAME.'"';
 	  	 if ($u_display == UserHelper::PREFERENCES_LOGIN_AND_NAME) {
 	  	     print ' selected="selected"';
 	  	 }
                 print '>'.$Language->getText('account_options','codendi_login_and_name').'</option>';
 	  	 print '<option value="'.UserHelper::PREFERENCES_LOGIN.'"';
 	  	 if ($u_display == UserHelper::PREFERENCES_LOGIN) {
 	  	     print ' selected="selected"';
 	  	 }
                 print '>'.$Language->getText('account_options','codendi_login').'</option>';
 	  	 print '<option value="'.UserHelper::PREFERENCES_REAL_NAME.'"';
 	  	 if ($u_display == UserHelper::PREFERENCES_REAL_NAME) {
 	  	     print ' selected="selected"';
 	  	 }
                 print '>'.$Language->getText('account_options','real_name').'</option>';
                 print '</select>';
 	  	 ?>
                    </tr>
                <?php
                $plugins_prefs = array();
                $em = EventManager::instance();
                $em->processEvent('user_preferences_appearance', array('preferences' => &$plugins_prefs));
                if (is_array($plugins_prefs)) {
                    foreach($plugins_prefs as $pref) {
                        echo '<tr><td>'. $pref['name'] .'</td><td>'. $pref['value'] .'</td></tr>';
                    }
                }
                ?>
                </table>
            </fieldset>
            <fieldset>
                <legend><?php echo $Language->getText('account_preferences', 'import_export'); ?></legend>
                 <table>
                  <tr>
                   <td>
<?php echo $Language->getText('account_options', 'csv_separator').' '.help_button('AccountMaintenance'); ?>:
                   </td>
                   <td>
<?php
if ($u_separator = user_get_preference("user_csv_separator")) {
} else {
    $u_separator = DEFAULT_CSV_SEPARATOR;
}
// build the CSV separator select box
print '<select name="user_csv_separator">'."\n";
// $csv_separators is defined in /www/include/utils.php
foreach ($csv_separators as $separator) {
    print '<option value="'.$separator.'"';
    if ($u_separator == $separator) {
        print ' selected="selected"';
    }
    print '>'.$Language->getText('account_options', $separator).'</option>\n';
}
print "</select>\n";
?>
                   </td>
                  </tr>
                  <tr>
                   <td>
<?php echo $Language->getText('account_preferences', 'csv_dateformat').' '.help_button('AccountMaintenance'); ?>:
                   </td>
                   <td>
<?php
if ($u_dateformat = user_get_preference("user_csv_dateformat")) {
} else {
    $u_dateformat = DEFAULT_CSV_DATEFORMAT;
}
// build the CSV date format select box
print '<select name="user_csv_dateformat">'."\n";
// $csv_dateformats is defined in /www/include/utils.php
foreach ($csv_dateformats as $dateformat) {
    print '<option value="'.$dateformat.'"';
    if ($u_dateformat == $dateformat) {
        print ' selected="selected"';
    }
    print '>'.$Language->getText('account_preferences', $dateformat).'</option>\n';
}
print "</select>\n";
?>
                  </td>
                 </tr>
                </table>
            </fieldset>
        </td>
    </tr>
</table>
<P align=center><CENTER><INPUT type="submit" name="Submit" value="<?php echo $Language->getText('global', 'btn_submit'); ?>"></CENTER>
</FORM>
<?php 
$HTML->footer(array());
?>
