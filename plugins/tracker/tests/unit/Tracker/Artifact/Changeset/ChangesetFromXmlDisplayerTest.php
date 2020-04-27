<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Tracker\Artifact\Changeset;

use Mockery;
use PHPUnit\Framework\TestCase;
use Tuleap\GlobalLanguageMock;

final class ChangesetFromXmlDisplayerTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var ChangesetFromXmlDisplayer
     */
    private $displayer;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\TemplateRenderer
     */
    private $renderer;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\UserManager
     */
    private $user_manager;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ChangesetFromXmlDao
     */
    private $dao;

    protected function setUp(): void
    {
        $this->dao          = \Mockery::mock(ChangesetFromXmlDao::class);
        $this->user_manager = Mockery::mock(\UserManager::class);
        $this->renderer     = Mockery::mock(\TemplateRenderer::class);
        $this->displayer    = new ChangesetFromXmlDisplayer(
            $this->dao,
            $this->user_manager,
            $this->renderer
        );
    }

    public function testItReturnsAnEmptyStringIfChangesetDoesNotComeFromXmlImport(): void
    {
        $this->dao->shouldReceive('searchChangeset')->andReturn(null);
        $this->user_manager->shouldReceive('getUserById')->never();
        $this->renderer->shouldReceive('renderToString')->never();

        $this->assertEquals("", $this->displayer->display(1234));
    }

    public function testItReturnsAnEmptyStringIfUserWhoPerfomTheImportDoesNotExisit(): void
    {
        $this->dao->shouldReceive('searchChangeset')->andReturn(["user_id" => 101, "timestamp" => 123456789]);
        $this->user_manager->shouldReceive('getUserById')->once()->andReturn(null);
        $this->renderer->shouldReceive('renderToString')->never();

        $this->assertEquals("", $this->displayer->display(1234));
    }

    public function testItRendersTheChangeset(): void
    {
        $this->dao->shouldReceive('searchChangeset')->andReturn(["user_id" => 101, "timestamp" => 123456789]);
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('getName')->andReturn("user");
        $user->shouldReceive('getPublicProfileUrl')->andReturn("user");
        $this->user_manager->shouldReceive('getUserById')->once()->andReturn($user);
        $this->renderer->shouldReceive('renderToString')->once()->andReturn("Imported by user on 2020-04-20");

        $this->assertEquals("Imported by user on 2020-04-20", $this->displayer->display(1234));
    }
}
