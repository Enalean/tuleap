<?php
/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Admin\Reference\Creation;

/**
 * @psalm-immutable
 */
final readonly class CreateReferencePresenter
{
    /**
     * @var NatureReferencePresenter[]
     */
    public array $natures;

    /**
     * @param $natures array<string, NatureReferencePresenter>
     */
    public function __construct(
        public int $project_id,
        array $natures,
        public bool $is_super_user_in_default_template,
        public array $services_reference,
        public string $url,
        public \CSRFSynchronizerToken $csrf_token,
        public string $short_locale,
    ) {
        usort(
            $natures,
            static fn (NatureReferencePresenter $a, NatureReferencePresenter $b) => strnatcasecmp($a->nature_label, $b->nature_label)
        );
        $this->natures = $natures;
    }
}
