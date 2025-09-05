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

use Tracker_ArtifactLinkInfo;
use Tuleap\Tracker\Artifact\Artifact;

class MethodBasedOnLinksCount implements IComputeProgression
{
    private const METHOD_NAME = 'artifacts-links-count-based';

    /**
     * @var SemanticProgressDao
     */
    private $dao;
    /**
     * @var string
     */
    private $artifact_link_type;

    public function __construct(
        SemanticProgressDao $dao,
        string $artifact_link_type,
    ) {
        $this->dao                = $dao;
        $this->artifact_link_type = $artifact_link_type;
    }

    #[\Override]
    public static function getMethodName(): string
    {
        return self::METHOD_NAME;
    }

    #[\Override]
    public static function getMethodLabel(): string
    {
        return dgettext('tuleap-tracker', 'Artifact links count based');
    }

    #[\Override]
    public function getCurrentConfigurationDescription(): string
    {
        return dgettext(
            'tuleap-tracker',
            'The progress of artifacts will be based on the ratio of nb open/nb total of children artifacts.'
        );
    }

    public function getArtifactLinkType(): string
    {
        return $this->artifact_link_type;
    }

    #[\Override]
    public function isFieldUsedInComputation(\Tuleap\Tracker\FormElement\Field\TrackerField $field): bool
    {
        return $field instanceof \Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
    }

    #[\Override]
    public function computeProgression(Artifact $artifact, \PFUser $user): ProgressionResult
    {
        $nb_total            = 0;
        $nb_closed           = 0;
        $artifact_link_field = $artifact->getAnArtifactLinkField($user);

        if ($artifact_link_field === null) {
            return $this->getNullProgressionResult();
        }

        $linked_artifacts_value = $artifact_link_field->getLastChangesetValue($artifact);

        if (! $linked_artifacts_value) {
            return $this->getProgressResult($artifact, 0, 0);
        }

        foreach ($linked_artifacts_value->getValue() as $link_info) {
            \assert($link_info instanceof Tracker_ArtifactLinkInfo);
            if ($link_info->getType() !== $this->artifact_link_type) {
                continue;
            }

            $linked_artifact = $link_info->getArtifact();
            if ($linked_artifact === null) {
                continue;
            }

            $nb_total++;

            if (! $linked_artifact->isOpen()) {
                $nb_closed++;
            }
        }

        return $this->getProgressResult($artifact, $nb_total, $nb_closed);
    }

    private function getProgressResult(Artifact $artifact, int $nb_total, int $nb_closed): ProgressionResult
    {
        if ($nb_total === 0) {
            return new ProgressionResult(
                $artifact->isOpen() ? 0 : 1,
                ''
            );
        }

        return new ProgressionResult($nb_closed / $nb_total, '');
    }

    #[\Override]
    public function isConfiguredAndValid(): bool
    {
        return true;
    }

    #[\Override]
    public function isConfigured(): bool
    {
        return true;
    }

    #[\Override]
    public function getErrorMessage(): string
    {
        return '';
    }

    #[\Override]
    public function exportToREST(\PFUser $user): ?IRepresentSemanticProgress
    {
        return new SemanticProgressBasedOnLinksCountRepresentation(
            $this->artifact_link_type
        );
    }

    #[\Override]
    public function exportToXMl(\SimpleXMLElement $root, array $xml_mapping): void
    {
        $xml_semantic_progress = $root->addChild('semantic');
        $xml_semantic_progress->addAttribute('type', SemanticProgress::NAME);
        $xml_semantic_progress->addChild('artifact_link_type')->addAttribute('shortname', $this->artifact_link_type);
    }

    #[\Override]
    public function saveSemanticForTracker(\Tuleap\Tracker\Tracker $tracker): bool
    {
        return $this->dao->save(
            $tracker->getId(),
            null,
            null,
            $this->artifact_link_type
        );
    }

    #[\Override]
    public function deleteSemanticForTracker(\Tuleap\Tracker\Tracker $tracker): bool
    {
        return $this->dao->delete($tracker->getId());
    }

    private function getNullProgressionResult(): ProgressionResult
    {
        return new ProgressionResult(
            null,
            ''
        );
    }
}
