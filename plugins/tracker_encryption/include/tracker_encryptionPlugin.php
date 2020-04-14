<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2016. All Rights Reserved.
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

use Tuleap\Layout\IncludeAssets;
use Tuleap\Plugin\PluginWithLegacyInternalRouting;
use Tuleap\TrackerEncryption\Dao\ValueDao;

require_once __DIR__ . '/../../tracker/include/trackerPlugin.php';
require_once __DIR__ . '/../vendor/autoload.php';

//phpcs:ignoreFile
class tracker_encryptionPlugin extends PluginWithLegacyInternalRouting
{
    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
    }

    public function getHooksAndCallbacks()
    {
        if (defined('TRACKER_BASE_URL')) {
            $this->addHook(TRACKER_EVENT_DELETE_TRACKER);
            $this->addHook(Tracker_FormElementFactory::GET_CLASSNAMES);
            $this->addHook(TRACKER_EVENT_FETCH_ADMIN_BUTTONS);
        }

        $this->addHook('fill_project_history_sub_events');
        $this->addHook('javascript_file');
        $this->addHook('cssfile');
        $this->listenToCollectRouteEventWithDefaultController();

        return parent::getHooksAndCallbacks();
    }

    /**
     * @return Tuleap\TrackerEncryption\Plugin\PluginInfo
     */
    public function getPluginInfo()
    {
        if (!$this->pluginInfo) {
            $this->pluginInfo = new Tuleap\TrackerEncryption\Plugin\PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    /**
     * @see Plugin::getDependencies()
     */
    public function getDependencies()
    {
        return array('tracker');
    }

    public function getServiceShortname()
    {
        return 'plugin_tracker_encryption';
    }

    public function tracker_formelement_get_classnames($params)
    {
        $request = HTTPRequest::instance();
        $params['fields']['Encrypted'] = "Tracker_FormElement_Field_Encrypted";
        if ($request->get('func') === 'admin-formElements') {
            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('plugin_tracker_encryption', 'warning_missing_key'));
        }
    }

    public function fill_project_history_sub_events($params)
    {
        array_push($params['subEvents']['event_others'], 'Tracker_key');
    }

    public function tracker_encryption_add_key($params)
    {
        $logger      = BackendLogger::getDefaultLogger();
        $dao_pub_key = new TrackerPublicKeyDao();
        $value_dao   = new ValueDao();
        $tracker_key = new Tracker_Key($dao_pub_key, $value_dao, $params['tracker_id'], $params['key']);
        if ($params['key'] == "" || $tracker_key->isValidPublicKey($params['key'])) {
            $tracker_key->associateKeyToTracker();
            $tracker = TrackerFactory::instance()->getTrackerById($params['tracker_id']);
            if ($tracker !== null) {
                $tracker_key->historizeKey($tracker->getGroupId());
            }
            $tracker_key->resetEncryptedFieldValues($params['tracker_id']);
            $logger->info(
                "[Tracker Encryption] A new public key has been set for the tracker[" . $params['tracker_id'] . "]."
            );
            $GLOBALS['Response']->addFeedback(
                'info',
                $GLOBALS['Language']->getText('plugin_tracker_admin', 'successfully_updated')
            );
        } else {
            $GLOBALS['Response']->addFeedback(
                'error',
                $GLOBALS['Language']->getText('plugin_tracker_encryption', 'public_key_error')
            );
        }
    }

    public function tracker_event_delete_tracker($params)
    {
        $dao_pub_key = new TrackerPublicKeyDao();
        $value_dao   = new ValueDao();
        $tracker_key = new Tracker_Key($dao_pub_key, $value_dao, $params['tracker_id'], $params['key']);
        $tracker_key->deleteTrackerKey($params['tracker_id']);
    }

    public function tracker_event_fetch_admin_buttons($params)
    {
        $params['items']['Encryption'] = array(
                    'url'         => '/plugins/tracker_encryption/?'. http_build_query(array(
                                                                                     'tracker' => $params['tracker_id'],
                                                                                     'func' => 'admin-encryption')),
                    'short_title' => $GLOBALS['Language']->getText('plugin_tracker_encryption', 'descriptor_name'),
                    'title'       => $GLOBALS['Language']->getText('plugin_tracker_encryption', 'descriptor_name'),
                    'description' => $GLOBALS['Language']->getText('plugin_tracker_encryption', 'descriptor_description'),
                    );
    }

    public function process() : void
    {
        $request = HTTPRequest::instance();
        $func       = $request->get('func');
        $tracker_id = $request->get('tracker');
        $tracker = TrackerFactory::instance()->getTrackerById($tracker_id);
        switch ($func) {
            case 'admin-encryption':
                if ($tracker !== null && $tracker->userIsAdmin()) {
                    $this->displayTrackerKeyForm($tracker_id);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='.$tracker_id);
                }
                break;
            case 'admin-editencryptionkey':
                if ($tracker !== null && $tracker->userIsAdmin()) {
                    $key = trim($request->getValidated('key', 'text', ''));
                    $csrf_token = new CSRFSynchronizerToken('/plugins/tracker_encryption/?tracker='.$tracker_id.'&func=admin-editencryptionkey');
                    $csrf_token->check();
                    $this->editTrackerKey($tracker_id, $key);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='.$tracker_id);
                }
                break;
        }
    }

    private function displayTrackerKeyForm($tracker_id)
    {
        $tracker = TrackerFactory::instance()->getTrackerById($tracker_id);
        $title = $GLOBALS['Language']->getText('plugin_tracker_encryption', 'descriptor_name');
        $layout = new TrackerManager();
        if ($tracker !== null) {
            $tracker->displayAdminHeader($layout, 'Encryption', $title);
        }
        $csrf_token = new CSRFSynchronizerToken('/plugins/tracker_encryption/?tracker='.$tracker_id.'&func=admin-editencryptionkey');
        $renderer   = TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates/');
        $renderer->renderToPage(
            'tracker-key-settings',
            new Tracker_EncryptionKeySettings_Presenter($tracker_id, '/plugins/tracker_encryption/?tracker='. (int)$tracker_id.'&func=admin-editencryptionkey', $csrf_token)
        );
        $GLOBALS['HTML']->footer(array());
    }

    private function editTrackerKey($tracker_id, $key)
    {
        $params = array("tracker_id" => $tracker_id, "key" => $key);
        $this->tracker_encryption_add_key($params);
        $this->displayTrackerKeyForm($tracker_id);
    }

    public function javascript_file($params): void
    {
        if ($this->currentRequestIsForPlugin() || strpos($_SERVER['REQUEST_URI'], 'plugins/tracker') == true) {
            echo $this->getAssets()->getHTMLSnippet('tracker_encryption.js');
        }
    }

    public function cssFile($params): void
    {
        if (strpos($_SERVER['REQUEST_URI'], '/plugins/tracker') === 0) {
            echo '<link rel="stylesheet" type="text/css" href="' . $this->getAssets()->getFileURL('style.css') . '" />';
        }
    }

    private function getAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../../../src/www/assets/tracker_encryption',
            '/assets/tracker_encryption/'
        );
    }
}
