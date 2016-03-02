<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * I extract data from ElasticSearch 1.2 search results
 * And I build ElasticSearch_SearchResult* objects
 */

class ElasticSearch_1_2_ResultFactory {

    /** @var ProjectManager */
    private $project_manager;

    /** @var URLVerification */
    private $url_verification;

    /** @var UserManager */
    private $user_manager;

    public function __construct(
        ProjectManager $project_manager,
        URLVerification $url_verification,
        UserManager $user_manager
    ) {
        $this->project_manager  = $project_manager;
        $this->url_verification = $url_verification;
        $this->user_manager     = $user_manager;
    }

    public function getChangesetIds(array $data) {
        $results = array();
        if (! empty($data['hits']['total'])) {
            foreach ($data['hits']['hits'] as $hit) {
                $results[$hit['fields']['id'][0]] = $hit['fields']['last_changeset_id'][0];
            }
        }

        return $results;
    }

    private function extractGroupIdFromHit(array $hit_data) {
        return $hit_data['fields']['group_id'][0];
    }

    private function extractIndexFromHit(array $hit_data) {
        return $hit_data['_index'];
    }

    public function getQueryTime(array $data) {
        if (isset($data['time'])) {
            return $data['time'];
        }

        return 0;
    }

    public function getSearchResultProjectsFacet(
        array $result,
        array $submitted_facets
    ) {
        if (isset($result['facets']['projects'])) {
            $current_user = $this->user_manager->getCurrentUser();

            return new ElasticSearch_SearchResultProjectsFacetCollection(
                $current_user,
                $result['facets']['projects'],
                $this->project_manager,
                $submitted_facets,
                $current_user->getProjects()
            );
        }
    }

    public function getSearchResultOwnerFacet(
        array $result,
        array $submitted_facets
    ) {
        if (isset($result['facets']['owner'])) {
            $current_user = $this->user_manager->getCurrentUser();

            return new ElasticSearch_SearchResultOwnerFacet($submitted_facets, $current_user);
        }
    }

    public function getSearchResultUpdateDateFacet(
        array $result,
        array $submitted_facets
    ) {
        if (isset($result['facets']['update_date'])) {
            $current_user = $this->user_manager->getCurrentUser();

            return new ElasticSearch_SearchResultUpdateDateFacetCollection($submitted_facets);
        }
    }

    public function getSearchResults(array $result) {
        $results   = array();
        $validator = new ElasticSearch_1_2_ResultValidator();

        if (! isset($result['hits']['hits'])) {
            return $results;
        }

        $user = $this->user_manager->getCurrentUser();

        foreach ($result['hits']['hits'] as $hit) {
            $project = $this->project_manager->getProject($this->extractGroupIdFromHit($hit));
            $index   = $this->extractIndexFromHit($hit);

            if ($project->isError()) {
                continue;
            }

            try{
                $this->url_verification->userCanAccessProject($user, $project);
            } catch (Project_AccessPrivateException $exception) {
                continue;
            } catch(Project_AccessDeletedException $exception) {
                continue;
            }

            switch ($index) {
                case fulltextsearchPlugin::SEARCH_DOCMAN_TYPE:
                    if (! $validator->isDocmanResultValid($hit)) {
                        continue;
                    }
                    $results[] = new ElasticSearch_SearchResultDocman($hit, $project);
                    break;
                case fulltextsearchPlugin::SEARCH_WIKI_TYPE:
                    if (! $validator->isWikiResultValid($hit)) {
                        continue;
                    }
                    $wiki = new Wiki($project->getID());

                    if ($wiki->isAutorized($user->getId())) {
                        $results[] = new ElasticSearch_SearchResultWiki($hit, $project);
                    }
                    break;
                case fulltextsearchPlugin::SEARCH_TRACKER_TYPE:
                    if (! $validator->isArtifactResultValid($hit)) {
                        continue;
                    }
                    $artifact = Tracker_ArtifactFactory::instance()->getArtifactById($hit['fields']['id'][0]);
                    if ($artifact->userCanView($user)) {
                        $results[] = new ElasticSearch_SearchResultTracker($hit, $project, $artifact);
                    }
                    break;
                default :
            }
        }

        return $results;
    }

}