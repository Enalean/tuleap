<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\SVN\SiteAdmin;

use Exception;
use Feedback;
use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\SVNCore\Cache\ParameterSaver;

final class UpdateTuleapPMParamsController implements DispatchableWithRequest
{
    public const URL = '/plugins/svn/admin/cache';

    /**
     * @var ParameterSaver
     */
    private $parameter_saver;

    public function __construct(ParameterSaver $parameter_saver)
    {
        $this->parameter_saver = $parameter_saver;
    }

    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        if (! $request->getCurrentUser()->isSuperUser()) {
            throw new ForbiddenException();
        }

        DisplayTuleapPMParamsController::getCSRFToken()->check();

        try {
            $this->parameter_saver->save(
                $request->get('cache-lifetime')
            );
            $layout->addFeedback(
                Feedback::INFO,
                dgettext('tuleap-svn', 'Your settings have been successfully saved and will be applied shortly.')
            );
        } catch (Exception $exception) {
            $layout->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-svn', 'Your settings could not be saved.')
            );
        }
        $layout->redirect(DisplayTuleapPMParamsController::URL);
    }
}
