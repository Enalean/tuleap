<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\OnlyOffice\Open;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Project\ProjectByIDFactory;

final class OnlyOfficeDocumentProvider implements ProvideOnlyOfficeDocument
{
    public function __construct(
        private ProvideDocmanFileLastVersion $docman_file_last_version_provider,
        private ProjectByIDFactory $project_factory,
    ) {
    }

    /**
     * @psalm-return Ok<OnlyOfficeDocument>|Err<Fault>
     */
    public function getDocument(\PFUser $user, int $item_id): Ok|Err
    {
        return $this->docman_file_last_version_provider
            ->getLastVersionOfAFileUserCanAccess($user, $item_id)
            ->andThen(
                /** @psalm-return Ok<OnlyOfficeDocument>|Err<Fault> */
                function (DocmanFileLastVersion $file_last_version): Ok|Err {
                    $filename = $file_last_version->version->getFilename();
                    if (! AllowedFileExtensions::isFilenameAllowedToBeOpenInOnlyOffice($filename)) {
                        return Result::err(
                            Fault::fromMessage(
                                sprintf('Item #%d cannot be opened with ONLYOFFICE', $file_last_version->item->getId())
                            )
                        );
                    }

                    return Result::ok(
                        new OnlyOfficeDocument(
                            $this->project_factory->getProjectById((int) $file_last_version->item->getGroupId()),
                            $file_last_version->item,
                            (int) $file_last_version->version->getId(),
                            $filename
                        )
                    );
                }
            );
    }
}
