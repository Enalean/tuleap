<?php
/**
 * Copyright Enalean (c) 2017-Present. All rights reserved.
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

namespace Tuleap\User;

use PFUser;
use ProjectUGroup;
use Rule_Email;
use Tuleap\GlobalResponseMock;

final class RequestFromAutocompleterTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use GlobalResponseMock;

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

    protected function setUp(): void
    {
        parent::setUp();

        $this->current_user = \Mockery::spy(\PFUser::class);
        $this->smith        = new PFUser(['user_id' => 234, 'language_id' => 'en_US']);
        $this->thomas       = new PFUser(['user_id' => 235, 'language_id' => 'en_US']);

        $this->project_members = new ProjectUGroup(['ugroup_id' => 3]);
        $this->developers      = new ProjectUGroup(['ugroup_id' => 103]);
        $this->secret          = new ProjectUGroup(['ugroup_id' => 104]);

        $this->project = \Mockery::spy(\Project::class, ['getID' => 101, 'getUnixName' => false, 'isPublic' => false]);
        $this->current_user->shouldReceive('isMemberOfUGroup')->with($this->developers->getId(), $this->project->getId())->andReturns(true);
        $this->current_user->shouldReceive('isMemberOfUGroup')->with($this->secret->getId(), $this->project->getId())->andReturns(false);

        $this->ugroup_manager = \Mockery::spy(\UGroupManager::class);
        $this->ugroup_manager->shouldReceive('getUgroupByName')->with($this->project, 'project_members')->andReturns($this->project_members);
        $this->ugroup_manager->shouldReceive('getUgroupByName')->with($this->project, 'Developers')->andReturns($this->developers);
        $this->ugroup_manager->shouldReceive('getUgroupByName')->with($this->project, 'Secret')->andReturns($this->secret);

        $this->user_manager = \Mockery::spy(\UserManager::class);
        $this->user_manager->shouldReceive('findUser')->with('Smith (asmith)')->andReturns($this->smith);
        $this->user_manager->shouldReceive('findUser')->with('Thomas A. Anderson (neo)')->andReturns($this->thomas);

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

    public function testItExtractEmails(): void
    {
        $request = $this->getRequest('jdoe@example.com,smith@example.com');

        $this->assertEquals(array('jdoe@example.com', 'smith@example.com'), $request->getEmails());
    }

    public function testItIgnoresIfItIsUnknown(): void
    {
        $request = $this->getRequest(',bla,');

        $this->assertEmpty($request->getEmails());
        $this->assertEmpty($request->getUsers());
        $this->assertEmpty($request->getUgroups());
    }

    public function testItExtractsUgroups(): void
    {
        $request = $this->getRequest('_ugroup:project_members,_ugroup:Developers');

        $this->assertEquals(array($this->project_members, $this->developers), $request->getUgroups());
    }

    public function testItDoesNotLeakSecretUgroups(): void
    {
        $request = $this->getRequest('_ugroup:Secret');

        $this->assertEquals([], $request->getUgroups());
    }

    public function testItExtractsUsers(): void
    {
        $request = $this->getRequest('Smith (asmith),Thomas A. Anderson (neo)');

        $this->assertEquals(array($this->smith, $this->thomas), $request->getUsers());
    }

    public function testItIgnoresUnknownPeople(): void
    {
        $request = $this->getRequest('Unknown (seraph)');

        $this->assertEquals([], $request->getUsers());
    }

    public function testItExtractsEmailsAndUgroupsAndUsers(): void
    {
        $request = $this->getRequest('jdoe@example.com,Thomas A. Anderson (neo),_ugroup:Developers');

        $this->assertEquals(array('jdoe@example.com'), $request->getEmails());
        $this->assertEquals(array($this->developers), $request->getUgroups());
        $this->assertEquals(array($this->thomas), $request->getUsers());
    }

    public function testItCollectsUnknownEntries(): void
    {
        $this->getRequest('bla,jdoe@example.com,_ugroup:Secret,Unknown (seraph)');

        $GLOBALS['Response']->shouldReceive('addFeedback')->times(3);
        $GLOBALS['Response']->shouldReceive('addFeedback')->with(\Feedback::WARN, "The entered value 'bla' is invalid.")->ordered();
        $GLOBALS['Response']->shouldReceive('addFeedback')->with(\Feedback::WARN, "The entered value 'Secret' is invalid.")->ordered();
        $GLOBALS['Response']->shouldReceive('addFeedback')->with(\Feedback::WARN, "The entered value 'seraph' is invalid.")->ordered();

        $this->invalid_entries->generateWarningMessageForInvalidEntries();
    }

    public function testItIgnoresEmptyStrings(): void
    {
        $this->getRequest('');

        $this->invalid_entries->generateWarningMessageForInvalidEntries();
        $GLOBALS['Response']->shouldReceive('addFeedback')->times(0);
    }
}
