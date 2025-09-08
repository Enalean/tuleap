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
use Tuleap\Config\ConfigSet;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\SVN\Commit\FileSizeValidator;

final class UpdateMaxFileSizeController implements DispatchableWithRequest
{
    public const URL = '/plugins/svn/admin/max-file-size';
    /**
     * @var ConfigSet
     */
    private $config_set;

    public function __construct(ConfigSet $config_set)
    {
        $this->config_set = $config_set;
    }

    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        if (! $request->getCurrentUser()->isSuperUser()) {
            throw new ForbiddenException();
        }

        DisplayMaxFileSizeController::getCSRFToken()->check();

        try {
            $this->config_set->set(FileSizeValidator::CONFIG_KEY, (string) $request->getValidated('max-file-size', 'uint', 0));
            $layout->addFeedback(
                Feedback::INFO,
                dgettext('tuleap-svn', 'Your settings have been successfully saved.')
            );
        } catch (Exception $exception) {
            $layout->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-svn', 'Your settings could not be saved.')
            );
        }
        $layout->redirect(DisplayMaxFileSizeController::URL);
    }
}
