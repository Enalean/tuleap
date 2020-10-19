<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

use Http\Client\Common\Plugin\CookiePlugin;
use Http\Message\CookieJar;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Jenkins\JenkinsCSRFCrumbRetriever;

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps,PSR1.Classes.ClassDeclaration.MissingNamespace
class Transition_PostAction_CIBuildFactory implements Transition_PostActionSubFactory
{

    /**
     * @var array<string, class-string> of available post actions classes run after fields validation
     */
    protected $post_actions_classes_ci = [
        Transition_PostAction_CIBuild::SHORT_NAME => Transition_PostAction_CIBuild::class,
    ];

    /** @var Transition_PostAction_CIBuildDao */
    private $dao;

    /**
     * @psalm-var array<int, array<int, list<array{id: int, transition_id: int, job_url: string}>>>
     */
    private $cache = [];

    public function __construct(Transition_PostAction_CIBuildDao $dao)
    {
        $this->dao = $dao;
    }

    public function warmUpCacheForWorkflow(Workflow $workflow): void
    {
        $workflow_id = (int) $workflow->getId();
        if (isset($this->cache[$workflow_id])) {
            return;
        }
        $dar = $this->dao->searchByWorkflow($workflow);
        if (! $dar) {
            return;
        }
        $this->cache[$workflow_id] = [];
        foreach ($dar as $row) {
            $this->cache[$workflow_id][(int) $row['transition_id']][] = [
                'id'            => (int) $row['id'],
                'transition_id' => (int) $row['transition_id'],
                'job_url'       => (string) $row['job_url'],
            ];
        }
    }

    /**
     * @return Transition_PostAction_CIBuild[]
     */
    public function loadPostActions(Transition $transition): array
    {
        $post_actions = [];

        $dar = $this->loadPostActionRows($transition);
        if (! $dar) {
            return [];
        }
        foreach ($dar as $row) {
            $post_actions[] = $this->buildPostAction($transition, $row);
        }

        return $post_actions;
    }

    /**
     * Retrieves matching PostAction database records.
     *
     * @psalm-return list<array{id: int, transition_id: int, job_url: string}>
     */
    private function loadPostActionRows(Transition $transition): array
    {
        $workflow_id = (int) $transition->getWorkflow()->getId();
        if (isset($this->cache[$workflow_id])) {
            $transition_id = (int) $transition->getId();
            return $this->cache[$workflow_id][$transition_id] ?? [];
        }
        $dar = $this->dao->searchByTransitionId($transition->getId());
        if (! $dar) {
            return [];
        }
        $rows = [];
        foreach ($dar as $row) {
            $rows[] = [
                'id'            => (int) $row['id'],
                'transition_id' => (int) $row['transition_id'],
                'job_url'       => (string) $row['job_url'],
            ];
        }
        return $rows;
    }

    /**
     * @see Transition_PostActionSubFactory::saveObject()
     */
    public function saveObject(Transition_PostAction $post_action)
    {
        $this->dao->create($post_action->getTransition()->getId(), $post_action->getJobUrl());
    }

    /**
     * @see Transition_PostActionSubFactory::duplicate()
     */
    public function duplicate(Transition $from_transition, $to_transition_id, array $field_mapping)
    {
        $this->dao->duplicate($from_transition->getId(), $to_transition_id);
    }

    /**
     * @see Transition_PostActionSubFactory::isFieldUsedInPostActions()
     */
    public function isFieldUsedInPostActions(Tracker_FormElement_Field $field)
    {
        return false;
    }

    /**
     * @see Transition_PostActionSubFactory::getInstanceFromXML()
     */
    public function getInstanceFromXML($xml, &$xmlMapping, Transition $transition)
    {
        $postaction_attributes = $xml->attributes();
        $row = [
            'id'      => 0,
            'job_url' => (string) $postaction_attributes['job_url'],
        ];

        return $this->buildPostAction($transition, $row);
    }

    private function buildPostAction(Transition $transition, array $row): Transition_PostAction_CIBuild
    {
        $id                           = (int) $row['id'];
        $job_url                      = (string) $row['job_url'];
        $http_client                  = HttpClientFactory::createClient(new CookiePlugin(new CookieJar()));
        $http_request_factory         = HTTPFactoryBuilder::requestFactory();
        $jenkins_csrf_crumb_retriever = new JenkinsCSRFCrumbRetriever($http_client, $http_request_factory);
        $ci_client                    = new Jenkins_Client(
            $http_client,
            $http_request_factory,
            HTTPFactoryBuilder::streamFactory(),
            $jenkins_csrf_crumb_retriever
        );

        return new Transition_PostAction_CIBuild($transition, $id, $job_url, $ci_client);
    }
}
