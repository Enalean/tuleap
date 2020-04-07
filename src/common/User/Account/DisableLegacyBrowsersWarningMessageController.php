<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

namespace Tuleap\User\Account;

use CSRFSynchronizerToken;
use Feedback;
use HTTPRequest;
use PFUser;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequestNoAuthz;

final class DisableLegacyBrowsersWarningMessageController implements DispatchableWithRequestNoAuthz
{
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $csrf = new CSRFSynchronizerToken('/account/disable_legacy_browser_warning');
        $csrf->check('/my/', $request);

        $request->getCurrentUser()->setPreference(PFUser::PREFERENCE_DISABLE_IE7_WARNING, '1');

        $layout->addFeedback(Feedback::INFO, _('We will bother you later with the deprecation of IE < 11.'));
        $layout->redirect('/my/');
    }
}
