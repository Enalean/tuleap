<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'common/soap/SOAP_RequestValidator.class.php';

class SOAP_RequestValidatorTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        $this->validator = new SOAP_RequestValidator(mock('ProjectManager'), mock('UserManager'));
        $this->user      = mock('PFUser');
        $this->project   = mock('Project');
        stub($this->project)->getGroupId()->returns(123);
    }
    
    public function itRaisesNoExceptionWhenProjectIsPublicAndUserIsNotRestricted() {
        stub($this->project)->isPublic()->returns(true);
        stub($this->user)->isRestricted()->returns(false);
        
        $this->validator->assertUserCanAccessProject($this->user, $this->project);
        $this->pass('No exception has been raised');
    }
    
    public function itRaisesNoExceptionWhenProjectIsPublicAndUserIsRestrictedAndMember() {
        stub($this->project)->isPublic()->returns(true);
        stub($this->user)->isRestricted()->returns(true);
        stub($this->user)->isMember($this->project->getGroupId())->returns(true);
        
        $this->validator->assertUserCanAccessProject($this->user, $this->project);
        $this->pass('No exception has been raised');
    }
    
    public function itRaisesAnExceptionWhenProjectIsPrivateAndUserNotMember() {
        stub($this->project)->isPublic()->returns(false);
        stub($this->user)->isMember($this->project->getGroupId())->returns(false);
        
        $this->expectException();
        $this->validator->assertUserCanAccessProject($this->user, $this->project);
    }
    
    public function itRaisesNoExceptionWhenProjectIsPrivateAndUserMember() {
        stub($this->project)->isPublic()->returns(false);
        stub($this->user)->isMember($this->project->getGroupId())->returns(true);
        
        $this->validator->assertUserCanAccessProject($this->user, $this->project);
        $this->pass('No exception has been raised');
    }
    
    public function itRaisesAnExceptionWhenProjectIsPublicAndUserIsRestrictedAndNotMember() {
        stub($this->project)->isPublic()->returns(true);
        stub($this->user)->isRestricted()->returns(true);
        stub($this->user)->isMember($this->project->getGroupId())->returns(false);
        
        $this->expectException();
        $this->validator->assertUserCanAccessProject($this->user, $this->project);
    }
    
    public function itRaisesAnExceptionWhenProjectIsPrivateAndUserIsRestrictedAndNotMember() {
        stub($this->project)->isPublic()->returns(false);
        stub($this->user)->isRestricted()->returns(true);
        stub($this->user)->isMember($this->project->getGroupId())->returns(false);
        
        $this->expectException();
        $this->validator->assertUserCanAccessProject($this->user, $this->project);
    }
}
?>
