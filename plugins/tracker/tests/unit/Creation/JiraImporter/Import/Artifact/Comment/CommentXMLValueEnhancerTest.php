<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\ActiveJiraCloudUser;
use Tuleap\Tracker\XML\Importer\TrackerImporterUser;

#[DisableReturnValueGenerationForTestDoubles]
final class CommentXMLValueEnhancerTest extends TestCase
{
    public function testItAddsTheNameOfTheJiraUserWhoAddTheCommentInTheContent(): void
    {
        $enhancer  = new CommentXMLValueEnhancer();
        $commenter = UserTestBuilder::buildWithId(TrackerImporterUser::ID);

        $comment = new JiraCloudComment(
            new ActiveJiraCloudUser([
                'displayName' => 'userO1',
                'accountId'   => 'e12ds5123sw',
            ]),
            new DateTimeImmutable(),
            '<p>Comment 01</p>'
        );

        self::assertSame(
            'userO1 said: <br/><br/><p>Comment 01</p>',
            $enhancer->getEnhancedValueWithCommentWriterInformation($comment, $commenter)
        );
    }

    public function testItReturnsOnlyTheCommentValueWhenAuthorHasBeenIdentifiedOnTuleapSide(): void
    {
        $enhancer  = new CommentXMLValueEnhancer();
        $commenter = UserTestBuilder::buildWithId(105);

        $update_author = new ActiveJiraCloudUser(
            [
                'displayName'  => 'userO1',
                'accountId'    => 'e12ds5123sw',
                'emailAddress' => 'user01@example.com',
            ]
        );

        $comment = new JiraCloudComment(
            $update_author,
            new DateTimeImmutable(),
            '<p>Comment 01</p>'
        );

        self::assertSame(
            '<p>Comment 01</p>',
            $enhancer->getEnhancedValueWithCommentWriterInformation($comment, $commenter)
        );
    }
}
