<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Reference;

use FRSFileFactory;
use FRSReleaseFactory;
use Override;
use Psr\EventDispatcher\EventDispatcherInterface;
use ReferenceManager;

final class ProjectIdForSystemReferenceGetter implements GetProjectIdForSystemReference
{
    public function __construct(
        private readonly GetSystemReferenceNatureByKeyword $dao,
        private readonly FRSReleaseFactory $release_factory,
        private readonly FRSFileFactory $file_factory,
        private readonly EventDispatcherInterface $dispatcher,
    ) {
    }

    #[Override]
    public function getProjectIdForSystemReference(string $keyword, string $value): ?int
    {
        $nature = $this->getSystemReferenceNatureByKeyword($keyword);
        if ($nature === null) {
            return null;
        }

        if ($this->isCoreReference($nature)) {
            return $this->getProjectIdForCoreReference($nature, $value);
        }

        return $this->dispatcher
            ->dispatch(new GetProjectIdForSystemReferenceEvent($nature, $value))
            ->getProjectId();
    }

    private function isCoreReference(string $nature): bool
    {
        return in_array($nature, [ReferenceManager::REFERENCE_NATURE_RELEASE, ReferenceManager::REFERENCE_NATURE_FILE], true);
    }

    private function getProjectIdForCoreReference(string $nature, string $value): ?int
    {
        switch ($nature) {
            case ReferenceManager::REFERENCE_NATURE_RELEASE:
                $release = $this->release_factory->getFRSReleaseFromDb($value);

                if ($release) {
                    return (int) $release->getProject()->getID();
                }

                break;
            case ReferenceManager::REFERENCE_NATURE_FILE:
                $file = $this->file_factory->getFRSFileFromDb($value);

                if ($file) {
                    return (int) $file->getGroup()->getID();
                }

                break;
        }

        return null;
    }

    private function getSystemReferenceNatureByKeyword(string $keyword): ?string
    {
        $system_reference_nature_row = $this->dao->getSystemReferenceNatureByKeyword($keyword);

        if (! $system_reference_nature_row) {
            return null;
        }

        return $system_reference_nature_row['nature'];
    }
}
