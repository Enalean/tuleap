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
use PHPUnit\Framework\MockObject\MockObject;
use ProjectUGroup;
use Rule_Email;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class RequestFromAutocompleterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalResponseMock;

    private Rule_Email $rule_email;
    private \UserManager&MockObject $user_manager;
    private ProjectUGroup $project_members;
    private ProjectUGroup $developers;
    private \Project $project;
    private \UGroupManager&MockObject $ugroup_manager;
    private ProjectUGroup $secret;
    private PFUser&MockObject $current_user;
    private PFUser $smith;
    private PFUser $thomas;
    private InvalidEntryInAutocompleterCollection $invalid_entries;

    protected function setUp(): void
    {
        parent::setUp();

        $this->current_user = $this->createMock(\PFUser::class);
        $this->smith        = new PFUser(['user_id' => 234, 'language_id' => 'en_US']);
        $this->thomas       = new PFUser(['user_id' => 235, 'language_id' => 'en_US']);

        $this->current_user->method('isAdmin')->willReturn(false);

        $this->project_members = new ProjectUGroup(['ugroup_id' => 3]);
        $this->developers      = new ProjectUGroup(['ugroup_id' => 103]);
        $this->secret          = new ProjectUGroup(['ugroup_id' => 104]);

        $this->project = ProjectTestBuilder::aProject()->withId(101)->withAccessPrivate()->build();
        $this->current_user->method('isMemberOfUGroup')->willReturnCallback(
            function (int $ugroup_id, string $group_id): bool {
                if ($group_id === "101" && $ugroup_id === 103) {
                    return true;
                }

                    return false;
            }
        );

        $this->ugroup_manager = $this->createMock(\UGroupManager::class);
        $this->ugroup_manager->method('getUgroupByName')->willReturnMap([
            [$this->project, 'project_members', $this->project_members],
            [$this->project, 'Developers', $this->developers],
            [$this->project, 'Secret', $this->secret],
        ]);

        $this->user_manager = $this->createMock(\UserManager::class);
        $this->user_manager->method('findUser')->willReturnMap([
            ['Smith (asmith)', $this->smith],
            ['Thomas A. Anderson (neo)', $this->thomas],
        ]);

        $this->invalid_entries = new InvalidEntryInAutocompleterCollection();

        $this->rule_email = new Rule_Email();
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

        self::assertEquals(['jdoe@example.com', 'smith@example.com'], $request->getEmails());
    }

    public function testItIgnoresIfItIsUnknown(): void
    {
        $request = $this->getRequest(',bla,');

        self::assertEmpty($request->getEmails());
        self::assertEmpty($request->getUsers());
        self::assertEmpty($request->getUgroups());
    }

    public function testItExtractsUgroups(): void
    {
        $request = $this->getRequest('_ugroup:project_members,_ugroup:Developers');

        self::assertEquals([$this->project_members, $this->developers], $request->getUgroups());
    }

    public function testItDoesNotLeakSecretUgroups(): void
    {
        $request = $this->getRequest('_ugroup:Secret');

        self::assertEquals([], $request->getUgroups());
    }

    public function testItExtractsUsers(): void
    {
        $request = $this->getRequest('Smith (asmith),Thomas A. Anderson (neo)');

        self::assertEquals([$this->smith, $this->thomas], $request->getUsers());
    }

    public function testItIgnoresUnknownPeople(): void
    {
        $request = $this->getRequest('Unknown (seraph)');

        self::assertEquals([], $request->getUsers());
    }

    public function testItExtractsEmailsAndUgroupsAndUsers(): void
    {
        $request = $this->getRequest('jdoe@example.com,Thomas A. Anderson (neo),_ugroup:Developers');

        self::assertEquals(['jdoe@example.com'], $request->getEmails());
        self::assertEquals([$this->developers], $request->getUgroups());
        self::assertEquals([$this->thomas], $request->getUsers());
    }

    public function testItCollectsUnknownEntries(): void
    {
        $this->getRequest('bla,jdoe@example.com,_ugroup:Secret,Unknown (seraph)');

        $GLOBALS['Response']->expects(self::exactly(3))->method('addFeedback')->willReturnCallback(
            function (string $level, string $message): void {
                if ($level !== \Feedback::WARN) {
                    throw new \LogicException("Unexpected feedback level: " . $level);
                }
                if (
                    $message !== "The entered value 'bla' is invalid." &&
                    $message !== "The entered value 'Secret' is invalid." &&
                    $message !== "The entered value 'Unknown (seraph)' is invalid."
                ) {
                    throw new \LogicException("Unexpected message");
                }
            }
        );

        $this->invalid_entries->generateWarningMessageForInvalidEntries();
    }

    public function testItIgnoresEmptyStrings(): void
    {
        $this->getRequest('');

        $this->invalid_entries->generateWarningMessageForInvalidEntries();
        $GLOBALS['Response']->expects(self::never())->method('addFeedback');
    }
}
