<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\SVN\Reference;

use Tuleap\SVN\Repository\Exception\CannotFindRepositoryException;
use Tuleap\SVN\Repository\RepositoryManager;
use TuleapTestCase;

require_once __DIR__ .'/../bootstrap.php';

class ExtractorTest extends TuleapTestCase {

    /**
     * @var Extractor
     */
    private $extractor;

    /**
     * @var Project
     */
    private $project;

    /**
     * @var RepositoryManager
     */
    private $repository_manager;

    public function setUp() {
        parent::setUp();

        $this->project            = stub('Project')->getID()->returns(101);
        $this->repository_manager = mock('Tuleap\SVN\Repository\RepositoryManager');
        $this->extractor          = new Extractor($this->repository_manager);
    }

    public function itReturnsFalseIfReferenceDoesNotProvideRepositoryName() {
        $keyword = 'svn';
        $value   = '1';

        stub($this->project)->usesService('plugin_svn')->returns(true);

        $this->assertFalse($this->extractor->getReference($this->project, $keyword, $value));
    }

    public function itReturnsFalseIfTheProjectDoesNotUseTheSubversionPlugin() {
        $keyword = 'svn';
        $value   = 'repo01/1';

        stub($this->project)->usesService('plugin_svn')->returns(false);

        $this->assertFalse($this->extractor->getReference($this->project, $keyword, $value));
    }

    public function itReturnsFalseIfTheProvidedRepositoryIsNotInTheCurrentProject() {
        $keyword = 'svn';
        $value   = 'repo02/1';

        stub($this->project)->usesService('plugin_svn')->returns(true);
        stub($this->repository_manager)
            ->getRepositoryByName($this->project, 'repo02')
            ->throws(new CannotFindRepositoryException());

        $this->assertFalse($this->extractor->getReference($this->project, $keyword, $value));
    }

    public function itBuildsASubversionPluginReference() {
        $keyword    = 'svn';
        $value      = 'repo01/1';
        $repository = stub('Tuleap\SVN\Repository\Repository')->getFullName()->returns('project01/repo01');

        stub($this->project)->usesService('plugin_svn')->returns(true);
        stub($this->repository_manager)
            ->getRepositoryByName($this->project, 'repo01')
            ->returns($repository);

        $reference = $this->extractor->getReference($this->project, $keyword, $value);

        $this->assertIsA(
            $reference,
            'Tuleap\SVN\Reference\Reference'
        );

        $this->assertEqual($reference->getGroupId(), 101);
        $this->assertEqual($reference->getKeyword(), 'svn');
    }
}
