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
use Tuleap\Tracker\Admin\GlobalAdmin\Trackers\MarkTrackerAsDeletedController;
use Tuleap\Tracker\Artifact\ActionButtons\MoveArtifactActionAllowedByPluginRetriever;
use Tuleap\Tracker\FormElement\Field\FieldDao;
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
        bindtextdomain('tuleap-tracker_encryption', __DIR__ . '/../site-content');
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

    public function getDependencies()
    {
        return array('tracker');
    }

    public function getServiceShortname()
    {
        return 'plugin_tracker_encryption';
    }

    #[\Tuleap\Plugin\ListeningToEventName(Tracker_FormElementFactory::GET_CLASSNAMES)]
    public function trackerFormelementGetClassnames($params): void
    {
        $request = HTTPRequest::instance();
        $params['fields'][Tracker_FormElement_Field_Encrypted::TYPE] = Tracker_FormElement_Field_Encrypted::class;
        if ($request->get('func') === 'admin-formElements') {
            $GLOBALS['Response']->addFeedback('warning', dgettext('tuleap-tracker_encryption', 'Please add a public key in the tracker administration in order to use encrypted field.'));
        }
    }

    #[\Tuleap\Plugin\ListeningToEventName('fill_project_history_sub_events')]
    public function fillProjectHistorySubEvents($params): void
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
                dgettext('tuleap-tracker', 'Tracker successfully updated.')
            );
        } else {
            $GLOBALS['Response']->addFeedback(
                'error',
                dgettext('tuleap-tracker_encryption', 'Public key is not a valid one.')
            );
        }
    }

    #[\Tuleap\Plugin\ListeningToEventName(MarkTrackerAsDeletedController::TRACKER_EVENT_DELETE_TRACKER)]
    public function trackerEventDeleteTracker($params): void
    {
        $dao_pub_key = new TrackerPublicKeyDao();
        $value_dao   = new ValueDao();
        $tracker_key = new Tracker_Key($dao_pub_key, $value_dao, $params['tracker_id'], $params['key']);
        $tracker_key->deleteTrackerKey($params['tracker_id']);
    }

    #[\Tuleap\Plugin\ListeningToEventName(Tracker::TRACKER_EVENT_FETCH_ADMIN_BUTTONS)]
    public function trackerEventFetchAdminButtons($params): void
    {
        $params['items']['Encryption'] = array(
                    'url'         => '/plugins/tracker_encryption/?'. http_build_query(array(
                                                                                     'tracker' => $params['tracker_id'],
                                                                                     'func' => 'admin-encryption')),
                    'short_title' => dgettext('tuleap-tracker_encryption', 'Tracker Encryption'),
                    'title'       => dgettext('tuleap-tracker_encryption', 'Tracker Encryption'),
                    'description' => dgettext('tuleap-tracker_encryption', 'Encrypt tracker datas.'),
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
                    $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-tracker', 'Access denied. You don\'t have permissions to perform this action.'));
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
                    $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-tracker', 'Access denied. You don\'t have permissions to perform this action.'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='.$tracker_id);
                }
                break;
        }
    }

    private function displayTrackerKeyForm($tracker_id)
    {
        $tracker = TrackerFactory::instance()->getTrackerById($tracker_id);
        $title = dgettext('tuleap-tracker_encryption', 'Tracker Encryption');
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

    #[\Tuleap\Plugin\ListeningToEventName('javascript_file')]
    public function javascriptFile($params): void
    {
        if ($this->currentRequestIsForPlugin() || strpos($_SERVER['REQUEST_URI'], 'plugins/tracker') == true) {
            $layout = $params['layout'];
            assert($layout instanceof \Tuleap\Layout\BaseLayout);
            $layout->addJavascriptAsset(new \Tuleap\Layout\JavascriptAsset($this->getAssets(), 'tracker_encryption.js'));
        }
    }

    #[\Tuleap\Plugin\ListeningToEventName('cssfile')]
    public function cssfile($params): void
    {
        if (strpos($_SERVER['REQUEST_URI'], '/plugins/tracker') === 0) {
            echo '<link rel="stylesheet" type="text/css" href="' . $this->getAssets()->getFileURL('style.css') . '" />';
        }
    }

    private function getAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../frontend-assets',
            '/assets/tracker_encryption/'
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function moveArtifactActionAllowedByPluginRetriever(MoveArtifactActionAllowedByPluginRetriever $event): void
    {
        $tracker_encrypted_fields = Tracker_FormElementFactory::instance()->getFormElementsByType(
            $event->getTracker(),
            [Tracker_FormElement_Field_Encrypted::TYPE],
            true
        );
        if (empty($tracker_encrypted_fields)) {
            return;
        }

        $event->setCanNotBeMoveDueToExternalPlugin(
            dgettext(
                'tuleap-tracker_encryption',
                'This artifact cannot be moved because the tracker uses encrypted fields.'
            )
        );
    }
}
