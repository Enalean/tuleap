<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\User\AccessKey;

use CSRFSynchronizerToken;
use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\User\Account\DisplayKeysTokensController;

class AccessKeyRevocationController implements DispatchableWithRequest
{
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;

    public function __construct(CSRFSynchronizerToken $csrf_token)
    {
        $this->csrf_token = $csrf_token;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $current_user = $request->getCurrentUser();
        if ($current_user->isAnonymous()) {
            throw new ForbiddenException(_('Unauthorized action for anonymous'));
        }

        $this->csrf_token->check(DisplayKeysTokensController::URL);

        $key_ids          = [];
        $selected_key_ids = $request->get('access-keys-selected');
        if (is_array($selected_key_ids)) {
            foreach ($selected_key_ids as $selected_key_id) {
                $key_ids[] = (int) $selected_key_id;
            }
        }

        $revoker = new AccessKeyRevoker(new AccessKeyDAO());
        $revoker->revokeASetOfUserAccessKeys($current_user, $key_ids);

        $layout->addFeedback(\Feedback::INFO, _('Access keys have been successfully deleted.'));
        $layout->redirect(DisplayKeysTokensController::URL);
    }
}
