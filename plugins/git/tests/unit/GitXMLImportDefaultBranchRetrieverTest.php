<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Git;

use Tuleap\Test\PHPUnit\TestCase;

final class GitXMLImportDefaultBranchRetrieverTest extends TestCase
{
    public function testItReturnsEmptyIfNoBranchInRepository(): void
    {
        $xml_content = new \SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <repository name="repo01" description="-- Default description --" allow_artifact_closure="1" default_branch="main" bundle-path="export/repository-186.bundle"/>
            EOS
        );

        $git_exec = new class ('') extends \Git_Exec {
            public function getAllBranchesSortedByCreationDate(): array
            {
                return [];
            }
        };

        $default_branch = (new GitXMLImportDefaultBranchRetriever())->retrieveDefaultBranchFromXMLContent(
            $git_exec,
            $xml_content,
        );

        self::assertSame(
            "",
            $default_branch,
        );
    }

    public function testItReturnsLegacyDefaultBranchNameIfOnlyAvailableWithoutXMLDefaultBranch(): void
    {
        $xml_content = new \SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <repository name="repo01" description="-- Default description --" allow_artifact_closure="1" bundle-path="export/repository-186.bundle"/>
            EOS
        );

        $git_exec = new class ('') extends \Git_Exec {
            public function getAllBranchesSortedByCreationDate(): array
            {
                return ['test', 'master', 'another'];
            }
        };

        $default_branch = (new GitXMLImportDefaultBranchRetriever())->retrieveDefaultBranchFromXMLContent(
            $git_exec,
            $xml_content,
        );

        self::assertSame(
            "master",
            $default_branch,
        );
    }

    public function testItReturnsDefaultBranchNameIfAvailableWithoutXMLDefaultBranch(): void
    {
        $xml_content = new \SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <repository name="repo01" description="-- Default description --" allow_artifact_closure="1" bundle-path="export/repository-186.bundle"/>
            EOS
        );

        $git_exec = new class ('') extends \Git_Exec {
            public function getAllBranchesSortedByCreationDate(): array
            {
                return ['test', 'master', 'main', 'another'];
            }
        };

        $default_branch = (new GitXMLImportDefaultBranchRetriever())->retrieveDefaultBranchFromXMLContent(
            $git_exec,
            $xml_content,
        );

        self::assertSame(
            "main",
            $default_branch,
        );
    }

    public function testItReturnsFirstBranchFoundIfProvidedXMLDefaultBranchNotInRepositoryAndNoDefaultBranches(): void
    {
        $xml_content = new \SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <repository name="repo01" description="-- Default description --" allow_artifact_closure="1" default_branch="branch01" bundle-path="export/repository-186.bundle"/>
            EOS
        );

        $git_exec = new class ('') extends \Git_Exec {
            public function getAllBranchesSortedByCreationDate(): array
            {
                return ['test', 'another'];
            }
        };

        $default_branch = (new GitXMLImportDefaultBranchRetriever())->retrieveDefaultBranchFromXMLContent(
            $git_exec,
            $xml_content,
        );

        self::assertSame(
            "test",
            $default_branch,
        );
    }

    public function testItReturnsProvidedXMLDefaultBranch(): void
    {
        $xml_content = new \SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <repository name="repo01" description="-- Default description --" allow_artifact_closure="1" default_branch="branch01" bundle-path="export/repository-186.bundle"/>
            EOS
        );

        $git_exec = new class ('') extends \Git_Exec {
            public function getAllBranchesSortedByCreationDate(): array
            {
                return ['test', 'main', 'branch01', 'another'];
            }
        };

        $default_branch = (new GitXMLImportDefaultBranchRetriever())->retrieveDefaultBranchFromXMLContent(
            $git_exec,
            $xml_content,
        );

        self::assertSame(
            "branch01",
            $default_branch,
        );
    }
}
