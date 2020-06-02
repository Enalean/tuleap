<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Document\Config\Admin;

use ConfigDao;
use CSRFSynchronizerToken;
use HTTPRequest;
use Tuleap\Document\Config\HistoryEnforcementSettings;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;

class HistoryEnforcementAdminSaveController implements DispatchableWithRequest
{
    /**
     * @var ConfigDao
     */
    private $config_dao;
    /**
     * @var CSRFSynchronizerToken
     */
    private $token;

    public function __construct(CSRFSynchronizerToken $token, ConfigDao $config_dao)
    {
        $this->config_dao = $config_dao;
        $this->token      = $token;
    }

    public static function buildSelf(): self
    {
        return new self(
            new CSRFSynchronizerToken(HistoryEnforcementAdminController::URL),
            new ConfigDao()
        );
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        if (! $request->getCurrentUser()->isSuperUser()) {
            throw new ForbiddenException();
        }

        $this->token->check();

        $this->save(
            (bool) $request->get('is-changelog-proposed-after-dnd'),
            $layout
        );

        $layout->redirect(HistoryEnforcementAdminController::URL);
    }

    private function save(bool $is_changelog_proposed_after_dnd, BaseLayout $layout): void
    {
        $success = $this->config_dao->save(
            HistoryEnforcementSettings::IS_CHANGELOG_PROPOSED_AFTER_DND,
            $is_changelog_proposed_after_dnd
        );

        if ($success) {
            $layout->addFeedback(
                \Feedback::INFO,
                dgettext('tuleap-document', 'Settings have been saved successfully.')
            );
        } else {
            $layout->addFeedback(
                \Feedback::ERROR,
                dgettext('tuleap-document', 'An error occurred while saving configuration.')
            );
        }
    }
}
