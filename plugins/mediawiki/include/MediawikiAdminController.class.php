<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

require_once 'MediawikiAdminPresenter.class.php';
require_once 'MediawikiUserGroupsMapper.class.php';
require_once 'MediawikiManager.class.php';

class MediawikiAdminController {

    /** @var MediawikiUserGroupsMapper */
    private $mapper;

    /** @var MediawikiManager */
    private $manager;

    /** @var User_ForgeUserGroupFactory */
    private $user_group_factory;

    /** @var PermissionsNormalizer */
    private $permissions_normalizer;

    public function __construct() {
        $dao = new MediawikiDao();
        $this->mapper = new MediawikiUserGroupsMapper(
            $dao,
            new User_ForgeUserGroupPermissionsDao()
        );

        $this->manager = new MediawikiManager($dao);

        $user_dao = new UserGroupDao();
        $this->user_group_factory = new User_ForgeUserGroupFactory($user_dao);
        $this->permissions_normalizer = new PermissionsNormalizer();
   }

    public function index(ServiceMediawiki $service, HTTPRequest $request) {
        $this->assertUserIsProjectAdmin($service, $request);
        $GLOBALS['HTML']->includeFooterJavascriptFile(MEDIAWIKI_BASE_URL.'/forgejs/admin.js');

        $project = $request->getProject();
        $options = $this->manager->getOptions($project);

        $read_ugroups  = $this->getReadUGroups($project);
        $write_ugroups = $this->getWriteUGroups($project);

        $service->renderInPage(
            $request,
            $GLOBALS['Language']->getText('global', 'Administration'),
            'admin',
            new MediawikiAdminPresenter(
                $project,
                $this->getMappedGroupPresenter($project),
                $this->mapper->isDefaultMapping($project),
                $options,
                $read_ugroups,
                $write_ugroups
            )
        );
    }

    private function getReadUGroups(Project $project) {
        $user_groups  = $this->user_group_factory->getAllForProject($project);
        $read_ugroups = array();

        $selected_ugroups = $this->manager->getReadAccessControl($project);

        foreach ($user_groups as $ugroup) {
            $read_ugroups[] = array(
                'label'    => $ugroup->getName(),
                'value'    => $ugroup->getId(),
                'selected' => in_array($ugroup->getId(), $selected_ugroups)
            );
        }

        return $read_ugroups;
    }

    private function getWriteUGroups(Project $project) {
        $user_groups  = $this->user_group_factory->getAllForProject($project);
        $write_ugroups = array();

        $selected_ugroups = $this->manager->getWriteAccessControl($project);

        foreach ($user_groups as $ugroup) {
            $write_ugroups[] = array(
                'label'    => $ugroup->getName(),
                'value'    => $ugroup->getId(),
                'selected' => in_array($ugroup->getId(), $selected_ugroups)
            );
        }

        return $write_ugroups;
    }

    private function getMappedGroupPresenter(Project $project) {
        $group_mapper_presenters = array();
        $current_mapping = $this->mapper->getCurrentUserGroupMapping($project);
        $all_ugroups     = $this->getIndexedUgroups($project);
        foreach(MediawikiUserGroupsMapper::$MEDIAWIKI_MODIFIABLE_GROUP_NAMES as $mw_group_name) {
            $group_mapper_presenters[] = $this->getGroupPresenters($mw_group_name, $current_mapping, $all_ugroups);
        }
        return $group_mapper_presenters;
    }

    private function getIndexedUgroups(Project $project) {
        $ugroups        = array();
        $ugroup_manager = new UGroupManager();
        $excluded_groups = array_merge(ProjectUGroup::$legacy_ugroups, array(ProjectUGroup::NONE, ProjectUGroup::ANONYMOUS));
        if (! $project->isPublic()) {
            $excluded_groups = array_merge($excluded_groups, array(ProjectUGroup::REGISTERED));
        }
        $all_ugroups    = $ugroup_manager->getUGroups($project, $excluded_groups);
        foreach ($all_ugroups as $ugroup) {
            $ugroups[$ugroup->getId()] = $ugroup;
        }
        return $ugroups;
    }

    private function getGroupPresenters($mw_group_name, array $current_mapping, array $all_ugroups) {
        $mapped_groups    = array();
        $available_groups = array();
        foreach ($all_ugroups as $ugroup_id => $ugroup) {
            if (in_array($ugroup_id, $current_mapping[$mw_group_name])) {
                $mapped_groups[] = $ugroup;
            } else {
                $available_groups[] = $ugroup;
            }
        }
        return new MediawikiGroupPresenter(
            $mw_group_name,
            $GLOBALS['Language']->getText('plugin_mediawiki', 'group_name_'.$mw_group_name),
            $available_groups,
            $mapped_groups
        );
    }

    public function save(ServiceMediawiki $service, HTTPRequest $request) {
        $this->assertUserIsProjectAdmin($service, $request);
        if($request->isPost()) {
            $project          = $request->getProject();
            $new_mapping_list = $this->getSelectedMappingsFromRequest($request, $project);
            $this->mapper->saveMapping($new_mapping_list, $project);

            $options = $this->manager->getDefaultOptions();

            if (! $this->requestIsRestore($request)) {
                foreach (array_keys($this->manager->getDefaultOptions()) as $key) {
                    $options[$key] = $request->get($key);
                }
            }

            $this->manager->saveOptions($project, $options);

            if (! $this->requestIsRestore($request)) {

                $selected_read_ugroup = $request->get('read_ugroups');
                if ($selected_read_ugroup) {
                    $override_collection = new PermissionsNormalizerOverrideCollection();
                    $normalized_ids = $this->permissions_normalizer->getNormalizedUGroupIds($project, $selected_read_ugroup, $override_collection);

                    if ($this->manager->saveReadAccessControl($project, $normalized_ids)) {
                        $override_collection->emitFeedback(MediawikiManager::READ_ACCESS);
                    }
                }

                $selected_write_ugroup = $request->get('write_ugroups');
                if ($selected_write_ugroup) {
                    $override_collection = new PermissionsNormalizerOverrideCollection();
                    $normalized_ids = $this->permissions_normalizer->getNormalizedUGroupIds($project, $selected_write_ugroup, $override_collection);

                    if ($this->manager->saveWriteAccessControl($project, $normalized_ids)) {
                        $override_collection->emitFeedback(MediawikiManager::WRITE_ACCESS);
                    }
                }
            }

            if ($this->requestIsRestore($request)) {
                $GLOBALS['Response']->addFeedback(Feedback::INFO, $GLOBALS['Language']->getText('plugin_mediawiki', 'options_restored'));
            } else {
                $GLOBALS['Response']->addFeedback(Feedback::INFO, $GLOBALS['Language']->getText('plugin_mediawiki', 'options_saved'));
            }
        }

        $GLOBALS['Response']->redirect(MEDIAWIKI_BASE_URL .'/forge_admin?'. http_build_query(
            array(
                'group_id'   => $request->get('group_id'),
            )
        ));
    }

    private function getSelectedMappingsFromRequest(HTTPRequest $request, Project $project) {
        if ($this->requestIsRestore($request)) {
            return $this->mapper->getDefaultMappingsForProject($project);
        }

        $list = array();
        foreach(MediawikiUserGroupsMapper::$MEDIAWIKI_GROUPS_NAME as $mw_group_name) {
            $list[$mw_group_name] = array_filter(explode(',', $request->get('hidden_selected_'.$mw_group_name)));
        }
        return $list;
    }

    private function requestIsRestore(HTTPRequest $request) {
        return $request->get('restore') != null;
    }

    private function assertUserIsProjectAdmin(ServiceMediawiki $service, HTTPRequest $request) {
        if (! $service->userIsAdmin($request)) {
            $GLOBALS['Response']->redirect(MEDIAWIKI_BASE_URL.'/wiki/'.$request->getProject()->getUnixName());
        }
    }
}