<?php
/*
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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Date\TlpRelativeDatePresenterBuilder;
use Tuleap\GlobalLanguageMock;
use Tuleap\Reference\CrossReferencePresenter;
use Tuleap\Test\Builders\CrossReferencePresenterBuilder;

class GitlabCommitCrossReferenceEnhancerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var GitlabCommitCrossReferenceEnhancer
     */
    private $gitlab_cross_reference_enhancer;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\UserManager
     */
    private $user_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\UserHelper
     */
    private $user_helper;

    protected function setUp(): void
    {
        $this->user_manager                    = Mockery::mock(\UserManager::class);
        $this->user_helper                     = Mockery::mock(\UserHelper::class);
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
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('getPreference')->andReturn("relative_first-absolute_tooltip");
        $user->shouldReceive('getLocale')->andReturn("en_US");

        $committer = Mockery::mock(\PFUser::class)
            ->shouldReceive([
                'hasAvatar' => true,
                'getAvatarUrl' => "john_snow_avatar_url.png"
            ])
            ->getMock();

        $commit = $this->buildGitlabCommit(
            'John Snow',
            'john-snow@the-wall.com',
            'dev/feature'
        );

        $this->user_manager->shouldReceive('getUserByEmail')->andReturn($committer);
        $this->user_helper->shouldReceive('getDisplayNameFromUser')
            ->with($committer)
            ->andReturn("John Snow (jsnow)");

        $reference = $this->getCrossReferencePresenter();

        $enhanced_reference = $this->gitlab_cross_reference_enhancer->getCrossReferencePresenterWithCommitInformation(
            $reference,
            $commit,
            $user
        );

        $this->assertEquals('Increase blankets stocks for winter', $enhanced_reference->title);

        $this->assertCount(2, $enhanced_reference->additional_badges);
        $this->assertEquals('dev/feature', $enhanced_reference->additional_badges[0]->label);
        $this->assertEquals('14a9b6c0c0', $enhanced_reference->additional_badges[1]->label);

        $this->assertEquals('2020-12-21T14:00:18+01:00', $enhanced_reference->creation_metadata->created_on->date);
        $this->assertEquals('21/12/2020 14:00', $enhanced_reference->creation_metadata->created_on->absolute_date);
        $this->assertEquals('tooltip', $enhanced_reference->creation_metadata->created_on->placement);
        $this->assertEquals('relative', $enhanced_reference->creation_metadata->created_on->preference);
        $this->assertEquals('en_US', $enhanced_reference->creation_metadata->created_on->locale);

        $this->assertEquals('John Snow (jsnow)', $enhanced_reference->creation_metadata->created_by->display_name);
        $this->assertTrue($enhanced_reference->creation_metadata->created_by->has_avatar);
        $this->assertEquals('john_snow_avatar_url.png', $enhanced_reference->creation_metadata->created_by->avatar_url);
    }

    public function testItDisplaysCommitterNameAndDefaultTuleapAvatarWhenUserEmailMatchNoAccountOnTuleap(): void
    {
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('getPreference')->andReturn("relative_first-absolute_tooltip");
        $user->shouldReceive('getLocale')->andReturn("en_US");

        $commit = $this->buildGitlabCommit('The Night King', 'knight-king@is-comming.com', 'dev/feature');

        $this->user_manager->shouldReceive('getUserByEmail')->andReturn(null);
        $this->user_helper->shouldReceive('getDisplayNameFromUser')->never();

        $reference = $this->getCrossReferencePresenter();

        $enhanced_reference = $this->gitlab_cross_reference_enhancer->getCrossReferencePresenterWithCommitInformation(
            $reference,
            $commit,
            $user
        );

        $this->assertEquals('Increase blankets stocks for winter', $enhanced_reference->title);

        $this->assertCount(2, $enhanced_reference->additional_badges);
        $this->assertEquals('dev/feature', $enhanced_reference->additional_badges[0]->label);
        $this->assertEquals('14a9b6c0c0', $enhanced_reference->additional_badges[1]->label);

        $this->assertEquals('2020-12-21T14:00:18+01:00', $enhanced_reference->creation_metadata->created_on->date);
        $this->assertEquals('21/12/2020 14:00', $enhanced_reference->creation_metadata->created_on->absolute_date);
        $this->assertEquals('tooltip', $enhanced_reference->creation_metadata->created_on->placement);
        $this->assertEquals('relative', $enhanced_reference->creation_metadata->created_on->preference);
        $this->assertEquals('en_US', $enhanced_reference->creation_metadata->created_on->locale);

        $this->assertEquals('The Night King', $enhanced_reference->creation_metadata->created_by->display_name);
        $this->assertFalse($enhanced_reference->creation_metadata->created_by->has_avatar);
        $this->assertEquals('', $enhanced_reference->creation_metadata->created_by->avatar_url);
    }

    public function testItDoesNotDisplayTheBranchNameBadgeIfCommitBranchIsUnknown(): void
    {
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('getPreference')->andReturn("relative_first-absolute_tooltip");
        $user->shouldReceive('getLocale')->andReturn("en_US");

        $committer = Mockery::mock(\PFUser::class)
            ->shouldReceive([
                'hasAvatar' => true,
                'getAvatarUrl' => "john_snow_avatar_url.png"
            ])
            ->getMock();

        $commit = $this->buildGitlabCommit(
            'John Snow',
            'john-snow@the-wall.com',
            ''
        );

        $this->user_manager->shouldReceive('getUserByEmail')->andReturn($committer);
        $this->user_helper->shouldReceive('getDisplayNameFromUser')
            ->with($committer)
            ->andReturn("John Snow (jsnow)");

        $reference = $this->getCrossReferencePresenter();

        $enhanced_reference = $this->gitlab_cross_reference_enhancer->getCrossReferencePresenterWithCommitInformation(
            $reference,
            $commit,
            $user
        );

        $this->assertEquals('Increase blankets stocks for winter', $enhanced_reference->title);

        $this->assertCount(1, $enhanced_reference->additional_badges);
        $this->assertEquals('14a9b6c0c0', $enhanced_reference->additional_badges[0]->label);
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
