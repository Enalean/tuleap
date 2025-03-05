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
 */

declare(strict_types=1);

namespace Tuleap\Git\Reference;

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Date\TlpRelativeDatePresenterBuilder;
use Tuleap\Git\GitPHP\Commit;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\CrossReferencePresenterBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\User\UserEmailCollection;
use UserHelper;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CrossReferenceGitEnhancerTest extends TestCase
{
    use GlobalLanguageMock;

    private UserHelper&MockObject $user_helper;
    private CrossReferenceGitEnhancer $enhancer;
    private PFUser $user;
    private Commit&MockObject $commit;

    protected function setUp(): void
    {
        $this->user_helper = $this->createMock(UserHelper::class);

        $this->enhancer = new CrossReferenceGitEnhancer(
            $this->user_helper,
            new TlpRelativeDatePresenterBuilder(),
        );

        $this->user = $this->createMock(PFUser::class);
        $this->user->method('getPreference')->willReturn('relative_first-absolute_tooltip');
        $this->user->method('getLocale')->willReturn('en_US');

        $this->commit = $this->createMock(Commit::class);

        $GLOBALS['Language']
            ->method('getText')
            ->with('system', 'datefmt')
            ->willReturn('d/m/Y H:i');
    }

    public function testItDisplaysCommitTitleAsXRefTitle(): void
    {
        $ref = CrossReferencePresenterBuilder::get(1)->build();

        $new_ref = $this->enhancer->getCrossReferencePresenterWithCommitInformation(
            $ref,
            new CommitDetails(
                '1a2b3c4d5e6f7g8h9i',
                'Add foo to stuff',
                '',
                '',
                'jdoe@example.com',
                'John Doe',
                1234567890
            ),
            $this->user,
            new UserEmailCollection(),
        );

        self::assertEquals(
            'Add foo to stuff',
            $new_ref->title,
        );
    }

    public function testItDisplaysOnlyCommitSha1AsAdditionalBadgeWhenThereIsNoBranchNorTag(): void
    {
        $ref = CrossReferencePresenterBuilder::get(1)->build();

        $new_ref = $this->enhancer->getCrossReferencePresenterWithCommitInformation(
            $ref,
            new CommitDetails(
                '1a2b3c4d5e6f7g8h9i',
                'Add foo to stuff',
                '',
                '',
                'jdoe@example.com',
                'John Doe',
                1234567890
            ),
            $this->user,
            new UserEmailCollection(),
        );

        self::assertCount(1, $new_ref->additional_badges);
        self::assertEquals('1a2b3c4d5e', $new_ref->additional_badges[0]->label);
        self::assertFalse($new_ref->additional_badges[0]->is_plain);
        self::assertFalse($new_ref->additional_badges[0]->is_primary);
    }

    public function testItDisplaysCommitSha1AndFirstBranchAsAdditionalBadges(): void
    {
        $ref = CrossReferencePresenterBuilder::get(1)->build();

        $new_ref = $this->enhancer->getCrossReferencePresenterWithCommitInformation(
            $ref,
            new CommitDetails(
                '1a2b3c4d5e6f7g8h9i',
                'Add foo to stuff',
                'dev-feature',
                'v1.2.0',
                'jdoe@example.com',
                'John Doe',
                1234567890
            ),
            $this->user,
            new UserEmailCollection(),
        );

        self::assertCount(2, $new_ref->additional_badges);

        self::assertEquals('dev-feature', $new_ref->additional_badges[0]->label);
        self::assertFalse($new_ref->additional_badges[0]->is_plain);
        self::assertTrue($new_ref->additional_badges[0]->is_primary);

        self::assertEquals('1a2b3c4d5e', $new_ref->additional_badges[1]->label);
        self::assertFalse($new_ref->additional_badges[1]->is_plain);
        self::assertFalse($new_ref->additional_badges[1]->is_primary);
    }

    public function testItDisplaysCommitSha1AndFirstTagAsAdditionalBadgesWhenThereIsNoBranch(): void
    {
        $ref = CrossReferencePresenterBuilder::get(1)->build();

        $new_ref = $this->enhancer->getCrossReferencePresenterWithCommitInformation(
            $ref,
            new CommitDetails(
                '1a2b3c4d5e6f7g8h9i',
                'Add foo to stuff',
                '',
                'v1.2.0',
                'jdoe@example.com',
                'John Doe',
                1234567890
            ),
            $this->user,
            new UserEmailCollection(),
        );

        self::assertCount(2, $new_ref->additional_badges);

        self::assertEquals('v1.2.0', $new_ref->additional_badges[0]->label);
        self::assertTrue($new_ref->additional_badges[0]->is_plain);
        self::assertTrue($new_ref->additional_badges[0]->is_primary);

        self::assertEquals('1a2b3c4d5e', $new_ref->additional_badges[1]->label);
        self::assertFalse($new_ref->additional_badges[1]->is_plain);
        self::assertFalse($new_ref->additional_badges[1]->is_primary);
    }

    public function testItAddCreationDateAsMetadata(): void
    {
        $ref = CrossReferencePresenterBuilder::get(1)->build();

        $author = UserTestBuilder::aUser()
            ->withEmail('jdoe@example.com')
            ->withAvatarUrl('/path/to/avatar')
            ->build();

        $this->user_helper
            ->method('getDisplayNameFromUser')
            ->with($author)
            ->willReturn('John Doe');

        $new_ref = $this->enhancer->getCrossReferencePresenterWithCommitInformation(
            $ref,
            new CommitDetails(
                '1a2b3c4d5e6f7g8h9i',
                'Add foo to stuff',
                '',
                '',
                'jdoe@example.com',
                'John Doe',
                1234567890
            ),
            $this->user,
            new UserEmailCollection($author),
        );

        self::assertEquals('2009-02-14T00:31:30+01:00', $new_ref->creation_metadata->created_on->date);
        self::assertEquals('14/02/2009 00:31', $new_ref->creation_metadata->created_on->absolute_date);
        self::assertEquals('tooltip', $new_ref->creation_metadata->created_on->placement);
        self::assertEquals('relative', $new_ref->creation_metadata->created_on->preference);
        self::assertEquals('en_US', $new_ref->creation_metadata->created_on->locale);
    }

    public function testItUsesTuleapUserInformationForAuthor(): void
    {
        $ref = CrossReferencePresenterBuilder::get(1)->build();

        $author = UserTestBuilder::aUser()
            ->withEmail('jdoe@example.com')
            ->withAvatarUrl('/path/to/avatar')
            ->build();

        $this->user_helper
            ->method('getDisplayNameFromUser')
            ->with($author)
            ->willReturn('John Doe');

        $new_ref = $this->enhancer->getCrossReferencePresenterWithCommitInformation(
            $ref,
            new CommitDetails(
                '1a2b3c4d5e6f7g8h9i',
                'Add foo to stuff',
                '',
                '',
                'jdoe@example.com',
                'John Doe',
                1234567890
            ),
            $this->user,
            new UserEmailCollection($author),
        );

        self::assertEquals('John Doe', $new_ref->creation_metadata->created_by->display_name);
        self::assertTrue($new_ref->creation_metadata->created_by->has_avatar);
        self::assertEquals('/path/to/avatar', $new_ref->creation_metadata->created_by->avatar_url);
    }

    public function testItUsesCommitAuthorWhenMetadataDoesNotContainTuleapUser(): void
    {
        $ref = CrossReferencePresenterBuilder::get(1)->build();

        $this->commit->method('GetAuthorName')->willReturn('Korben Dallas');

        $new_ref = $this->enhancer->getCrossReferencePresenterWithCommitInformation(
            $ref,
            new CommitDetails(
                '1a2b3c4d5e6f7g8h9i',
                'Add foo to stuff',
                '',
                '',
                'korben@example.com',
                'Korben Dallas',
                1234567890
            ),
            $this->user,
            new UserEmailCollection(),
        );

        self::assertEquals('Korben Dallas', $new_ref->creation_metadata->created_by->display_name);
        self::assertFalse($new_ref->creation_metadata->created_by->has_avatar);
        self::assertEquals('', $new_ref->creation_metadata->created_by->avatar_url);
    }
}
