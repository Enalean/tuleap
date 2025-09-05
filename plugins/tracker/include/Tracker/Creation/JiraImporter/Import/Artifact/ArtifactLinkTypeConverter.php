<?php
/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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


namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact;

use Cocur\Slugify\Slugify;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\AllTypesRetriever;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeIsChildPresenter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenter;

final class ArtifactLinkTypeConverter implements GetExistingArtifactLinkTypes, GetMissingArtifactLinkTypes
{
    /**
     * @var array<string, TypePresenter>|null
     */
    private ?array $existing_type_names;

    public function __construct(private AllTypesRetriever $all_natures_retriever)
    {
    }

    #[\Override]
    public function getMissingArtifactLinkTypes(array $json_representation): ?TypePresenter
    {
        $tuleap_type = $this->getArtifactLinkTypeFromJiraRepresentation($json_representation);
        if (! $tuleap_type || $this->doesTypeExists($tuleap_type)) {
            return null;
        }
        return $tuleap_type;
    }

    #[\Override]
    public function getExistingArtifactLinkTypes(array $json_representation): ?TypePresenter
    {
        $tuleap_type = $this->getArtifactLinkTypeFromJiraRepresentation($json_representation);
        if (! $tuleap_type || ! $this->doesTypeExists($tuleap_type)) {
            return null;
        }
        return $tuleap_type;
    }

    private function getArtifactLinkTypeFromJiraRepresentation(array $json_representation): ?TypePresenter
    {
        if (isset($json_representation['name']) && $json_representation['name'] === self::FAKE_JIRA_TYPE_TO_RECREATE_CHILDREN) {
            return new TypeIsChildPresenter();
        }
        if (! isset($json_representation['name'], $json_representation['outward'], $json_representation['inward'])) {
            return null;
        }
        $slugify = new Slugify(['regexp' => '/[^A-Za-z_]+/', 'lowercase' => false, 'separator' => '_']);
        return TypePresenter::buildVisibleType($slugify->slugify($json_representation['name']), $json_representation['outward'], $json_representation['inward']);
    }

    private function doesTypeExists(TypePresenter $tuleap_type): bool
    {
        if (! isset($this->existing_type_names)) {
            foreach ($this->all_natures_retriever->getAllTypes() as $type) {
                $this->existing_type_names[$type->shortname] = $type;
            }
        }
        return isset($this->existing_type_names[$tuleap_type->shortname]);
    }
}
