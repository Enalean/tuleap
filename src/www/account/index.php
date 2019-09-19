<?php
/**
 * Copyright (c) Enalean, 2014-2018. All rights reserved
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

use Tuleap\Cryptography\KeyFactory;
use Tuleap\User\AccessKey\AccessKeyDAO;
use Tuleap\User\AccessKey\AccessKeyMetadataPresenter;
use Tuleap\User\AccessKey\AccessKeyMetadataRetriever;
use Tuleap\User\AccessKey\LastAccessKeyIdentifierStore;

require_once __DIR__ . '/../include/pre.php';

session_require(array('isloggedin'=>'1'));

$em = EventManager::instance();
$um = UserManager::instance();

$user = $um->getCurrentUser();

$third_paty_html     = '';
$can_change_password = true;
$can_change_realname = true;
$can_change_email    = true;
$extra_user_info     = array();
$ssh_keys_extra_html = '';

$em->processEvent(
    Event::MANAGE_THIRD_PARTY_APPS,
    array(
        'user' => $user,
        'html' => &$third_paty_html
    )
);

$em->processEvent(
    'display_change_password',
    array(
        'allow' => &$can_change_password
    )
);

$em->processEvent(
    'display_change_realname',
    array(
        'allow' => &$can_change_realname
    )
);

$em->processEvent(
    'display_change_email',
    array(
        'allow' => &$can_change_email
    )
);

$em->processEvent(
    'account_pi_entry',
    array(
        'user'      => $user,
        'user_info' => &$extra_user_info,
    )
);

$em->processEvent(
    Event::LIST_SSH_KEYS,
    array(
        'user' => $user,
        'html' => &$ssh_keys_extra_html
    )
);

$csrf = new CSRFSynchronizerToken('/account/index.php');
$mail_manager = new MailManager();
$tracker_formats = array();

foreach ($mail_manager->getAllMailFormats() as $format) {
    $tracker_formats[] = array(
        'format'      => $format,
        'is_selected' => $format === $mail_manager->getMailPreferencesByUser($user)
    );
}

$languages_html = array();
$language_factory = new BaseLanguageFactory();
foreach ($language_factory->getAvailableLanguages() as $code => $lang) {
    $languages_html[] = array(
        'lang'        => $lang,
        'code'        => $code,
        'is_selected' => $user->getLocale() === $code
    );
}

$user_helper_preferences = array(
    array(
        'preference_name'  => UserHelper::PREFERENCES_NAME_AND_LOGIN,
        'preference_label' => $Language->getText('account_options', 'tuleap_name_and_login'),
        'is_selected'      => (int) user_get_preference("username_display") === UserHelper::PREFERENCES_NAME_AND_LOGIN
    ),
    array(
        'preference_name'  => UserHelper::PREFERENCES_LOGIN_AND_NAME,
        'preference_label' => $Language->getText('account_options', 'tuleap_login_and_name'),
        'is_selected'      => (int) user_get_preference("username_display") === UserHelper::PREFERENCES_LOGIN_AND_NAME
    ),
    array(
        'preference_name'  => UserHelper::PREFERENCES_LOGIN,
        'preference_label' => $Language->getText('account_options', 'tuleap_login'),
        'is_selected'      => (int) user_get_preference("username_display") === UserHelper::PREFERENCES_LOGIN
    ),
    array(
        'preference_name'  => UserHelper::PREFERENCES_REAL_NAME,
        'preference_label' => $Language->getText('account_options', 'real_name'),
        'is_selected'      => (int) user_get_preference("username_display") === UserHelper::PREFERENCES_REAL_NAME
    )
);

$plugins_prefs = array();
$em->processEvent(
    'user_preferences_appearance',
    array('preferences' => &$plugins_prefs)
);

$all_csv_separator = array();

foreach (PFUser::$csv_separators as $separator) {
    $all_csv_separator[] = array(
        'separator_name'  => $separator,
        'separator_label' => $Language->getText('account_options', $separator),
        'is_selected'     => $separator === user_get_preference("user_csv_separator")
    );
}

$all_csv_dateformat = array();

foreach (PFUser::$csv_dateformats as $dateformat) {
    $all_csv_dateformat[] = array(
        'dateformat_name'  => $dateformat,
        'dateformat_label' => $Language->getText('account_preferences', $dateformat),
        'is_selected'      => $dateformat === user_get_preference("user_csv_dateformat")
    );
}

$user_access_info = $um->getUserAccessInfo($user);
if (! $user_access_info) {
    $user_access_info = array(
        'last_auth_success' => false,
        'last_auth_failure' => false,
        'nb_auth_failure'   => false,
        'prev_auth_success' => false,
    );
}

$svn_token_handler    = new SVN_TokenHandler(
    new SVN_TokenDao(),
    new RandomNumberGenerator(),
    PasswordHandlerFactory::getPasswordHandler()
);
$svn_token_presenters = array();
foreach ($svn_token_handler->getSVNTokensForUser($user) as $user_svn_token) {
    $svn_token_presenters[] = new SVN_TokenPresenter($user_svn_token);
}

$last_svn_token = '';
if (isset($_SESSION['last_svn_token'])) {
    $last_svn_token = $_SESSION['last_svn_token'];
    unset($_SESSION['last_svn_token']);
}

$user_default_format = user_get_preference('user_edition_default_format');

$access_key_presenters         = [];
$access_key_metadata_retriever = new AccessKeyMetadataRetriever(new AccessKeyDAO());
foreach ($access_key_metadata_retriever->getMetadataByUser($user) as $access_key_metadata) {
    $access_key_presenters[] = new AccessKeyMetadataPresenter($access_key_metadata);
}
$last_access_key_identifier_store = new LastAccessKeyIdentifierStore(
    new \Tuleap\User\AccessKey\AccessKeySerializer(),
    (new KeyFactory)->getEncryptionKey(),
    $_SESSION
);
$last_access_key = $last_access_key_identifier_store->getLastGeneratedAccessKeyIdentifier();

$default_formats = array(
    array(
        'label'    => $Language->getText('account_preferences', 'html_format'),
        'value'    => 'html',
        'selected' => $user_default_format === 'html'
    ),
    array(
        'label'    => $Language->getText('account_preferences', 'text_format'),
        'value'    => 'text',
        'selected' => ($user_default_format === false || $user_default_format === 'text')
    )
);

$presenter = new User_PreferencesPresenter(
    $user,
    $can_change_realname,
    $can_change_email,
    $can_change_password,
    $extra_user_info,
    $user_access_info,
    $ssh_keys_extra_html,
    $svn_token_presenters,
    $access_key_presenters,
    $third_paty_html,
    $csrf,
    $tracker_formats,
    $languages_html,
    $user_helper_preferences,
    $plugins_prefs,
    $all_csv_separator,
    $all_csv_dateformat,
    $last_svn_token,
    $default_formats,
    $last_access_key
);

$HTML->header(array(
    'title'      => $Language->getText('account_options', 'title'),
    'body_class' => array('account-maintenance')
    ));

$renderer = TemplateRendererFactory::build()->getRenderer(dirname(__FILE__).'/../../templates/user');
$renderer->renderToPage('account-maintenance', $presenter);

$HTML->footer(array());
