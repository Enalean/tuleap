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
class NewLicenseAgreement implements LicenseAgreementInterface
{

    /**
     * @var string
     */
    private $title;
    /**
     * @var string
     */
    private $content;

    public function __construct(string $title, string $content)
    {
        $this->title = $title;
        $this->content = $content;
    }

    public function getAsJson(): string
    {
        return \json_encode(['title' => $this->getTitle(), 'content' => $this->getContent()]);
    }

    public function getId(): int
    {
        return -2;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getLicenseOptionPresenter(LicenseAgreementInterface $selected_agreement): LicenseOptionPresenter
    {
        return new LicenseOptionPresenter($this->getId(), $this->getTitle(), false);
    }

    public function isModifiable(): bool
    {
        return true;
    }

    public function isViewable(): bool
    {
        return true;
    }

    public function canBeDeleted(): bool
    {
        return false;
    }
}
