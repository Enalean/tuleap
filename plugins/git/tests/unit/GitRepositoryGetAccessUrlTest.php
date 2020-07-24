<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once 'bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class GitRepositoryGetAccessUrlTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Git_Backend_Interface
     */
    private $backend;

    /**
     * @var GitRepository
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backend = \Mockery::spy(\GitBackend::class);

        $this->repository = new GitRepository();
        $this->repository->setBackend($this->backend);
    }

    public function testItReturnsTheBackendContent(): void
    {
        $access_url = ['ssh' => 'plop'];
        $this->backend->shouldReceive('getAccessURL')->andReturns(['ssh' => 'plop']);
        $this->assertEquals($access_url, $this->repository->getAccessURL());
    }
}
