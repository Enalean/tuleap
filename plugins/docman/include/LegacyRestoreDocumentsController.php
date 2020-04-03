<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Docman;

use CSRFSynchronizerToken;
use Docman_Controller;
use Docman_ItemFactory;
use Docman_VersionFactory;
use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Valid_WhiteList;

final class LegacyRestoreDocumentsController implements DispatchableWithRequest
{
    /**
     * @var \DocmanPlugin
     */
    private $plugin;

    public function __construct(\DocmanPlugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        \Tuleap\Project\ServiceInstrumentation::increment('docman');
        // Need to setup the controller so the notification & logging works (setup in controller constructor)
        new Docman_Controller($this->plugin, $this->plugin->getPluginPath(), $this->plugin->getThemePath(), $request);

        $current_user = $request->getCurrentUser();
        if (! $current_user->isSuperUser()) {
            $layout->redirect('/');
        }

        $func    = $request->getValidated('func', new Valid_WhiteList('func', array('confirm_restore_item', 'confirm_restore_version')));
        $groupId = $request->getValidated('group_id', 'uint', 0);
        $id      = $request->getValidated('id', 'uint', 0);

        $csrf_token = new CSRFSynchronizerToken('/admin/show_pending_documents.php?group_id=' . urlencode((string) $groupId));
        $csrf_token->check();

        if ($request->existAndNonEmpty('func')) {
            switch ($func) {
                case 'confirm_restore_item':
                    $itemFactory = new Docman_ItemFactory($groupId);
                    $item = $itemFactory->getItemFromDb($id, array('ignore_deleted' => true));
                    if ($item !== null && $itemFactory->restore($item)) {
                        $url = $this->plugin->getPluginPath() . '/?group_id=' . $groupId . '&action=details&id=' . $id . '&section=properties';
                        $layout->addFeedback('info', sprintf(dgettext('tuleap-docman', 'The <a href="%1$s">selected item</a> has been successfully restored.'), $url), CODENDI_PURIFIER_DISABLED);
                        $layout->redirect('/admin/show_pending_documents.php?group_id=' . $groupId . '&focus=item');
                    } else {
                        exit_error(dgettext('tuleap-docman', 'Error'), dgettext('tuleap-docman', 'The selected item has not been restored.'));
                    }
                    break;
                case 'confirm_restore_version':
                    $versionFactory = new Docman_VersionFactory();
                    $version = $versionFactory->getSpecificVersionById($id);
                    if ($version !== null && $versionFactory->restore($version)) {
                        $url = $this->plugin->getPluginPath() . '/?group_id=' . $groupId . '&action=details&id=' . $version->getItemId() . '&section=history';
                        $layout->addFeedback('info', sprintf(dgettext('tuleap-docman', 'The <a href="%1$s">selected version</a> has been successfully restored.'), $url), CODENDI_PURIFIER_DISABLED);
                        $layout->redirect('/admin/show_pending_documents.php?group_id=' . $groupId . '&focus=version');
                    } else {
                        exit_error(dgettext('tuleap-docman', 'Error'), dgettext('tuleap-docman', 'The selected version has not been restored.'));
                    }
                    break;
                default:
                    break;
            }
        }
    }
}
