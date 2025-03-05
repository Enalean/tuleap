<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Gitlab\Reference;

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitlabReferenceValueWithoutSeparatorSplitValuesBuilderTest extends TestCase
{
    public function testItReturnsANullRepositoryNameAndANullSha1WhenTheStringDoesNotContainAPath(): void
    {
        $split_value = (new GitlabReferenceValueWithoutSeparatorSplitValuesBuilder())->splitRepositoryNameAndReferencedItemId(
            'a_string_with_no_path',
            101,
        );
        self::assertEquals(null, $split_value->getRepositoryName());
        self::assertEquals(null, $split_value->getValue());
    }

    public function testItReturnsTheRepositoryNameAndTheValueAfterTheLastSeparator(): void
    {
        $split_value = (new GitlabReferenceValueWithoutSeparatorSplitValuesBuilder())->splitRepositoryNameAndReferencedItemId(
            'root/repo01/14a9b6c0c0c965977cf2af2199f93df82afcdea3',
            101,
        );
        self::assertEquals('root/repo01', $split_value->getRepositoryName());
        self::assertEquals('14a9b6c0c0c965977cf2af2199f93df82afcdea3', $split_value->getValue());

        $split_value = (new GitlabReferenceValueWithoutSeparatorSplitValuesBuilder())->splitRepositoryNameAndReferencedItemId(
            'root/repo01/25',
            101,
        );
        self::assertEquals('root/repo01', $split_value->getRepositoryName());
        self::assertEquals('25', $split_value->getValue());

        $split_value = (new GitlabReferenceValueWithoutSeparatorSplitValuesBuilder())->splitRepositoryNameAndReferencedItemId(
            'root/repo01/dev/tuleap-25',
            101,
        );
        self::assertEquals('root/repo01/dev', $split_value->getRepositoryName());
        self::assertEquals('tuleap-25', $split_value->getValue());
    }
}
