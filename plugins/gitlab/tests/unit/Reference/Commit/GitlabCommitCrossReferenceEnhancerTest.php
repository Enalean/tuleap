<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Reference\Commit;

use Tuleap\Date\TlpRelativeDatePresenterBuilder;
use Tuleap\GlobalLanguageMock;
use Tuleap\Reference\CrossReferencePresenter;
use Tuleap\Test\Builders\CrossReferencePresenterBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class GitlabCommitCrossReferenceEnhancerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    /**
     * @var GitlabCommitCrossReferenceEnhancer
     */
    private $gitlab_cross_reference_enhancer;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\UserManager
     */
    private $user_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\UserHelper
     */
    private $user_helper;

    protected function setUp(): void
    {
        $this->user_manager                    = $this->createMock(\UserManager::class);
        $this->user_helper                     = $this->createMock(\UserHelper::class);
        $this->gitlab_cross_reference_enhancer = new GitlabCommitCrossReferenceEnhancer(
            $this->user_manager,
            $this->user_helper,
            new TlpRelativeDatePresenterBuilder()
        );

        $GLOBALS['Language']
            ->method('getText')
            ->with('system', 'datefmt')
            ->willReturn('d/m/Y H:i');
    }

    public function testItEnhancesTheReferenceAndRetrievesTheCommitterAccountOnTuleap(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('getPreference')->willReturn('relative_first-absolute_tooltip');
        $user->method('getLocale')->willReturn('en_US');

        $committer = $this->createMock(\PFUser::class);
        $committer->method('hasAvatar')->willReturn(true);
        $committer->method('getAvatarUrl')->willReturn('john_snow_avatar_url.png');

        $commit = $this->buildGitlabCommit(
            'John Snow',
            'john-snow@example.com',
            'dev/feature'
        );

        $this->user_manager->method('getUserByEmail')->willReturn($committer);
        $this->user_helper->method('getDisplayNameFromUser')
            ->with($committer)
            ->willReturn('John Snow (jsnow)');

        $reference = $this->getCrossReferencePresenter();

        $enhanced_reference = $this->gitlab_cross_reference_enhancer->getCrossReferencePresenterWithCommitInformation(
            $reference,
            $commit,
            $user
        );

        self::assertEquals('Increase blankets stocks for winter', $enhanced_reference->title);

        self::assertCount(2, $enhanced_reference->additional_badges);
        self::assertEquals('dev/feature', $enhanced_reference->additional_badges[0]->label);
        self::assertEquals('14a9b6c0c0', $enhanced_reference->additional_badges[1]->label);

        self::assertNotNull($enhanced_reference->creation_metadata);

        self::assertEquals('2020-12-21T14:00:18+01:00', $enhanced_reference->creation_metadata->created_on->date);
        self::assertEquals('21/12/2020 14:00', $enhanced_reference->creation_metadata->created_on->absolute_date);
        self::assertEquals('tooltip', $enhanced_reference->creation_metadata->created_on->placement);
        self::assertEquals('relative', $enhanced_reference->creation_metadata->created_on->preference);
        self::assertEquals('en_US', $enhanced_reference->creation_metadata->created_on->locale);

        self::assertNotNull($enhanced_reference->creation_metadata->created_by);

        self::assertEquals('John Snow (jsnow)', $enhanced_reference->creation_metadata->created_by->display_name);
        self::assertTrue($enhanced_reference->creation_metadata->created_by->has_avatar);
        self::assertEquals('john_snow_avatar_url.png', $enhanced_reference->creation_metadata->created_by->avatar_url);
    }

    public function testItDisplaysCommitterNameAndDefaultTuleapAvatarWhenUserEmailMatchNoAccountOnTuleap(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('getPreference')->willReturn('relative_first-absolute_tooltip');
        $user->method('getLocale')->willReturn('en_US');

        $commit = $this->buildGitlabCommit('The Night King', 'knight-king@is-comming.com', 'dev/feature');

        $this->user_manager->method('getUserByEmail')->willReturn(null);
        $this->user_helper->expects($this->never())->method('getDisplayNameFromUser');

        $reference = $this->getCrossReferencePresenter();

        $enhanced_reference = $this->gitlab_cross_reference_enhancer->getCrossReferencePresenterWithCommitInformation(
            $reference,
            $commit,
            $user
        );

        self::assertEquals('Increase blankets stocks for winter', $enhanced_reference->title);

        self::assertCount(2, $enhanced_reference->additional_badges);
        self::assertEquals('dev/feature', $enhanced_reference->additional_badges[0]->label);
        self::assertEquals('14a9b6c0c0', $enhanced_reference->additional_badges[1]->label);

        self::assertNotNull($enhanced_reference->creation_metadata);

        self::assertEquals('2020-12-21T14:00:18+01:00', $enhanced_reference->creation_metadata->created_on->date);
        self::assertEquals('21/12/2020 14:00', $enhanced_reference->creation_metadata->created_on->absolute_date);
        self::assertEquals('tooltip', $enhanced_reference->creation_metadata->created_on->placement);
        self::assertEquals('relative', $enhanced_reference->creation_metadata->created_on->preference);
        self::assertEquals('en_US', $enhanced_reference->creation_metadata->created_on->locale);

        self::assertNotNull($enhanced_reference->creation_metadata->created_by);

        self::assertEquals('The Night King', $enhanced_reference->creation_metadata->created_by->display_name);
        self::assertFalse($enhanced_reference->creation_metadata->created_by->has_avatar);
        self::assertEquals('', $enhanced_reference->creation_metadata->created_by->avatar_url);
    }

    public function testItDoesNotDisplayTheBranchNameBadgeIfCommitBranchIsUnknown(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('getPreference')->willReturn('relative_first-absolute_tooltip');
        $user->method('getLocale')->willReturn('en_US');

        $committer = $this->createMock(\PFUser::class);
        $committer->method('hasAvatar')->willReturn(true);
        $committer->method('getAvatarUrl')->willReturn('john_snow_avatar_url');

        $commit = $this->buildGitlabCommit(
            'John Snow',
            'john-snow@example.com',
            ''
        );

        $this->user_manager->method('getUserByEmail')->willReturn($committer);
        $this->user_helper->method('getDisplayNameFromUser')
            ->with($committer)
            ->willReturn('John Snow (jsnow)');

        $reference = $this->getCrossReferencePresenter();

        $enhanced_reference = $this->gitlab_cross_reference_enhancer->getCrossReferencePresenterWithCommitInformation(
            $reference,
            $commit,
            $user
        );

        self::assertEquals('Increase blankets stocks for winter', $enhanced_reference->title);

        self::assertCount(1, $enhanced_reference->additional_badges);
        self::assertEquals('14a9b6c0c0', $enhanced_reference->additional_badges[0]->label);
    }

    private function buildGitlabCommit(string $committer_name, string $committer_email, string $branch_name): GitlabCommit
    {
        return new GitlabCommit(
            '14a9b6c0c0c965977cf2af2199f93df82afcdea3',
            1608555618,
            'Increase blankets stocks for winter',
            $branch_name,
            $committer_name,
            $committer_email,
        );
    }

    private function getCrossReferencePresenter(): CrossReferencePresenter
    {
        return CrossReferencePresenterBuilder::get(1)
            ->withProjectId(1)
            ->withType(GitlabCommitReference::NATURE_NAME)
            ->withValue('john-snow/winter-is-coming/14a9b6c0c0c965977cf2af2199f93df82afcdea3')
            ->build();
    }
}
