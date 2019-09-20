<?php
/**
 * Copyright (c) Enalean, 2012-2019. All Rights Reserved.
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

use Tuleap\SOAP\SOAPRequestValidator;

class SVN_SOAPServerBaseTest extends TuleapTestCase
{

    public function setUp()
    {
        $this->session_key = 'whatever';
        $this->group_id    = 123;
        $this->svn_path    = '/tags';

        $this->project = mock('Project');
        $this->user = mock('PFUser');

        $this->soap_request_valid     = Mockery::mock(SOAPRequestValidator::class);
        stub($this->soap_request_valid)->getProjectById($this->group_id, '*')->returns($this->project);
        stub($this->soap_request_valid)->continueSession($this->session_key)->returns($this->user);
    }
}

class SVN_SOAPServer_GetSvnPath_Test extends SVN_SOAPServerBaseTest
{
    public function itCheckUserSessionAndGroupValidity()
    {
        $svn_repository_listing = mock('SVN_RepositoryListing');
        $svn_repository_listing->expectOnce('getSvnPaths', array($this->user, $this->project, $this->svn_path));

        $this->soap_request_valid->shouldReceive('assertUserCanAccessProject')->once();
        $svn_soap = new SVN_SOAPServer($this->soap_request_valid, $svn_repository_listing);
        $svn_soap->getSvnPath($this->session_key, $this->group_id, $this->svn_path);
    }
}

class SVN_SOAPServer_GetSvnPathWithLogDetails_Test extends SVN_SOAPServerBaseTest
{
    public function setUp()
    {
        parent::setUp();

        $this->svn_repository_listing = mock('SVN_RepositoryListing');
        stub($this->svn_repository_listing)->getSvnPathsWithLogDetails()->returns(array());
        $this->order = 'ASC';
    }

    public function itCheckUserSessionAndGroupValidity()
    {
        $svn_soap = new SVN_SOAPServer($this->soap_request_valid, $this->svn_repository_listing);
        $svn_soap->getSvnPathsWithLogDetails($this->session_key, $this->group_id, $this->svn_path, $this->order);
    }

    public function itThrowsSoapFaultIfUserHasWrongPermissions()
    {
        $soap_request_valid     = Mockery::mock(SOAPRequestValidator::class);
        stub($soap_request_valid)->getProjectById($this->group_id, '*')->returns($this->project);
        stub($soap_request_valid)->continueSession($this->session_key)->returns($this->user);
        stub($soap_request_valid)->assertUserCanAccessProject($this->user, $this->project)->returns(new Exception());

        $svn_soap = new SVN_SOAPServer($soap_request_valid, $this->svn_repository_listing);
        $svn_soap->getSvnPathsWithLogDetails($this->session_key, $this->group_id, $this->svn_path, $this->order);
    }

    public function itDoesNotThrowSoapFaultIfRepositoryIsEmpty()
    {
        $svn_soap = new SVN_SOAPServer($this->soap_request_valid, $this->svn_repository_listing);
        $svn_soap->getSvnPathsWithLogDetails($this->session_key, $this->group_id, $this->svn_path, $this->order);
    }
}
