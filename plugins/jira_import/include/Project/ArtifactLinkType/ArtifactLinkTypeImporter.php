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

use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\AllTypesRetriever;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\NatureCreatorInterface;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenter;

final class ArtifactLinkTypeImporter
{
    private const ISSUE_LINK_TYPE_URL = '/issueLinkType';

    /**
     * @var AllTypesRetriever
     */
    private $all_natures_retriever;
    /**
     * @var NatureCreatorInterface
     */
    private $creator;

    public function __construct(AllTypesRetriever $all_natures_retriever, NatureCreatorInterface $creator)
    {
        $this->all_natures_retriever = $all_natures_retriever;
        $this->creator               = $creator;
    }

    /**
     * @throws \Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\InvalidNatureParameterException
     * @throws \Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\UnableToCreateNatureException
     */
    public function import(JiraClient $jira_client): void
    {
        $existing_type_names = [];
        foreach ($this->all_natures_retriever->getAllTypes() as $type) {
            $existing_type_names[$type->shortname] = true;
        }

        $issue_link_types = $jira_client->getUrl(ClientWrapper::JIRA_CORE_BASE_URL . '/' . self::ISSUE_LINK_TYPE_URL);

        if (! isset($issue_link_types['issueLinkTypes']) || ! is_array($issue_link_types['issueLinkTypes'])) {
            throw new \RuntimeException('Payload returned by Jira ' . self::ISSUE_LINK_TYPE_URL . ' endpoint was not expected `issueLinkTypes` must be present and must be an array');
        }
        foreach ($issue_link_types['issueLinkTypes'] as $link_type) {
            if (isset($existing_type_names[$link_type['name']])) {
                continue;
            }
            $this->creator->createFromType(
                TypePresenter::buildVisibleType($link_type['name'], $link_type['outward'], $link_type['inward'])
            );
        }
    }
}
