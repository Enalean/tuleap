<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
use Tuleap\Document\Config\FileDownloadLimits;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;

class FilesDownloadLimitsAdminSaveController implements DispatchableWithRequest
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
            new CSRFSynchronizerToken(FilesDownloadLimitsAdminController::URL),
            new ConfigDao()
        );
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        if (! $request->getCurrentUser()->isSuperUser()) {
            throw new ForbiddenException();
        }

        $this->token->check();

        $max_archive_size  = $this->getSubmittedMaxArchiveSize($request, $layout);
        $warning_threshold = $this->getSubmittedWarningThreshold($request, $layout);

        if ($max_archive_size && $warning_threshold) {
            $this->save($max_archive_size, $warning_threshold, $layout);
        }

        $layout->redirect(FilesDownloadLimitsAdminController::URL);
    }

    private function getSubmittedMaxArchiveSize(HTTPRequest $request, BaseLayout $layout): int
    {
        $max_archive_size = (int) $request->getValidated('max-archive-size', 'uint');
        if (! $max_archive_size) {
            $layout->addFeedback(
                \Feedback::ERROR,
                dgettext('tuleap-document', 'Submitted maximum file size should be an unsigned integer greater than zero.')
            );
        }

        return $max_archive_size;
    }

    private function getSubmittedWarningThreshold(HTTPRequest $request, BaseLayout $layout): int
    {
        $warning_threshold = (int) $request->getValidated('warning-threshold', 'uint');
        if (! $warning_threshold) {
            $layout->addFeedback(
                \Feedback::ERROR,
                dgettext('tuleap-document', 'Submitted warning threshold should be an unsigned integer greater than zero.')
            );
        }

        return $warning_threshold;
    }

    private function save(int $max_archive_size, int $warning_threshold, BaseLayout $layout): void
    {
        $success = $this->config_dao->save(FileDownloadLimits::WARNING_THRESHOLD_NAME, $warning_threshold);
        $success = $this->config_dao->save(FileDownloadLimits::MAX_ARCHIVE_SIZE_NAME, $max_archive_size) && $success;

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
