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

use CSRFSynchronizerToken;
use ForgeConfig;
use HTTPRequest;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\SVN\Commit\FileSizeValidator;

final class DisplayMaxFileSizeController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    public const string URL = '/plugins/svn/admin/max-file-size';
    /**
     * @var AdminPageRenderer
     */
    private $renderer;

    public function __construct(AdminPageRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        if (! $request->getCurrentUser()->isSuperUser()) {
            throw new ForbiddenException();
        }


        $this->renderer->renderANoFramedPresenter(
            dgettext('tuleap-svn', 'Subversion'),
            __DIR__ . '/templates',
            'admin',
            new MaxFileSizePresenter(
                self::getCSRFToken(),
                $this->getMaxFileSize(),
            )
        );
    }

    private function getMaxFileSize(): ?int
    {
        if (! FileSizeValidator::isLimitSet()) {
            return null;
        }
        return ForgeConfig::getInt(FileSizeValidator::CONFIG_KEY);
    }

    public static function getCSRFToken(): CSRFSynchronizerToken
    {
        return new CSRFSynchronizerToken(self::URL);
    }
}
