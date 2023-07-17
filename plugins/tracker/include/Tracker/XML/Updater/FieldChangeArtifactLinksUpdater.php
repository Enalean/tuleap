<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Tracker\XML\Updater;

use Codendi_HTMLPurifier;
use SimpleXMLElement;
use Tracker_Artifact_Changeset_Comment;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\AllTypesRetriever;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\IRetrieveAllUsableTypesInProject;
use Tuleap\User\RetrieveUserById;
use XML_SimpleXMLCDATAFactory;

final class FieldChangeArtifactLinksUpdater implements UpdateArtifactLinkXML
{
    public function __construct(
        private readonly AllTypesRetriever $all_types_retriever,
        private readonly IRetrieveAllUsableTypesInProject $retrieve_all_usable_types_in_project,
        private readonly Codendi_HTMLPurifier $purifier,
        private readonly RetrieveUserById $retrieve_user_by_id,
    ) {
    }

    public function updateArtifactLinks(
        SimpleXMLElement $changeset_xml,
        \Tracker_FormElement_Field_ArtifactLink $destination_field,
        int $index,
    ): void {
        $artifact_links = $changeset_xml->field_change[$index]->value;
        if ($artifact_links === null) {
            return;
        }

        $system_natures_shortnames = array_map(
            static fn($nature) => $nature->shortname,
            array_filter(
                $this->all_types_retriever->getAllTypes(),
                static fn($nature) => $nature->is_system
            ),
        );

        $usable_natures_in_destination_project = array_map(
            static fn($nature) => $nature->shortname,
            $this->retrieve_all_usable_types_in_project->getAllUsableTypesInProject(
                $destination_field->getTracker()->getProject()
            )
        );

        $comment = [];

        foreach ($artifact_links as $link) {
            $nature = (string) $link->attributes()->nature;

            if (
                in_array($nature, $system_natures_shortnames, true) ||
                ! in_array($nature, $usable_natures_in_destination_project, true)
            ) {
                $link->attributes()->nature = \Tracker_FormElement_Field_ArtifactLink::NO_TYPE;

                $comment[(string) $link] = sprintf(
                    dgettext('tuleap-tracker', 'The type "%s" of the link to artifact #%s has been set to "no type"'),
                    $this->purifier->purify($nature),
                    $this->purifier->purify((string) $link)
                );
            }
        }

        if ($comment !== "") {
            $comments_node = $changeset_xml->addChild('comments');
            if (! $comments_node instanceof SimpleXMLElement) {
                return;
            }
            $comment_node = $comments_node->addChild('comment');
            if (! $comment_node instanceof SimpleXMLElement) {
                return;
            }

            $simplexml_cdata_factory = new XML_SimpleXMLCDATAFactory();

            $user = $this->retrieve_user_by_id->getUserById((string) $changeset_xml->submitted_by);

            $simplexml_cdata_factory->insertWithAttributes(
                $comment_node,
                'submitted_by',
                (string) $user?->getUserName(),
                ['format' => 'username']
            );

            $simplexml_cdata_factory->insertWithAttributes(
                $comment_node,
                'submitted_on',
                date('c', (int) $changeset_xml->submitted_on),
                ['format' => 'ISO8601']
            );

            $simplexml_cdata_factory->insertWithAttributes(
                $comment_node,
                'body',
                implode("<br />", $comment),
                ['format' => Tracker_Artifact_Changeset_Comment::HTML_COMMENT]
            );
        }
    }
}
