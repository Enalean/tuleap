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

namespace Tuleap\Reference\ByNature\ConcurrentVersionsSystem;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Project;
use ProjectManager;
use Tuleap\ConcurrentVersionsSystem\CvsDao;
use Tuleap\Date\TlpRelativeDatePresenterBuilder;
use Tuleap\GlobalLanguageMock;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Reference\CrossReferencePresenter;
use Tuleap\Test\Builders\CrossReferencePresenterBuilder;
use UserHelper;
use UserManager;

class CrossReferenceCvsOrganizerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    private const PROJECT_ID       = 42;
    private const PROJECT_UNIXNAME = "my-project-42";

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CvsDao
     */
    private $dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UserManager
     */
    private $user_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UserHelper
     */
    private $user_helper;
    /**
     * @var CrossReferenceCvsOrganizer
     */
    private $cvs_organizer;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CrossReferenceByNatureOrganizer
     */
    private $by_nature_organizer;

    protected function setUp(): void
    {
        $project_manager    = Mockery::mock(ProjectManager::class);
        $this->dao          = Mockery::mock(CvsDao::class);
        $this->user_manager = Mockery::mock(UserManager::class);
        $this->user_helper  = Mockery::mock(UserHelper::class);

        $this->cvs_organizer = new CrossReferenceCvsOrganizer(
            $project_manager,
            $this->dao,
            new TlpRelativeDatePresenterBuilder(),
            $this->user_manager,
            $this->user_helper,
        );

        $this->project = Mockery::mock(Project::class, ['getUnixNameMixedCase' => self::PROJECT_UNIXNAME]);

        $project_manager
            ->shouldReceive('getProject')
            ->with(self::PROJECT_ID)
            ->andReturn($this->project);

        $user = Mockery::mock(
            \PFUser::class,
            [
                'getPreference' => 'relative_first-absolute_tooltip',
                'getLocale'     => 'en_US',
            ]
        );

        $this->by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class, ['getCurrentUser' => $user]);

        $GLOBALS['Language']
            ->method('getText')
            ->with('system', 'datefmt')
            ->willReturn('d/m/Y H:i');
    }

    public function testItRemovesCrossReferenceIfCommitIsNotFound(): void
    {
        $ref = CrossReferencePresenterBuilder::get(1)
            ->withProjectId(self::PROJECT_ID)
            ->withValue("111")
            ->build();

        $this->dao
            ->shouldReceive('searchCommit')
            ->with(111, self::PROJECT_UNIXNAME)
            ->andReturnNull();

        $this->by_nature_organizer
            ->shouldReceive('removeUnreadableCrossReference')
            ->with($ref)
            ->once();

        $this->cvs_organizer->organizeCvsReference($ref, $this->by_nature_organizer);
    }

    public function testItMovesCrossReferenceToUnlabelledSection(): void
    {
        $ref = CrossReferencePresenterBuilder::get(1)
            ->withProjectId(self::PROJECT_ID)
            ->withValue("111")
            ->build();

        $author = Mockery::mock(
            \PFUser::class,
            [
                'hasAvatar'    => true,
                'getAvatarUrl' => '/path/to/avatar.png',
            ]
        );

        $this->user_manager
            ->shouldReceive('getUserById')
            ->with(102)
            ->andReturn($author);

        $this->user_helper
            ->shouldReceive('getDisplayNameFromUser')
            ->with($author)
            ->andReturn('Irène Joliot-Curie');

        $this->dao
            ->shouldReceive('searchCommit')
            ->with(111, self::PROJECT_UNIXNAME)
            ->andReturn(
                [
                    'repository'  => '/cvsroot/my-project-42',
                    'description' => "\nLorem ipsum\ndoloret\n",
                    'revision'    => '1.35',
                    'whoid'       => 102,
                    'comm_when'   => '2009/02/13 23:31:30',
                ]
            );

        $this->by_nature_organizer
            ->shouldReceive('moveCrossReferenceToSection')
            ->with(
                Mockery::on(
                    function (CrossReferencePresenter $presenter): bool {
                        return $presenter->id === 1
                            && $presenter->title === 'Lorem ipsum'
                            && $presenter->additional_badges[0]->label === '1.35'
                            && $presenter->creation_metadata->created_by->display_name === 'Irène Joliot-Curie'
                            && $presenter->creation_metadata->created_on->absolute_date === '13/02/2009 23:31';
                    }
                ),
                '',
            )
            ->once();

        $this->cvs_organizer->organizeCvsReference($ref, $this->by_nature_organizer);
    }
}
