<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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

require_once __DIR__.'/bootstrap.php';

Mock::generate('Git_Backend_Gitolite');
Mock::generate('GitViews');
Mock::generate('PFUser');

class GitViewsRepositoriesTraversalStrategy_SelectboxTest extends TuleapTestCase
{

    public function testEmptyListShouldReturnEmptyString()
    {
        $view = new MockGitViews();
        $user = mock('PFUser');
        $repositories = array();
        $strategy = new GitViewsRepositoriesTraversalStrategy_Selectbox($view);
        $this->assertIdentical('', $strategy->fetch($repositories, $user));
    }

    public function testFlatTreeShouldReturnRepresentation()
    {
        $view = new MockGitViews();
        $user = mock('PFUser');
        $strategy = TestHelper::getPartialMock('GitViewsRepositoriesTraversalStrategy_Selectbox', array('getRepository'));

        $repositories    = $this->getFlatTree($strategy);
        $expectedPattern = $this->getExpectedPattern($repositories);

        $strategy->__construct($view);
        $this->assertPattern('`'. $expectedPattern .'`', $strategy->fetch($repositories, $user));
    }

    public function getExpectedPattern($repositories)
    {
        $nb_repositories = count($repositories);
        foreach ($repositories as $r) {
            if ($r['repository_backend_type'] == 'gitshell') {
                $nb_repositories--;
            }
        }
        $li_regexp_for_repository_representation = '<option value="(?P<value>[^"]*)">(?P<repo>[^\(<]*)</option>';

        $pattern = sprintf('<select (?P<args>[^>]*)>(?:%s){%d}</select>', $li_regexp_for_repository_representation, $nb_repositories);
        return $pattern;
    }

    public function testRepoIDShouldBeTheValue()
    {
        $view = new MockGitViews();
        $user = mock('PFUser');
        $strategy = TestHelper::getPartialMock('GitViewsRepositoriesTraversalStrategy_Selectbox', array('getRepository'));

        $repositories    = $this->getFlatTree($strategy);
        $a_repository_id = 4;

        $strategy->__construct($view);
        $this->assertPattern('`value="'. $a_repository_id .'"`', $strategy->fetch($repositories, $user));
    }

    private function getFlatTree($strategy): array
    {
        //go find the variable $repositories
        $repositories = $this->getFLatTreeOfRepositories();
        foreach ($repositories as $row) {
            /** @var GitRepository $repository */
            $repository = partial_mock('GitRepository', array('userCanRead'));
            $repository->setId($row['repository_id']);
            $repository->setName($row['repository_name']);
            $repository->setDescription($row['repository_description']);
            stub($repository)->userCanRead()->returns(true);
            if ($row['repository_backend_type'] == 'gitolite') {
                $repository->setBackend(mock('Git_Backend_Gitolite'));
            } else {
                $repository->setBackend(mock('GitBackend'));
            }
            $strategy->setReturnValue('getRepository', $repository, array($row));
        }
        return $repositories;
    }

    private function getFLatTreeOfRepositories(): array
    {
        /**
         *
         * Git
         * |-- abc
         * |-- automaticTests
         * |   |-- Python
         * |   `-- Ruby
         * |-- deps
         * |   `-- 3rdparty
         * |       |-- cvsgraph
         * |       |-- geshi
         * |       `-- gitolite
         * `-- tools
         * `-- lxc
         *
         */
        return array(
            2 =>
                array (
                    'repository_id' => '2',
                    'repository_name' => 'abc',
                    'repository_namespace' => '',
                    'repository_description' => '-- Default description --',
                    'repository_path' => 'ngt/abc.git',
                    'repository_parent_id' => '0',
                    'project_id' => '101',
                    'repository_creation_user_id' => '102',
                    'repository_creation_date' => '2011-12-06 17:24:58',
                    'repository_deletion_date' => '0000-00-00 00:00:00',
                    'repository_is_initialized' => '0',
                    'repository_access' => 'private',
                    'repository_events_mailing_prefix' => '[SCM]',
                    'repository_backend_type' => 'gitolite',
                ),
            3 =>
                array (
                    'repository_id' => '3',
                    'repository_name' => 'Python',
                    'repository_namespace' => 'automaticTests',
                    'repository_description' => '-- Default description --',
                    'repository_path' => 'ngt/automaticTests/Python.git',
                    'repository_parent_id' => '0',
                    'project_id' => '101',
                    'repository_creation_user_id' => '102',
                    'repository_creation_date' => '2011-12-06 17:24:58',
                    'repository_deletion_date' => '0000-00-00 00:00:00',
                    'repository_is_initialized' => '0',
                    'repository_access' => 'private',
                    'repository_events_mailing_prefix' => '[SCM]',
                    'repository_backend_type' => 'gitolite',
                ),
            4 =>
                array (
                    'repository_id' => '4',
                    'repository_name' => 'Ruby',
                    'repository_namespace' => 'automaticTests',
                    'repository_description' => '-- Default description --',
                    'repository_path' => 'ngt/automaticTests/Ruby.git',
                    'repository_parent_id' => '0',
                    'project_id' => '101',
                    'repository_creation_user_id' => '102',
                    'repository_creation_date' => '2011-12-06 17:25:06',
                    'repository_deletion_date' => '0000-00-00 00:00:00',
                    'repository_is_initialized' => '0',
                    'repository_access' => 'private',
                    'repository_events_mailing_prefix' => '[SCM]',
                    'repository_backend_type' => 'gitolite',
                ),
            5 =>
                array (
                    'repository_id' => '5',
                    'repository_name' => 'cvsgraph',
                    'repository_namespace' => 'deps/3rdparty',
                    'repository_description' => '-- Default description --',
                    'repository_path' => 'ngt/deps/3rdparty/cvsgraph.git',
                    'repository_parent_id' => '0',
                    'project_id' => '101',
                    'repository_creation_user_id' => '102',
                    'repository_creation_date' => '2011-12-06 17:25:14',
                    'repository_deletion_date' => '0000-00-00 00:00:00',
                    'repository_is_initialized' => '0',
                    'repository_access' => 'private',
                    'repository_events_mailing_prefix' => '[SCM]',
                    'repository_backend_type' => 'gitolite',
                ),
            6 =>
                array (
                    'repository_id' => '6',
                    'repository_name' => 'geshi',
                    'repository_namespace' => 'deps/3rdparty',
                    'repository_description' => '-- Default description --',
                    'repository_path' => 'ngt/deps/3rdparty/geshi.git',
                    'repository_parent_id' => '0',
                    'project_id' => '101',
                    'repository_creation_user_id' => '102',
                    'repository_creation_date' => '2011-12-06 17:25:23',
                    'repository_deletion_date' => '0000-00-00 00:00:00',
                    'repository_is_initialized' => '0',
                    'repository_access' => 'private',
                    'repository_events_mailing_prefix' => '[SCM]',
                    'repository_backend_type' => 'gitolite',
                ),
            7 =>
                array (
                    'repository_id' => '7',
                    'repository_name' => 'gitolite',
                    'repository_namespace' => 'deps/3rdparty',
                    'repository_description' => '-- Default description --',
                    'repository_path' => 'ngt/deps/3rdparty/gitolite.git',
                    'repository_parent_id' => '0',
                    'project_id' => '101',
                    'repository_creation_user_id' => '102',
                    'repository_creation_date' => '2011-12-06 17:25:33',
                    'repository_deletion_date' => '0000-00-00 00:00:00',
                    'repository_is_initialized' => '0',
                    'repository_access' => 'private',
                    'repository_events_mailing_prefix' => '[SCM]',
                    'repository_backend_type' => 'gitolite',
                ),
            8 =>
                array (
                    'repository_id' => '8',
                    'repository_name' => 'lxc/tools',
                    'repository_namespace' => '',
                    'repository_description' => '-- Default description --',
                    'repository_path' => 'ngt/tools/lxc.git',
                    'repository_parent_id' => '0',
                    'project_id' => '101',
                    'repository_creation_user_id' => '102',
                    'repository_creation_date' => '2011-12-06 17:25:46',
                    'repository_deletion_date' => '0000-00-00 00:00:00',
                    'repository_is_initialized' => '0',
                    'repository_access' => 'private',
                    'repository_events_mailing_prefix' => '[SCM]',
                    'repository_backend_type' => 'gitolite',
                ),
            9 =>
                array (
                    'repository_id' => '9',
                    'repository_name' => 'gitshell',
                    'repository_namespace' => '',
                    'repository_description' => '-- Default description --',
                    'repository_path' => 'ngt/gitshell.git',
                    'repository_parent_id' => '0',
                    'project_id' => '101',
                    'repository_creation_user_id' => '102',
                    'repository_creation_date' => '2011-12-06 17:25:46',
                    'repository_deletion_date' => '0000-00-00 00:00:00',
                    'repository_is_initialized' => '0',
                    'repository_access' => 'private',
                    'repository_events_mailing_prefix' => '[SCM]',
                    'repository_backend_type' => 'gitshell',
                ),
        );
    }
}
