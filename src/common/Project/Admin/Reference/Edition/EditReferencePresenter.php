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

namespace Tuleap\Project\Admin\Reference\Edition;

final readonly class EditReferencePresenter
{
    /**
     * @var EditNatureReferencePresenter[]
     */
    public array $natures;
    public int $reference_id;
    public string $reference_nature;
    public string $reference_description;
    public string $reference_link;
    public string $service_shortname;
    public bool $reference_is_used;
    public string $reference_key;
    public string $reference_scope;
    public bool $is_empty_description;

    /**
     * @param $natures array<string, EditNatureReferencePresenter>
     */
    public function __construct(
        public int $project_id,
        array $natures,
        public array $services_reference,
        public string $url,
        public \CSRFSynchronizerToken $csrf_token,
        public string $short_locale,
        \Reference $reference,
        public bool $is_reference_read_only,
        public bool $is_super_user,
        public bool $is_in_default_template,
    ) {
        usort(
            $natures,
            static fn (EditNatureReferencePresenter $a, EditNatureReferencePresenter $b) => strnatcasecmp($a->nature_label, $b->nature_label)
        );
        $this->natures          = $natures;
        $this->reference_id     = $reference->getId();
        $this->reference_key    = $reference->getKeyWord();
        $this->reference_nature = $reference->getNature();
        if ($reference->getDescription() === 'reference_' . $reference->getKeyWord() . '_desc_key') {
            $this->reference_description = $GLOBALS['Language']->getOverridableText('project_reference', $reference->getDescription());
        } else {
            $this->reference_description = $reference->getDescription();
        }
        $this->reference_link       = $reference->getLink();
        $this->service_shortname    = $reference->getServiceShortName();
        $this->reference_is_used    = $reference->isActive();
        $this->reference_scope      = $reference->getScope() === 'P' ? _('Project') : _('System');
        $this->is_empty_description = $this->reference_description === '';
    }
}
