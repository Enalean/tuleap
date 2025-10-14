<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\FRS\LicenseAgreement;

/**
 * @psalm-immutable
 */
class DefaultLicenseAgreement implements LicenseAgreementInterface
{
    public const int ID = 0;

    #[\Override]
    public function getAsJson(): string
    {
        return '{}';
    }

    #[\Override]
    public function getId(): int
    {
        return self::ID;
    }

    #[\Override]
    public function getTitle(): string
    {
        return 'Code eXchange Corporate Policy';
    }

    #[\Override]
    public function getContent(): string
    {
        return '';
    }

    #[\Override]
    public function getLicenseOptionPresenter(LicenseAgreementInterface $selected_agreement): LicenseOptionPresenter
    {
        return new LicenseOptionPresenter($this->getId(), $this->getTitle(), $selected_agreement->getId() === $this->getId());
    }

    #[\Override]
    public function isModifiable(): bool
    {
        return false;
    }

    #[\Override]
    public function isViewable(): bool
    {
        return true;
    }

    #[\Override]
    public function canBeDeleted(): bool
    {
        return false;
    }
}
