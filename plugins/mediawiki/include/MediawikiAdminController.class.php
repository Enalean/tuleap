<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

class MediawikiAdminController //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{

    /** @var MediawikiUserGroupsMapper */
    private $mapper;

    /** @var MediawikiManager */
    private $manager;

    /** @var User_ForgeUserGroupFactory */
    private $user_group_factory;

    /** @var PermissionsNormalizer */
    private $permissions_normalizer;

    /** @var MediawikiLanguageManager */
    private $language_manager;

    public function __construct(MediawikiManager $manager)
    {
        $this->manager = $manager;

        $this->mapper = new MediawikiUserGroupsMapper(
            $this->manager->getDao(),
            new User_ForgeUserGroupPermissionsDao()
        );

        $user_dao                     = new UserGroupDao();
        $this->user_group_factory     = new User_ForgeUserGroupFactory($user_dao);
        $this->permissions_normalizer = new PermissionsNormalizer();
        $this->language_manager       = new MediawikiLanguageManager(new MediawikiLanguageDao());
    }

    public function index(ServiceMediawiki $service, HTTPRequest $request)
    {
        $this->assertUserIsProjectAdmin($service, $request);

        $assets = new \Tuleap\Layout\IncludeAssets(__DIR__ . '/../../../src/www/assets/mediawiki', '/assets/mediawiki');

        $GLOBALS['HTML']->includeFooterJavascriptFile($assets->getFileURL('admin.js'));

        $project = $request->getProject();

        $read_ugroups  = $this->getReadUGroups($project);
        $write_ugroups = $this->getWriteUGroups($project);

        switch ($request->get('pane')) {
            case 'language':
                if ($request->exist('nolang')) {
                    $GLOBALS['Response']->addFeedback(
                        Feedback::INFO,
                        $GLOBALS['Language']->getText('plugin_mediawiki', 'language_not_set_admin_warning')
                    );
                }
                $service->renderInPage(
                    $GLOBALS['Language']->getText('global', 'Administration'),
                    'language-pane-admin',
                    new MediawikiAdminLanguagePanePresenter(
                        $project,
                        $this->language_manager->getAvailableLanguagesWithUsage($project)
                    )
                );
                break;
            case 'permissions':
            default:
                $service->renderInPage(
                    $GLOBALS['Language']->getText('global', 'Administration'),
                    'permissions-pane-admin',
                    new MediawikiAdminPermissionsPanePresenter(
                        $project,
                        $this->getMappedGroupPresenter($project),
                        $this->manager->isCompatibilityViewEnabled($project),
                        $read_ugroups,
                        $write_ugroups
                    )
                );
                break;
        }
    }

    private function getReadUGroups(Project $project)
    {
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

    private function getWriteUGroups(Project $project)
    {
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

    private function getMappedGroupPresenter(Project $project)
    {
        $group_mapper_presenters = array();
        $current_mapping = $this->mapper->getCurrentUserGroupMapping($project);
        $all_ugroups     = $this->getIndexedUgroups($project);
        foreach (MediawikiUserGroupsMapper::$MEDIAWIKI_MODIFIABLE_GROUP_NAMES as $mw_group_name) {
            $group_mapper_presenters[] = $this->getGroupPresenters($mw_group_name, $current_mapping, $all_ugroups);
        }
        return $group_mapper_presenters;
    }

    private function getIndexedUgroups(Project $project)
    {
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

    private function getGroupPresenters($mw_group_name, array $current_mapping, array $all_ugroups)
    {
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
            $this->getGroupName($mw_group_name),
            $available_groups,
            $mapped_groups
        );
    }

    private function getGroupName($mw_group_name): string
    {
        switch ($mw_group_name) {
            case 'user':
                return $GLOBALS['Language']->getText('plugin_mediawiki', 'group_name_user');
            case 'bot':
                return $GLOBALS['Language']->getText('plugin_mediawiki', 'group_name_bot');
            case 'sysop':
                return $GLOBALS['Language']->getText('plugin_mediawiki', 'group_name_sysop');
            case 'bureaucrat':
                return $GLOBALS['Language']->getText('plugin_mediawiki', 'group_name_bureaucrat');
            case 'anonymous':
            default:
                return $GLOBALS['Language']->getText('plugin_mediawiki', 'group_name_anonymous');
        }
    }

    public function save_language(ServiceMediawiki $service, HTTPRequest $request) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->assertUserIsProjectAdmin($service, $request);
        if ($request->isPost()) {
            $project  = $request->getProject();
            $language = $request->get('language');

            try {
                $this->language_manager->saveLanguageOption($project, $language);
            } catch (Mediawiki_UnsupportedLanguageException $exception) {
                $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('plugin_mediawiki', 'unsupported_language', array($exception->getLanguage())));
            }
        }

        $GLOBALS['Response']->redirect(MEDIAWIKI_BASE_URL . '/forge_admin.php?' . http_build_query(
            array(
                'group_id'   => $request->get('group_id'),
                'pane'       => 'language',
            )
        ));
    }

    public function save_permissions(ServiceMediawiki $service, HTTPRequest $request) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->assertUserIsProjectAdmin($service, $request);
        if ($request->isPost()) {
            $project          = $request->getProject();
            $new_mapping_list = $this->getSelectedMappingsFromRequest($request, $project);
            $this->mapper->saveMapping($new_mapping_list, $project);

            $this->manager->saveCompatibilityViewOption($project, $request->get('enable_compatibility_view'));

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

        $GLOBALS['Response']->redirect(MEDIAWIKI_BASE_URL . '/forge_admin.php?' . http_build_query(
            array(
                'group_id'   => $request->get('group_id'),
            )
        ));
    }

    private function getSelectedMappingsFromRequest(HTTPRequest $request, Project $project)
    {
        if ($this->requestIsRestore($request)) {
            return $this->mapper->getDefaultMappingsForProject($project);
        }

        $list = array();
        foreach (MediawikiUserGroupsMapper::$MEDIAWIKI_GROUPS_NAME as $mw_group_name) {
            $list[$mw_group_name] = array_filter(explode(',', $request->get('hidden_selected_' . $mw_group_name)));
        }
        return $list;
    }

    private function requestIsRestore(HTTPRequest $request)
    {
        return $request->get('restore') != null;
    }

    private function assertUserIsProjectAdmin(ServiceMediawiki $service, HTTPRequest $request)
    {
        if (! $service->userIsAdmin($request->getCurrentUser())) {
            $GLOBALS['Response']->redirect(MEDIAWIKI_BASE_URL . '/wiki/' . $request->getProject()->getUnixName());
        }
    }
}
