<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class User_PreferencesPresenter
{

    /** @var PFUser */
    private $user;
    public $can_change_real_name;
    private $can_change_email;
    private $can_change_password;

    private $extra_user_info;

    /** @var array */
    private $user_access;

    /** string */
    private $third_party_html;

    private $ssh_keys_extra_html;

    /** @var SVN_TokenPresenter[] */
    public $svn_tokens;

    /**
     * @var \Tuleap\User\AccessKey\AccessKeyMetadataPresenter[]
     */
    public $access_keys;

    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;

    public $csrf_input_html;

    /** @var array */
    public $tracker_formats;

    /** @var array */
    public $languages_html;

    /** @var array */
    public $user_helper_preferences;

    /** @var array */
    public $plugins_prefs;

    /** @var array */
    public $all_csv_separator;

    /** @var array */
    public $all_csv_dateformat;

    /** @var string */
    public $last_svn_token;

    /**
     * @var array
     */
    public $default_formats;

    /**
     * @var string
     */
    public $last_access_key;

    /**
     * @var string
     */
    public $last_access_resolution;

    public $user_language;
    public $user_has_accessibility_mode;
    public $is_condensed;
    public $display_density_name;
    public $display_density_condensed;

    public function __construct(
        PFUser $user,
        $can_change_real_name,
        $can_change_email,
        $can_change_password,
        array $extra_user_info,
        array $user_access,
        $ssh_keys_extra_html,
        $svn_tokens,
        $access_keys,
        $third_party_html,
        CSRFSynchronizerToken $csrf_token,
        array $tracker_formats,
        array $languages_html,
        array $user_helper_preferences,
        array $plugins_prefs,
        array $all_csv_separator,
        array $all_csv_dateformat,
        $last_svn_token,
        array $default_formats,
        $last_access_key
    ) {
        $this->user                    = $user;
        $this->can_change_real_name    = $can_change_real_name;
        $this->can_change_email        = $can_change_email;
        $this->can_change_password     = $can_change_password;
        $this->extra_user_info         = $extra_user_info;
        $this->user_access             = $user_access;
        $this->ssh_keys_extra_html     = $ssh_keys_extra_html;
        $this->svn_tokens              = $svn_tokens;
        $this->access_keys             = $access_keys;
        $this->third_party_html        = $third_party_html;
        $this->csrf_token              = $csrf_token;
        $this->csrf_input_html         = $csrf_token->fetchHTMLInput();
        $this->tracker_formats         = $tracker_formats;
        $this->languages_html          = $languages_html;
        $this->user_helper_preferences = $user_helper_preferences;
        $this->plugins_prefs           = $plugins_prefs;
        $this->all_csv_separator       = $all_csv_separator;
        $this->all_csv_dateformat      = $all_csv_dateformat;
        $this->last_svn_token          = $last_svn_token;
        $this->default_formats         = $default_formats;
        $this->last_access_key         = $last_access_key;

        $this->last_access_resolution  = DateHelper::distanceOfTimeInWords(0, ForgeConfig::get('last_access_resolution'));

        $this->user_language               = $user->getShortLocale();
        $this->user_has_accessibility_mode = $user->getPreference(PFUser::ACCESSIBILITY_MODE);

        $this->display_density_name      = PFUser::PREFERENCE_DISPLAY_DENSITY;
        $this->display_density_condensed = PFUser::DISPLAY_DENSITY_CONDENSED;

        $this->is_condensed = $user->getPreference(PFUser::PREFERENCE_DISPLAY_DENSITY) === PFUser::DISPLAY_DENSITY_CONDENSED;
    }

    public function generated_svn_token()
    {
        return $GLOBALS['Language']->getText('account_options', 'generated_svn_token');
    }

    public function avatar()
    {
        return $this->user->fetchHtmlAvatar();
    }

    public function change_real_name()
    {
        return $GLOBALS['Language']->getText('account_options', 'change_real_name');
    }

    public function real_name()
    {
        return $this->user->getRealName();
    }

    public function user_username()
    {
        return $this->user->getUnixName();
    }

    public function welcome_user()
    {
        return $GLOBALS['Language']->getText('account_options', 'welcome') . ' ' . $this->user->getRealName();
    }

    public function user_id_label()
    {
        return $GLOBALS['Language']->getText('account_options', 'user_id');
    }

    public function user_id_value()
    {
        return $this->user->getId();
    }

    public function user_email_label()
    {
        return $GLOBALS['Language']->getText('account_options', 'email_address');
    }

    public function user_email_value()
    {
        return $this->user->getEmail();
    }

    public function can_change_email()
    {
        return $this->can_change_email;
    }

    public function password_label()
    {
        return $GLOBALS['Language']->getText('account_options', 'password_label');
    }

    public function change_email()
    {
        return $GLOBALS['Language']->getText('account_options', 'change_email_address');
    }

    public function change_avatar()
    {
        return $GLOBALS['Language']->getText('account_options', 'change_avatar');
    }

    public function select_avatar()
    {
        return $GLOBALS['Language']->getText('account_options', 'select_avatar');
    }

    public function use_default_avatar()
    {
        return $GLOBALS['Language']->getText('account_options', 'use_default_avatar');
    }

    public function change_avatar_desc()
    {
        return $GLOBALS['Language']->getText('account_options', 'change_avatar_desc');
    }

    public function btn_save_avatar_label()
    {
        return $GLOBALS['Language']->getText('account_options', 'btn_save_avatar_label');
    }

    public function can_change_password()
    {
        return $this->can_change_password;
    }

    public function change_password()
    {
        return $GLOBALS['Language']->getText('account_options', 'change_password');
    }

    public function member_since_label()
    {
        return $GLOBALS['Language']->getText('account_options', 'member_since');
    }

    public function member_since_value()
    {
        return format_date($GLOBALS['Language']->getText('system', 'datefmt'), $this->user->getAddDate());
    }

    public function timezone_label()
    {
        return $GLOBALS['Language']->getText('account_options', 'timezone');
    }

    public function timezone_value()
    {
        return $this->user->getTimezone();
    }

    public function change_timezone()
    {
        return $GLOBALS['Language']->getText('account_options', 'change_timezone');
    }

    public function extra_user_info()
    {
        return $this->extra_user_info;
    }

    public function shell_account_title()
    {
        return $GLOBALS['Language']->getText('account_options', 'shell_account_title');
    }

    public function ssh_keys_count_label()
    {
        return $GLOBALS['Language']->getText('account_options', 'shell_shared_keys');
    }

    public function ssh_keys_count()
    {
        return count($this->user->getAuthorizedKeysArray());
    }

    public function ssh_keys_label()
    {
        return 'Key';
    }

    public function ssh_keys_list()
    {
        $keys = array();
        foreach ($this->user->getAuthorizedKeysArray() as $ssh_key_number => $ssh_key_value) {
            $keys[] = array(
                'ssh_key_ellipsis_value' => substr($ssh_key_value, 0, 40).'...'.substr($ssh_key_value, -40),
                'ssh_key_value'          => $ssh_key_value,
                'ssh_key_number'         => $ssh_key_number
            );
        }
        return $keys;
    }

    public function ssh_keys_extra_html()
    {
        return $this->ssh_keys_extra_html;
    }

    public function authentication_attempts_title()
    {
        return $GLOBALS['Language']->getText('account_options', 'auth_attempt_title');
    }

    public function last_successful_login_label()
    {
        return $GLOBALS['Language']->getText('account_options', 'auth_attempt_last_success');
    }

    public function last_successful_login_value()
    {
        return format_date($GLOBALS['Language']->getText('system', 'datefmt'), $this->user_access['last_auth_success']);
    }

    public function last_login_failure_label()
    {
        return $GLOBALS['Language']->getText('account_options', 'auth_attempt_last_failure');
    }

    public function last_login_failure_value()
    {
        return format_date($GLOBALS['Language']->getText('system', 'datefmt'), $this->user_access['last_auth_failure']);
    }

    public function number_login_failure_label()
    {
        return $GLOBALS['Language']->getText('account_options', 'auth_attempt_nb_failure');
    }

    public function number_login_failure_value()
    {
        return $this->user_access['nb_auth_failure'];
    }

    public function previous_successful_login_label()
    {
        return $GLOBALS['Language']->getText('account_options', 'auth_attempt_prev_success');
    }

    public function previous_successful_login_value()
    {
        return format_date($GLOBALS['Language']->getText('system', 'datefmt'), $this->user_access['prev_auth_success']);
    }

    public function third_party_applications_title()
    {
        return 'Third party applications';
    }

    public function third_party_applications_content()
    {
        return $this->third_party_html;
    }

    public function user_legal()
    {
        ob_start();
        include $GLOBALS['Language']->getContent('account/user_legal');
        return ob_get_clean();
    }

    public function add_ssh_key_button()
    {
        return $GLOBALS['Language']->getText('account_options', 'shell_add_keys');
    }

    public function delete_ssh_key_button()
    {
        return $GLOBALS['Language']->getText('account_options', 'shell_delete_ssh_keys');
    }

    public function has_ssh_key()
    {
        return $this->ssh_keys_count() > 0;
    }

    public function ssh_keys_no_key()
    {
        return $GLOBALS['Language']->getText('account_options', 'ssh_keys_no_key');
    }

    public function has_svn_tokens()
    {
        return count($this->svn_tokens) > 0;
    }

    public function svn_tokens_title()
    {
        return $GLOBALS['Language']->getText('account_options', 'svn_tokens_title');
    }

    public function svn_tokens_help()
    {
        return $GLOBALS['Language']->getText('account_options', 'svn_tokens_help');
    }

    public function svn_tokens_no_token()
    {
        return $GLOBALS['Language']->getText('account_options', 'svn_tokens_no_token');
    }

    public function svn_token_generated_date()
    {
        return $GLOBALS['Language']->getText('account_options', 'svn_token_generated_date');
    }

    public function svn_token_last_usage()
    {
        return $GLOBALS['Language']->getText('account_options', 'svn_token_last_usage');
    }

    public function svn_token_last_ip()
    {
        return $GLOBALS['Language']->getText('account_options', 'svn_token_last_ip');
    }

    public function svn_token_comment()
    {
        return $GLOBALS['Language']->getText('account_options', 'svn_token_comment');
    }

    public function generate_svn_token_button()
    {
        return $GLOBALS['Language']->getText('account_options', 'generate_svn_token_button');
    }

    public function delete_svn_tokens_button()
    {
        return $GLOBALS['Language']->getText('account_options', 'delete_svn_tokens_button');
    }

    public function generate_svn_token_modal_title()
    {
        return $GLOBALS['Language']->getText('account_options', 'generate_svn_token_modal_title');
    }

    public function generate_svn_token_modal_button()
    {
        return $GLOBALS['Language']->getText('account_options', 'generate_svn_token_modal_button');
    }

    public function generate_svn_token_modal_button_comment_label()
    {
        return $GLOBALS['Language']->getText('account_options', 'generate_svn_token_modal_button_comment_label');
    }

    public function generate_svn_token_modal_button_comment_placeholder()
    {
        return $GLOBALS['Language']->getText('account_options', 'generate_svn_token_modal_button_comment_placeholder');
    }

    public function generate_svn_token_modal_button_help()
    {
        return $GLOBALS['Language']->getText('account_options', 'generate_svn_token_modal_button_help');
    }

    public function has_access_keys()
    {
        return count($this->access_keys) > 0;
    }



    /* PREFERENCES */

    public function preference_title()
    {
        return $GLOBALS['Language']->getText('account_options', 'preferences');
    }

    public function email_settings()
    {
        return $GLOBALS['Language']->getText('account_preferences', 'email_settings');
    }

    public function user_has_mail_site_updates()
    {
        return $this->user->getMailSiteUpdates();
    }

    public function user_has_sticky_login()
    {
        return $this->user->getStickyLogin();
    }

    public function user_has_mail_va()
    {
        return $this->user->getMailVA();
    }

    public function site_update_label()
    {
        return $GLOBALS['Language']->getText('account_register', 'siteupdate');
    }

    public function community_mail_label()
    {
        return $GLOBALS['Language']->getText('account_register', 'communitymail');
    }

    public function tracker_mail_format_label()
    {
        return $GLOBALS['Language']->getText('account_preferences', 'tracker_mail_format');
    }

    public function tracker_mail_format_select_name()
    {
        return Codendi_Mail_Interface::PREF_FORMAT;
    }

    public function session_label()
    {
        return $GLOBALS['Language']->getText('account_preferences', 'session');
    }

    public function remember_me()
    {
        return $GLOBALS['Language']->getText('account_options', 'remember_me', $GLOBALS['sys_name']);
    }

    public function lab_features_title()
    {
        return $GLOBALS['Language']->getText('account_preferences', 'lab_features_title', array($GLOBALS['sys_name']));
    }

    public function lab_features_description()
    {
        return $GLOBALS['Language']->getText('account_preferences', 'lab_features_description', array($GLOBALS['sys_name']));
    }

    public function user_uses_lab_features()
    {
        return $this->user->useLabFeatures();
    }

    public function lab_features_checkbox_label()
    {
        return $GLOBALS['Language']->getText('account_preferences', 'lab_features_cblabel', $GLOBALS['sys_name']);
    }

    public function lab_features_default_image()
    {
        return $GLOBALS['HTML']->getImage('lab_features_default.png');
    }

    public function appearance_title()
    {
        return $GLOBALS['Language']->getText('account_preferences', 'appearance');
    }

    public function theme_label()
    {
        return $GLOBALS['Language']->getText('account_options', 'theme');
    }

    public function default_theme()
    {
        return $GLOBALS['Language']->getText('global', 'default');
    }

    public function theme_variant_label()
    {
        return $GLOBALS['Language']->getText('account_options', 'theme_variant');
    }

    public function language_label()
    {
        return $GLOBALS['Language']->getText('account_options', 'language');
    }

    public function username_display_label()
    {
        return $GLOBALS['Language']->getText('account_options', 'username_display');
    }

    public function import_export_title()
    {
        return $GLOBALS['Language']->getText('account_preferences', 'import_export');
    }

    public function csv_separator_label()
    {
        return $GLOBALS['Language']->getText('account_options', 'csv_separator');
    }

    public function csv_dateformat_label()
    {
        return $GLOBALS['Language']->getText('account_preferences', 'csv_dateformat');
    }

    public function preference_save_button()
    {
        return $GLOBALS['Language']->getText('account_preferences', 'save_preferences');
    }

    /* MODAL */

    public function add_keys_modal_title()
    {
        return $GLOBALS['Language']->getText('account_editsshkeys', 'add_keys_title');
    }

    public function btn_close_label()
    {
        return $GLOBALS['Language']->getText('global', 'btn_close');
    }

    public function btn_save_keys_label()
    {
        return $GLOBALS['Language']->getText('account_editsshkeys', 'btn_save_keys');
    }

    public function edition_title()
    {
        return $GLOBALS['Language']->getText('account_preferences', 'edition_title');
    }

    public function default_format_label()
    {
        return $GLOBALS['Language']->getText('account_preferences', 'default_format_label');
    }

    public function default_format_intro()
    {
        return $GLOBALS['Language']->getText('account_preferences', 'default_format_intro');
    }
}
