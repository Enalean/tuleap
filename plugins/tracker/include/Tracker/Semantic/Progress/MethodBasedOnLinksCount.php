<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Progress;

use Tuleap\Tracker\Artifact\Artifact;

class MethodBasedOnLinksCount implements IComputeProgression
{
    private const METHOD_NAME = 'artifacts-links-count-based';

    /**
     * @var SemanticProgressDao
     */
    private $dao;
    /**
     * @var \Tracker_FormElement_Field_ArtifactLink
     */
    private $artifact_link_field;
    /**
     * @var string
     */
    private $artifact_link_type;

    public function __construct(
        SemanticProgressDao $dao,
        \Tracker_FormElement_Field_ArtifactLink $artifact_link_field,
        string $artifact_link_nature
    ) {
        $this->dao                 = $dao;
        $this->artifact_link_field = $artifact_link_field;
        $this->artifact_link_type  = $artifact_link_nature;
    }

    public static function getMethodName(): string
    {
        return self::METHOD_NAME;
    }

    public static function getMethodLabel(): string
    {
        return dgettext('tuleap-tracker', 'Artifact links count based');
    }

    public function getCurrentConfigurationDescription(): string
    {
        return dgettext(
            'tuleap-tracker',
            'The progress of artifacts will be based on the ratio of nb open/nb total of children artifacts.'
        );
    }

    public function getArtifactLinkFieldId(): int
    {
        return $this->artifact_link_field->getId();
    }

    public function getArtifactLinkType(): string
    {
        return $this->artifact_link_type;
    }

    public function isFieldUsedInComputation(\Tracker_FormElement_Field $field): bool
    {
        return $field->getId() === $this->artifact_link_field->getId();
    }

    public function computeProgression(Artifact $artifact, \PFUser $user): ProgressionResult
    {
        return new ProgressionResult(null, $this->getErrorMessage());
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function getErrorMessage(): string
    {
        return dgettext('tuleap-tracker', 'Implementation of child count based semantic progress is ongoing. You cannot use it yet.');
    }

    public function exportToREST(\PFUser $user): ?SemanticProgressRepresentation
    {
        return null;
    }

    public function exportToXMl(\SimpleXMLElement $root, array $xml_mapping): void
    {
    }

    public function saveSemanticForTracker(\Tracker $tracker): bool
    {
        return false;
    }

    public function deleteSemanticForTracker(\Tracker $tracker): bool
    {
        return false;
    }
}
