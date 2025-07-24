<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Reference\Branch;

use DateTimeImmutable;
use PFUser;
use Tuleap\Date\TlpRelativeDatePresenterBuilder;
use Tuleap\GlobalLanguageMock;
use Tuleap\Reference\CrossReferencePresenter;
use Tuleap\Test\Builders\CrossReferencePresenterBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class GitlabBranchCrossReferenceEnhancerTest extends TestCase
{
    use GlobalLanguageMock;

    private GitlabBranchCrossReferenceEnhancer $enhancer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->enhancer = new GitlabBranchCrossReferenceEnhancer(
            new TlpRelativeDatePresenterBuilder()
        );

        $GLOBALS['Language']
            ->method('getText')
            ->with('system', 'datefmt')
            ->willReturn('d/m/Y H:i');
    }

    public function testItBuildsTheBranchReferenceWithLastPushDate(): void
    {
        $reference = $this->getCrossReferencePresenter();

        $gitlab_branch = new GitlabBranch(
            '14a9b6c0c0c965977cf2af2199f93df82afcdea3',
            'dev_tuleap-123',
            (new DateTimeImmutable())->setTimestamp(1608555618)
        );

        $enhanced_reference = $this->enhancer->getCrossReferencePresenterWithBranchInformation(
            $reference,
            $gitlab_branch,
            $this->mockUser()
        );

        self::assertEquals('dev_tuleap-123', $enhanced_reference->title);

        self::assertCount(1, $enhanced_reference->additional_badges);
        self::assertEquals('14a9b6c0c0', $enhanced_reference->additional_badges[0]->label);

        self::assertNotNull($enhanced_reference->creation_metadata);
        self::assertNotNull($enhanced_reference->creation_metadata->created_on);
        self::assertEquals('2020-12-21T14:00:18+01:00', $enhanced_reference->creation_metadata->created_on->date);
        self::assertEquals('21/12/2020 14:00', $enhanced_reference->creation_metadata->created_on->absolute_date);
        self::assertEquals('tooltip', $enhanced_reference->creation_metadata->created_on->placement);
        self::assertEquals('relative', $enhanced_reference->creation_metadata->created_on->preference);
        self::assertEquals('en_US', $enhanced_reference->creation_metadata->created_on->locale);

        self::assertNull($enhanced_reference->creation_metadata->created_by);
    }

    public function testItBuildsTheBranchReferenceWithoutLastPushDate(): void
    {
        $reference = $this->getCrossReferencePresenter();

        $gitlab_branch = new GitlabBranch(
            '14a9b6c0c0c965977cf2af2199f93df82afcdea3',
            'dev_tuleap-123',
            null
        );

        $enhanced_reference = $this->enhancer->getCrossReferencePresenterWithBranchInformation(
            $reference,
            $gitlab_branch,
            $this->mockUser()
        );

        self::assertEquals('dev_tuleap-123', $enhanced_reference->title);

        self::assertCount(1, $enhanced_reference->additional_badges);
        self::assertEquals('14a9b6c0c0', $enhanced_reference->additional_badges[0]->label);

        self::assertNull($enhanced_reference->creation_metadata);
    }

    private function mockUser(): PFUser
    {
        $user = $this->createMock(PFUser::class);

        $user->method('getPreference')->willReturn('relative_first-absolute_tooltip');
        $user->method('getLocale')->willReturn('en_US');

        return $user;
    }

    private function getCrossReferencePresenter(): CrossReferencePresenter
    {
        return CrossReferencePresenterBuilder::get(1)
            ->withProjectId(1)
            ->withType(GitlabBranchReference::NATURE_NAME)
            ->withValue('john-snow/winter-is-coming/dev_tuleap-123')
            ->build();
    }
}
