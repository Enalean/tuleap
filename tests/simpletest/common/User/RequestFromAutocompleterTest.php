<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
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

namespace Tuleap\User;

use Rule_Email;
use TuleapTestCase;

class RequestFromAutocompleterTest extends TuleapTestCase
{
    private $rule_email;
    private $user_manager;
    private $project_members;
    private $developers;
    private $project;
    private $ugroup_manager;
    private $secret;
    private $current_user;
    private $smith;
    private $thomas;
    /** @var InvalidEntryInAutocompleterCollection */
    private $invalid_entries;

    public function setUp()
    {
        parent::setUp();

        $this->current_user = mock('PFUser');
        $this->smith        = aUser()->withId(234)->build();
        $this->thomas       = aUser()->withId(234)->build();

        $this->project_members = aMockUGroup()->withId(3)->build();
        $this->developers      = aMockUGroup()->withId(103)->build();
        $this->secret          = aMockUGroup()->withId(104)->build();

        $this->project = aMockProject()->withId(101)->build();
        stub($this->current_user)->isMemberOfUGroup($this->developers->getId(), $this->project->getId())->returns(true);
        stub($this->current_user)->isMemberOfUGroup($this->secret->getId(), $this->project->getId())->returns(false);

        $this->ugroup_manager = mock('UGroupManager');
        stub($this->ugroup_manager)->getUgroupByName($this->project, 'project_members')->returns($this->project_members);
        stub($this->ugroup_manager)->getUgroupByName($this->project, 'Developers')->returns($this->developers);
        stub($this->ugroup_manager)->getUgroupByName($this->project, 'Secret')->returns($this->secret);

        $this->user_manager = mock('UserManager');
        stub($this->user_manager)->findUser('Smith (asmith)')->returns($this->smith);
        stub($this->user_manager)->findUser('Thomas A. Anderson (neo)')->returns($this->thomas);

        $this->invalid_entries = new InvalidEntryInAutocompleterCollection();

        $this->rule_email   = new Rule_Email();
    }

    /**
     * @return RequestFromAutocompleter
     */
    private function getRequest($data)
    {
        return new RequestFromAutocompleter(
            $this->invalid_entries,
            $this->rule_email,
            $this->user_manager,
            $this->ugroup_manager,
            $this->current_user,
            $this->project,
            $data
        );
    }

    public function itExtractEmails()
    {
        $request = $this->getRequest('jdoe@example.com,smith@example.com');

        $this->assertEqual($request->getEmails(), array('jdoe@example.com', 'smith@example.com'));
    }

    public function itIgnoresIfItIsUnknown()
    {
        $request = $this->getRequest(',bla,');

        $this->assertEqual($request->getEmails(), array());
        $this->assertEqual($request->getUsers(), array());
        $this->assertEqual($request->getUgroups(), array());
    }

    public function itExtractsUgroups()
    {
        $request = $this->getRequest('_ugroup:project_members,_ugroup:Developers');

        $this->assertEqual($request->getUgroups(), array($this->project_members, $this->developers));
    }

    public function itDoesNotLeakSecretUgroups()
    {
        $request = $this->getRequest('_ugroup:Secret');

        $this->assertEqual($request->getUgroups(), array());
    }

    public function itExtractsUsers()
    {
        $request = $this->getRequest('Smith (asmith),Thomas A. Anderson (neo)');

        $this->assertEqual($request->getUsers(), array($this->smith, $this->thomas));
    }

    public function itIgnoresUnknownPeople()
    {
        $request = $this->getRequest('Unknown (seraph)');

        $this->assertEqual($request->getUsers(), array());
    }

    public function itExtractsEmailsAndUgroupsAndUsers()
    {
        $request = $this->getRequest('jdoe@example.com,Thomas A. Anderson (neo),_ugroup:Developers');

        $this->assertEqual($request->getEmails(), array('jdoe@example.com'));
        $this->assertEqual($request->getUgroups(), array($this->developers));
        $this->assertEqual($request->getUsers(), array($this->thomas));
    }

    public function itCollectsUnknownEntries()
    {
        $this->getRequest('bla,jdoe@example.com,_ugroup:Secret,Unknown (seraph)');

        $this->invalid_entries->generateWarningMessageForInvalidEntries();
        expect($GLOBALS['Response'])->addFeedback()->count(3);
        expect($GLOBALS['Response'])->addFeedback(\Feedback::WARN, "The entered value 'bla' is invalid.")->at(0);
        expect($GLOBALS['Response'])->addFeedback(\Feedback::WARN, "The entered value 'Secret' is invalid.")->at(1);
        expect($GLOBALS['Response'])->addFeedback(\Feedback::WARN, "The entered value 'seraph' is invalid.")->at(2);
    }

    public function itIgnoresEmptyStrings()
    {
        $this->getRequest('');

        $this->invalid_entries->generateWarningMessageForInvalidEntries();
        expect($GLOBALS['Response'])->addFeedback()->count(0);
    }
}
