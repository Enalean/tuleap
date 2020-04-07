<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1;

use Docman_PermissionsManager;
use Docman_VersionFactory;
use Project;
use Tuleap\Docman\ApprovalTable\ApprovalTableRetriever;
use Tuleap\Docman\ApprovalTable\ApprovalTableUpdateActionChecker;

final class DocumentBeforeVersionCreationValidatorVisitorBuilder
{
    public static function build(Project $project): DocumentBeforeVersionCreationValidatorVisitor
    {
        $docman_approval_table_retriever = new ApprovalTableRetriever(
            new \Docman_ApprovalTableFactoriesFactory(),
            new Docman_VersionFactory()
        );
        $approval_check                  = new ApprovalTableUpdateActionChecker($docman_approval_table_retriever);
        return new DocumentBeforeVersionCreationValidatorVisitor(
            Docman_PermissionsManager::instance($project->getGroupId()),
            $approval_check,
            new \Docman_ItemFactory(),
            $docman_approval_table_retriever
        );
    }
}
