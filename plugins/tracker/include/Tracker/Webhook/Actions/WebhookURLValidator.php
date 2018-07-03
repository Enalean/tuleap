<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\Webhook\Actions;

use Feedback;
use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Valid_HTTPURI;

class WebhookURLValidator
{
    /**
     * @return string
     */
    public function getValidURL(HTTPRequest $request, BaseLayout $layout, $redirect_url)
    {
        $valid_url = new Valid_HTTPURI('webhook_url');
        $valid_url->required();

        if (! $request->valid($valid_url)) {
            $layout->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-tracker', 'The submitted URL is not valid.')
            );
            $layout->redirect($redirect_url);
        }

        return $request->get('webhook_url');
    }
}
