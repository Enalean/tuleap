<?php
/**
 * Copyright (c) Enalean, 2013-Present. All rights reserved
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2006
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Docman\Notifications\CollectionOfUgroupMonitoredItemsBuilder;
use Tuleap\Docman\Notifications\NotificationListPresenter;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Docman_View_ItemDetailsSectionNotifications extends Docman_View_ItemDetailsSection
{
    /**
     * @var Docman_NotificationsManager
     */
    public $notificationsManager;
    public $token;

    /**
     * @var Tuleap\Docman\Notifications\CollectionOfUgroupMonitoredItemsBuilder
     */
    private $ugroups_to_be_notified_builder;

    public function __construct(
        $item,
        $url,
        $notificationsManager,
        $token,
        CollectionOfUgroupMonitoredItemsBuilder $ugroups_to_be_notified_builder,
    ) {
        parent::__construct(
            $item,
            $url,
            'notifications',
            dgettext('tuleap-docman', 'Notifications')
        );
        $this->notificationsManager           = $notificationsManager;
        $this->token                          = $token;
        $this->ugroups_to_be_notified_builder = $ugroups_to_be_notified_builder;
    }

    public function getContent($params = [])
    {
        $content  = '<dl><fieldset><legend>' . dgettext('tuleap-docman', 'Notifications') . '</legend>';
        $content .= '<dd>';
        $content .= '<form action="" method="POST">';
        $content .= '<p>';
        if ($this->token) {
            $content .= '<input type="hidden" name="token" value="' . $this->token . '" />';
        }
        $content .= '<input type="hidden" name="action" value="monitor" />';
        $content .= '<input type="hidden" name="id" value="' . $this->item->getId() . '" />';
        $um       = UserManager::instance();
        $user     = $um->getCurrentUser();
        $checked  = ! $user->isAnonymous() && $this->notificationsManager->userExists($user->getId(), $this->item->getId()) ? 'checked="checked"' : '';
        $disabled = $user->isAnonymous() ? 'disabled="disabled"' : '';
        $content .= '<input type="hidden" name="monitor" value="0" />';
        $content .= '<label class="checkbox" for="plugin_docman_monitor_item" data-test="notify-me-checkbox">';
        $content .= '<input type="checkbox" name="monitor" value="1" id="plugin_docman_monitor_item" data-test="notify-me-hierarchy-checkbox" ' . $checked . ' ' . $disabled . ' />' . dgettext('tuleap-docman', 'Send me an email whenever this item is updated.');
        $content .= '</label></p>';
        $content .= $this->item->accept($this, ['user' => &$user]);
        $content .= '<p><input type="submit" data-test="submit-notification-button" value="' . $GLOBALS['Language']->getText('global', 'btn_submit') . '" /></p>';
        $content .= '</form>';
        $content .= '</dd></fieldset></dl>';
        $content .= '<dl>' . $this->displayListeningUsers($this->item->getId()) . '</dl>';
        return $content;
    }

    /**
     * Show list of people monitoring the document directly or indirectly by monitoring one of the parents and its subitems
     *
     * @param int $itemId Id of the document
     *
     * @return String
     */
    private function displayListeningUsers($itemId)
    {
        $dpm      = Docman_PermissionsManager::instance($this->item->getGroupId());
        $um       = UserManager::instance();
        $purifier = Codendi_HTMLPurifier::instance();
        $content  = '';
        if ($dpm->userCanManage($um->getCurrentUser(), $itemId)) {
            $users   = $this->notificationsManager->getListeningUsers($this->item);
            $ugroups = $this->ugroups_to_be_notified_builder->getCollectionOfUgroupMonitoredItems($this->item);

            $content .= '<fieldset><legend>' . $purifier->purify(dgettext('tuleap-docman', 'Subscribers')) . '</legend>';

            $renderer = TemplateRendererFactory::build()->getRenderer(
                dirname(PLUGIN_DOCMAN_BASE_DIR) . '/templates'
            );
            $content .= $renderer->renderToString(
                'item-details-notifications',
                new NotificationListPresenter($users, $ugroups, $this->item)
            );

            $content .= '</fieldset>';
            $assets   = new \Tuleap\Layout\IncludeAssets(__DIR__ . '/../../frontend-assets', '/assets/docman');
            $GLOBALS['Response']->includeFooterJavascriptFile($assets->getFileURL('notifications.js'));
        }
        return $content;
    }

    public function visitEmpty(&$item, $params)
    {
        return $this->visitDocument($item, $params);
    }

    public function visitWiki(&$item, $params)
    {
        return $this->visitDocument($item, $params);
    }

    public function visitLink(&$item, $params)
    {
        return $this->visitDocument($item, $params);
    }

    public function visitEmbeddedFile(&$item, $params)
    {
        return $this->visitDocument($item, $params);
    }

    public function visitFile(&$item, $params)
    {
        return $this->visitDocument($item, $params);
    }

    public function visitDocument(&$item, $params)
    {
        return '';
    }

    public function visitFolder(&$item, $params)
    {
        $content  = '<blockquote>';
        $checked  = ! $params['user']->isAnonymous() && $this->notificationsManager->userExists($params['user']->getId(), $this->item->getId(), PLUGIN_DOCMAN_NOTIFICATION_CASCADE) ? 'checked="checked"' : '';
        $disabled = $params['user']->isAnonymous() ? 'disabled="disabled"' : '';
        $content .= '<input type="hidden" name="cascade" value="0" />';
        $content .= '<label for="plugin_docman_monitor_cascade_item" class="checkbox">';
        $content .= '<input type="checkbox" name="cascade" value="1" id="plugin_docman_monitor_cascade_item" ' . $checked . ' ' . $disabled . ' />';
        $content .= dgettext('tuleap-docman', '...and for the whole sub-hierarchy.') . '</label>';
        $content .= '</blockquote>';
        return $content;
    }
}
