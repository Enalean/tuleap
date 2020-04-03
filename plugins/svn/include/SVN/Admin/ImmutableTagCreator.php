<?php
/**
 * Copyright Enalean (c) 2016 - 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\SVN\Admin;

use ProjectHistoryDao;
use Tuleap\SVN\Repository\ProjectHistoryFormatter;
use Tuleap\SVN\Repository\Repository;

class ImmutableTagCreator
{
    /**
     * @var ProjectHistoryFormatter
     */
    private $project_history_formatter;
    /**
     * @var ProjectHistoryDao
     */
    private $project_history_dao;
    /**
     * @var ImmutableTagFactory
     */
    private $immutable_tag_factory;
    /**
     * @var ImmutableTagDao
     */
    private $dao;

    public function __construct(
        ImmutableTagDao $dao,
        ProjectHistoryFormatter $project_history_formatter,
        ProjectHistoryDao $project_history_dao,
        ImmutableTagFactory $immutable_tag_factory
    ) {
        $this->dao                       = $dao;
        $this->project_history_formatter = $project_history_formatter;
        $this->project_history_dao       = $project_history_dao;
        $this->immutable_tag_factory     = $immutable_tag_factory;
    }

    public function save(Repository $repository, $immutable_tags_path, $immutable_tags_whitelist)
    {
        $this->saveWithoutHistory($repository, $immutable_tags_path, $immutable_tags_whitelist);

        $immutable_tags = $this->immutable_tag_factory->getByRepositoryId($repository);
        $history        = $this->project_history_formatter->getImmutableTagsHistory($immutable_tags);
        $this->project_history_dao->groupAddHistory(
            'svn_multi_repository_immutable_tags_update',
            "Repository: " . $repository->getName() . PHP_EOL . $history,
            $repository->getProject()->getID()
        );
    }

    public function saveWithoutHistory(Repository $repository, $immutable_tags_path, $immutable_tags_whitelist)
    {
        if (
            !$this->dao->save(
                $repository,
                $this->cleanImmutableTag($immutable_tags_path),
                $this->cleanImmutableTag($immutable_tags_whitelist)
            )
        ) {
            throw new CannotCreateImmuableTagException(
                dgettext('tuleap-svn', 'Unable to save immutable tags.')
            );
        }
    }

    private function cleanImmutableTag($immutable_tags_path)
    {
        $immutable_paths = explode(PHP_EOL, $immutable_tags_path);

        return implode(PHP_EOL, array_map('trim', $immutable_paths));
    }
}
