<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\JiraImport\Project\ArtifactLinkType;

use Psr\Log\LoggerInterface;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\GetMissingArtifactLinkTypes;
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\InvalidTypeParameterException;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeCreatorInterface;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\UnableToCreateTypeException;

final class ArtifactLinkTypeImporter
{
    private const string ISSUE_LINK_TYPE_URL = 'issueLinkType';

    public function __construct(private GetMissingArtifactLinkTypes $link_type_converter, private TypeCreatorInterface $creator)
    {
    }

    public function import(JiraClient $jira_client, LoggerInterface $logger): void
    {
        $issue_link_types = $jira_client->getUrl(ClientWrapper::JIRA_CORE_BASE_URL . '/' . self::ISSUE_LINK_TYPE_URL);
        if (! isset($issue_link_types['issueLinkTypes']) || ! is_array($issue_link_types['issueLinkTypes'])) {
            throw new \RuntimeException('Payload returned by Jira ' . self::ISSUE_LINK_TYPE_URL . ' endpoint was not expected `issueLinkTypes` must be present and must be an array');
        }
        foreach ($issue_link_types['issueLinkTypes'] as $jira_link_type) {
            try {
                $tuleap_link_type = $this->link_type_converter->getMissingArtifactLinkTypes($jira_link_type);
                if ($tuleap_link_type === null) {
                    continue;
                }
                $this->creator->createFromType($tuleap_link_type);
            } catch (InvalidTypeParameterException | UnableToCreateTypeException $e) {
                $logger->warning(sprintf('Cannot create link type `%s` (%s). Links between issues will be created without this type.', $jira_link_type['name'], $e->getMessage()));
            }
        }
    }
}
