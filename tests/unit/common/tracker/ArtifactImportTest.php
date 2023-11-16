<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use PHPUnit\Framework\MockObject\MockObject;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class ArtifactImportTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Tuleap\GlobalLanguageMock;

    /**
     * @var MockObject&\Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface
     */
    private $da;
    /**
     * @var DataAccessResult&MockObject
     */
    private $dar;

    protected function setUp(): void
    {
        parent::setUp();
        $this->da  = $this->createMock(\Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface::class);
        $this->dar = $this->createMock(\DataAccessResult::class);
        $this->da->method('query')->willReturn($this->dar);
        CodendiDataAccess::setInstance($this->da);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        CodendiDataAccess::clearInstance();
        unset($GLOBALS['db_qhandle']);
    }

    public function testALL(): void
    {
        $GLOBALS['Language']->method('getText')->willReturnCallback(
            static function (string $page_name, string $category, $args): string {
                if ($page_name === 'global' && $category === 'on') {
                    return 'on';
                }

                if ($page_name === 'global' && $category === 'by') {
                    return 'by';
                }

                if ($page_name === 'global' && $category === 'none') {
                    return 'none';
                }

                if ($page_name === 'tracker_import_utils' && $category === 'date') {
                    return 'date';
                }

                return '';
            }
        );

      /***************** var setup ***********************
       */

        $at = $this->getMockBuilder(\ArtifactType::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getName', 'allowsAnon', 'getID', 'userIsAdmin'])
            ->getMock();
        $at->method('getName')->willReturn('TestTracker');
        $at->method('allowsAnon')->willReturn(false);
        $at->method('getID')->willReturn('123');
        $at->method('userIsAdmin')->willReturn(true);

        $submitted_by = $this->createMock(ArtifactField::class);
        $submitted_by->method('getLabel')->willReturn('Submitted By');
        $submitted_by->method('getName')->willReturn('submitted_by');
        $submitted_by->method('isEmptyOk')->willReturn(false);
        $submitted_by->method('getDisplayType')->willReturn('SB');
        $submitted_by->method('isDateField')->willReturn(false);
        $submitted_by->method('isSelectBox')->willReturn(false);
        $submitted_by->method('isMultiSelectBox')->willReturn(false);

        $submitted_on = $this->createMock(ArtifactField::class);
        $submitted_on->method('getLabel')->willReturn('Submitted On');
        $submitted_on->method('getName')->willReturn('open_date');
        $submitted_on->method('isEmptyOk')->willReturn(false);
        $submitted_on->method('getDisplayType')->willReturn('DF');
        $submitted_on->method('isDateField')->willReturn(false);
        $submitted_on->method('isSelectBox')->willReturn(false);
        $submitted_on->method('isMultiSelectBox')->willReturn(false);

        $last_update_date = $this->createMock(ArtifactField::class);
        $last_update_date->method('getLabel')->willReturn('Last Modified On');
        $last_update_date->method('getName')->willReturn('last_update_date');
        $last_update_date->method('isEmptyOk')->willReturn(false);
        $last_update_date->method('getDisplayType')->willReturn('DF');
        $last_update_date->method('isDateField')->willReturn(true);
        $last_update_date->method('isSelectBox')->willReturn(false);
        $last_update_date->method('isMultiSelectBox')->willReturn(false);

        $artifact_id = $this->createMock(ArtifactField::class);
        $artifact_id->method('getLabel')->willReturn('Artifact Id');
        $artifact_id->method('getName')->willReturn('artifact_id');
        $artifact_id->method('isEmptyOk')->willReturn(false);
        $artifact_id->method('getDisplayType')->willReturn('TF');
        $artifact_id->method('isDateField')->willReturn(false);
        $artifact_id->method('isSelectBox')->willReturn(false);
        $artifact_id->method('isMultiSelectBox')->willReturn(false);

        $comment_type_id = $this->createMock(ArtifactField::class);
        $comment_type_id->method('getLabel')->willReturn('Comment Type');
        $comment_type_id->method('getName')->willReturn('comment_type_id');
        $comment_type_id->method('isEmptyOk')->willReturn(true);
        $comment_type_id->method('getDisplayType')->willReturn('TF');
        $comment_type_id->method('isDateField')->willReturn(false);
        $comment_type_id->method('isSelectBox')->willReturn(false);
        $comment_type_id->method('isMultiSelectBox')->willReturn(false);

        $assigned_to = $this->createMock(ArtifactField::class);
        $assigned_to->method('getLabel')->willReturn('Assigned To');
        $assigned_to->method('getName')->willReturn('assigned_to');
        $assigned_to->method('isEmptyOk')->willReturn(false);
        $assigned_to->method('getDisplayType')->willReturn('SB');
        $assigned_to->method('isDateField')->willReturn(false);
        $assigned_to->method('isSelectBox')->willReturn(true);
        $assigned_to->method('isMultiSelectBox')->willReturn(false);

        $orig_subm = $this->createMock(ArtifactField::class);
        $orig_subm->method('getLabel')->willReturn('Original Submission');
        $orig_subm->method('getName')->willReturn('details');
        $orig_subm->method('isEmptyOk')->willReturn(false);
        $orig_subm->method('getDisplayType')->willReturn('TA');
        $orig_subm->method('isDateField')->willReturn(false);
        $orig_subm->method('isSelectBox')->willReturn(false);
        $orig_subm->method('isMultiSelectBox')->willReturn(false);

        $atf = $this->createMock(\ArtifactFieldFactory::class);
        $atf->method('getAllUsedFields')->willReturn([$submitted_by, $submitted_on, $artifact_id, $comment_type_id, $assigned_to, $orig_subm]);
        $atf->method('getFieldFromName')->willReturnMap([
            ['submitted_by', $submitted_by],
            ['open_date', $submitted_on],
            ['last_update_date', $last_update_date],
            ['artifact_id', $artifact_id],
            ['assigned_to', $assigned_to],
            ['comment_type_id', $comment_type_id],
            ['details', $orig_subm],
        ]);

      /**************** test parseFieldNames ************
       */

        $test = new ArtifactImport($at, $atf, 'group');
        $test->parseFieldNames(['Submitted By', 'Submitted On', 'Assigned To', 'Original Submission']);
        self::assertFalse($test->isError());

      // need mandatory field assigned_to
        $test = new ArtifactImport($at, $atf, 'group');
        $test->parseFieldNames(['Submitted By']);
        self::assertTrue($test->isError());

      //comment type is not taken into account
        $test = new ArtifactImport($at, $atf, 'group');
        $test->parseFieldNames(['Comment Type', 'Assigned To', 'Original Submission']);
        self::assertTrue($test->isError());

        $test = new ArtifactImport($at, $atf, 'group');
        $test->parseFieldNames([$GLOBALS['Language']->getText('project_export_artifact_export', 'follow_up_comments'),
            $GLOBALS['Language']->getText('project_export_artifact_export', 'depend_on'),
            $GLOBALS['Language']->getText('project_export_artifact_export', 'add_cc_lbl'),
            $GLOBALS['Language']->getText('project_export_artifact_export', 'cc_comment_lbl'),
            'Assigned To','Original Submission',
        ]);
        self::assertFalse($test->isError());

      /***************** test checkValues *****************
       */

      // can not check submitted_by values (can not get user_id from here)
        $test = new ArtifactImport($at, $atf, 'group');
        $test->parseFieldNames([$GLOBALS['Language']->getText('project_export_artifact_export', 'follow_up_comments'),
            $GLOBALS['Language']->getText('project_export_artifact_export', 'depend_on'),
            $GLOBALS['Language']->getText('project_export_artifact_export', 'add_cc_lbl'),
            $GLOBALS['Language']->getText('project_export_artifact_export', 'cc_comment_lbl'),
            'Assigned To','Original Submission',
        ]);
        $test->predefined_values    = [];
        $test->predefined_values[4] = ['schneide' => ''];
        $data                       = [$GLOBALS['Language']->getText('tracker_import_utils', 'no_followups'), '', '', '', 'schneide', 'my original submission'];
        $test->checkValues('1', $data, false);
        self::assertFalse($test->isError());

      // schnuffi not in predefined values of assigned_to
        $data = [$GLOBALS['Language']->getText('tracker_import_utils', 'no_followups'), '', '', '', 'schnuffi', 'my original submission'];
        $test->checkValues('1', $data, false);
        self::assertTrue($test->isError());
        $test->clearError();

      //test mb fields
        $mbox_field = $this->createMock(ArtifactField::class);
        $mbox_field->method('getLabel')->willReturn('MB Field');
        $mbox_field->method('getName')->willReturn('mbox_field');
        $mbox_field->method('isEmptyOk')->willReturn(true);
        $mbox_field->method('getDisplayType')->willReturn('MB');
        $mbox_field->method('isDateField')->willReturn(false);
        $mbox_field->method('isSelectBox')->willReturn(false);
        $mbox_field->method('isMultiSelectBox')->willReturn(true);

        $sbox_field = $this->createMock(ArtifactField::class);
        $sbox_field->method('getLabel')->willReturn('SB Field');
        $sbox_field->method('getName')->willReturn('sbox_field');
        $sbox_field->method('isEmptyOk')->willReturn(false);
        $sbox_field->method('getDisplayType')->willReturn('SB');
        $sbox_field->method('isDateField')->willReturn(false);
        $sbox_field->method('isSelectBox')->willReturn(true);
        $sbox_field->method('isMultiSelectBox')->willReturn(false);

        $atf = $this->createMock(\ArtifactFieldFactory::class);
        $atf->method('getAllUsedFields')->willReturn([$submitted_by, $submitted_on, $artifact_id, $comment_type_id, $assigned_to, $orig_subm, $mbox_field, $sbox_field]);
        $atf->method('getFieldFromName')->willReturnMap([
            ['submitted_by', $submitted_by],
            ['open_date', $submitted_on],
            ['last_update_date', $last_update_date],
            ['artifact_id', $artifact_id],
            ['assigned_to', $assigned_to],
            ['comment_type_id', $comment_type_id],
            ['details', $orig_subm],
            ['mbox_field', $mbox_field],
            ['sbox_field', $sbox_field],
        ]);

        $test = new ArtifactImport($at, $atf, 'group');
        $test->parseFieldNames([$GLOBALS['Language']->getText('project_export_artifact_export', 'follow_up_comments'),
            $GLOBALS['Language']->getText('project_export_artifact_export', 'depend_on'),
            $GLOBALS['Language']->getText('project_export_artifact_export', 'add_cc_lbl'),
            $GLOBALS['Language']->getText('project_export_artifact_export', 'cc_comment_lbl'),
            'Assigned To','Original Submission','MB Field','SB Field',
        ]);
        $test->predefined_values    = [];
        $test->predefined_values[4] = ['schneide' => ''];
        $test->predefined_values[6] = ['one' => '', 'two' => '', 'three' => ''];
        $test->predefined_values[7] = ['blue' => '', 'yellow' => '', 'red' => ''];
        $data                       = [$GLOBALS['Language']->getText('tracker_import_utils', 'no_followups'),'','','','schneide','my original submission',
            'one,two,' . $GLOBALS['Language']->getText('global', 'none'),'yellow',
        ];
        $test->checkValues('1', $data, false);
        self::assertFalse($test->isError());

        $data = [$GLOBALS['Language']->getText('tracker_import_utils', 'no_followups'), '', '', '', 'schneide', 'my original submission', 'one,two,four', 'yellow'];
        $test->checkValues('1', $data, false);
        self::assertTrue($test->isError());
        $test->clearError();

      //check mandatory fields
        $data = [$GLOBALS['Language']->getText('tracker_import_utils', 'no_followups'), '', '', '', 'schneide', '', 'one,two,four', 'yellow'];
        $test->checkValues('1', $data, false);
        self::assertTrue($test->isError());
        $test->clearError();

        $data = [$GLOBALS['Language']->getText('tracker_import_utils', 'no_followups'), '', '', '', 'schneide', 'my original submission', 'one,two,four', $GLOBALS['Language']->getText('global', 'none')];
        $test->checkValues('1', $data, false);
        self::assertTrue($test->isError());
        $test->clearError();

      //test date format
      //submitted on is allowed to be void, we set it to current date on insert into DB
        $test = new ArtifactImport($at, $atf, 'group');
        $test->parseFieldNames([$GLOBALS['Language']->getText('project_export_artifact_export', 'follow_up_comments'),
            $GLOBALS['Language']->getText('project_export_artifact_export', 'depend_on'),
            $GLOBALS['Language']->getText('project_export_artifact_export', 'add_cc_lbl'),
            $GLOBALS['Language']->getText('project_export_artifact_export', 'cc_comment_lbl'),
            'Assigned To','Original Submission','MB Field','SB Field','Submitted On',
        ]);
        $data = [$GLOBALS['Language']->getText('tracker_import_utils', 'no_followups'), '', '', '', 'schneide', 'my original submission', 'one,two,four', 'yellow', ''];
        $test->checkValues('1', $data, false);
        self::assertFalse($test->isError());

      //sys_date_fmt
        $data = [$GLOBALS['Language']->getText('tracker_import_utils', 'no_followups'), '', '', '', 'schneide', 'my original submission', 'one,two,four', 'yellow', '2004-Feb-03 16:13'];
        $test->checkValues('1', $data, false);
        self::assertFalse($test->isError());

      //xls date format
        $data = [$GLOBALS['Language']->getText('tracker_import_utils', 'no_followups'), '', '', '', 'schneide', 'my original submission', 'one,two,four', 'yellow', '2/3/2004 16:13'];
        $test->checkValues('1', $data, false);
        self::assertFalse($test->isError());

      //short sys_date_fmt
        $data = [$GLOBALS['Language']->getText('tracker_import_utils', 'no_followups'), '', '', '', 'schneide', 'my original submission', 'one,two,four', 'yellow', '2004-Feb-03'];
        $test->checkValues('1', $data, false);
        self::assertFalse($test->isError());

      //short xls date format
        $data = [$GLOBALS['Language']->getText('tracker_import_utils', 'no_followups'), '', '', '', 'schneide', 'my original submission', 'one,two,four', 'yellow', '2/3/2004'];
        $test->checkValues('1', $data, false);
        self::assertFalse($test->isError());

      /***************** test parseFollowUpComments *****************
       */

        $aff = $this->createMock(\ArtifactFieldFactory::class);
        $aff->method('getAllUsedFields')->willReturn([]);
        $aff->method('getFieldFromName')->willReturnMap([
            ['submitted_by', $submitted_by],
            ['open_date', $submitted_on],
            ['last_update_date', $last_update_date],
        ]);

        $test = new ArtifactImport($at, $aff, 'group');
        $test->parseFieldNames([$GLOBALS['Language']->getText('project_export_artifact_export', 'follow_up_comments')]);
        $parsed_comments = [];
        $art_id          = '1149';

        $followup_comments = "Follow-ups
**********

------------------------------------------------------------------
" . $GLOBALS['Language']->getText('tracker_import_utils', 'date') . ": 2005-09-02 18:18              " . $GLOBALS['Language']->getText('global', 'by') . ": doswald
8/17/2004 4:21:57 PM New Entry
8/17/2004 4:24:38 PM DCO: Accepted for investigation, Prio Major 2 Assigned Cyrkin, Tonya
9/14/2004 2:13:03 PM DCO: Waiting on new database from Craig DeHond.
";

        $um   = $this->createMock(\UserManager::class);
        $ai   = $this->getMockBuilder(\ArtifactImport::class)
            ->setConstructorArgs([$at, $aff, 'group'])
            ->onlyMethods(['getUserManager'])
            ->getMock();
        $user = $this->createMock(\PFUser::class);

        $ai->method('getUserManager')->willReturn($um);
        $um->method('getUserByUserName')->willReturn($user);

        $ai->parseFollowUpComments($followup_comments, $parsed_comments, $art_id, true);

        self::assertFalse($ai->isError());
        self::assertEquals('2005-09-02 18:18', $parsed_comments[0]['date']);
        self::assertEquals($GLOBALS['Language']->getText('global', 'none'), $parsed_comments[0]['type']);
        self::assertEquals('doswald', $parsed_comments[0]['by']);

        $parsed_comments   = [];
        $followup_comments = "Follow-ups
**********

------------------------------------------------------------------
" . $GLOBALS['Language']->getText('tracker_import_utils', 'date') . ": 2005-10-19 18:28              " . $GLOBALS['Language']->getText('global', 'by') . ": doswald
Excel issue, reassigned to Gene, reduced to Ordinary

------------------------------------------------------------------
" . $GLOBALS['Language']->getText('tracker_import_utils', 'date') . ": 2005-09-02 16:51              " . $GLOBALS['Language']->getText('global', 'by') . ": doswald
1/18/2005 10:09:24 AM New Entry
1/18/2005 10:10:58 AM DCO: Accepted for investigation, Prio Major  Assigned Unassigned
";

        $ai->parseFollowUpComments($followup_comments, $parsed_comments, $art_id, true);
        self::assertFalse($ai->isError());
        self::assertEquals('2005-10-19 18:28', $parsed_comments[0]['date']);
        self::assertEquals($GLOBALS['Language']->getText('global', 'none'), $parsed_comments[0]['type']);
        self::assertEquals('doswald', $parsed_comments[0]['by']);
        self::assertEquals('2005-09-02 16:51', $parsed_comments[1]['date']);
        self::assertEquals($GLOBALS['Language']->getText('global', 'none'), $parsed_comments[1]['type']);
        self::assertEquals('doswald', $parsed_comments[1]['by']);

        $parsed_comments = [];

        $followup_comments = "==================================================
" . $GLOBALS['Language']->getText('tracker_import_utils', 'type') . ": " . $GLOBALS['Language']->getText('global', 'none') . "     " . $GLOBALS['Language']->getText('global', 'by') . ": jstidd      " . $GLOBALS['Language']->getText('global', 'on') . ": 2000-12-09 00:08

noreply was aliased to codendi-admin in order to prevent failure of delivery (to anybody) for the message.  This will cause all new bugs to be visible to the codendi administrators until an alternate solution is devised.  It seems ill-advised to set the email value in user=100 to null, because we are not sure where this value is used in the system and what the effects will be of possible syntax errors created by the use of null instead of a valid mail address.  What is needed is to alias noreply to a bit bucket.
==================================================
" . $GLOBALS['Language']->getText('tracker_import_utils', 'type') . ": " . $GLOBALS['Language']->getText('global', 'none') . "     " . $GLOBALS['Language']->getText('global', 'by') . ": jstidd      " . $GLOBALS['Language']->getText('global', 'on') . ": 2000-12-08 23:06

The cause of this problem is that bugs assigned to 'None' are assigned by default to the default user, user_id=100 by bug_data_create in bugs/bug_data.php.  The email field for user 100 in the database was noreply@sourceforge.net.  This has been changed to noreply@codendi.com.  The Assigned To: field on this bug has been changed to None in order to test this change.
==================================================
" . $GLOBALS['Language']->getText('tracker_import_utils', 'type') . ": " . $GLOBALS['Language']->getText('global', 'none') . "     " . $GLOBALS['Language']->getText('global', 'by') . ": jstidd      " . $GLOBALS['Language']->getText('global', 'on') . ": 2000-12-08 22:30

Confirming the previous Followup.  The bug was assigned to jstidd, who was present twice in the To: field of the message.  The followup message was not sent to noreply@sourceforge.net.

==================================================
" . $GLOBALS['Language']->getText('tracker_import_utils', 'type') . ": " . $GLOBALS['Language']->getText('global', 'none') . "     " . $GLOBALS['Language']->getText('global', 'by') . ": jstidd      " . $GLOBALS['Language']->getText('global', 'on') . ": 2000-12-08 22:27

Problem also occurs for new bugs posted to a project *with* a New Bugs address.  Apparently, if a bug is assigned to None (which is always the case with a new bug), the copy of the message intended for Assigned To is sent to noreply@sourceforge.net.

";

        $test->parseLegacyDetails($followup_comments, $parsed_comments, $art_id, true);
        self::assertFalse($test->isError());
        self::assertEquals('2000-12-09 00:08', $parsed_comments[0]['date']);
        self::assertEquals($GLOBALS['Language']->getText('global', 'none'), $parsed_comments[0]['type']);
        self::assertEquals('jstidd', $parsed_comments[0]['by']);
        self::assertEquals('2000-12-08 23:06', $parsed_comments[1]['date']);
        self::assertEquals($GLOBALS['Language']->getText('global', 'none'), $parsed_comments[1]['type']);
        self::assertEquals('jstidd', $parsed_comments[1]['by']);
        self::assertEquals($GLOBALS['Language']->getText('global', 'none'), $parsed_comments[2]['type']);
        self::assertEquals('2000-12-08 22:30', $parsed_comments[2]['date']);
        self::assertEquals($GLOBALS['Language']->getText('global', 'none'), $parsed_comments[3]['type']);
        self::assertEquals('2000-12-08 22:27', $parsed_comments[3]['date']);

        $parsed_comments = [];
        $test->parseFollowUpComments($followup_comments, $parsed_comments, $art_id, true);
        self::assertFalse($test->isError());
        self::assertEquals('2000-12-09 00:08', $parsed_comments[0]['date']);
        self::assertEquals($GLOBALS['Language']->getText('global', 'none'), $parsed_comments[0]['type']);
        self::assertEquals('jstidd', $parsed_comments[0]['by']);
        self::assertEquals('2000-12-08 23:06', $parsed_comments[1]['date']);
        self::assertEquals($GLOBALS['Language']->getText('global', 'none'), $parsed_comments[1]['type']);
        self::assertEquals('jstidd', $parsed_comments[1]['by']);
        self::assertEquals($GLOBALS['Language']->getText('global', 'none'), $parsed_comments[2]['type']);
        self::assertEquals('2000-12-08 22:30', $parsed_comments[2]['date']);
        self::assertEquals($GLOBALS['Language']->getText('global', 'none'), $parsed_comments[3]['type']);
        self::assertEquals('2000-12-08 22:27', $parsed_comments[3]['date']);

      /**
      check by hand:
      *  double submission (can not access DB from here)
      *  not enough columns in a row
      *  real insertion, real update (can not access DB)
      *  follow-up comment is already in DB or not
      */
    }

    public function testSplitFollowUpComments(): void
    {
        $aitv              = $this->getMockBuilder(\ArtifactImport::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $followup_comments = file_get_contents(__DIR__ . '/_fixtures/followup_comments1.txt');
        $comments          = $aitv->splitFollowUpComments($followup_comments);
        self::assertCount(4 + 1, $comments); // + 1 because the follow-up comments header is returned
    }

    public function testCanApplyHtmlSpecialCharsWithBaseTranslation(): void
    {
        $ai = $this->getMockBuilder(\ArtifactImport::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        self::assertTrue($ai->canApplyHtmlSpecialChars('"'));
        self::assertTrue($ai->canApplyHtmlSpecialChars('<'));
        self::assertTrue($ai->canApplyHtmlSpecialChars('>'));
        self::assertTrue($ai->canApplyHtmlSpecialChars('&'));
        self::assertFalse($ai->canApplyHtmlSpecialChars("'"));
    }

    public function testCanApplyHtmlSpecialCharsWithTranslatedChars(): void
    {
        $ai = $this->getMockBuilder(\ArtifactImport::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        self::assertFalse($ai->canApplyHtmlSpecialChars('&quot;'));
        self::assertFalse($ai->canApplyHtmlSpecialChars('&lt;'));
        self::assertFalse($ai->canApplyHtmlSpecialChars('&gt;'));
        self::assertFalse($ai->canApplyHtmlSpecialChars('&amp;'));
        self::assertTrue($ai->canApplyHtmlSpecialChars('&#039;'));
    }

    public function testCanApplyHtmlSpecialCharsWithAdvancedHTMLTricks(): void
    {
        $ai = $this->getMockBuilder(\ArtifactImport::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        self::assertFalse($ai->canApplyHtmlSpecialChars("&lt;p&gt;this is 'my test'&lt;/p&gt;"));
        self::assertTrue($ai->canApplyHtmlSpecialChars("<p>this is 'my test'</p>"));

        self::assertFalse($ai->canApplyHtmlSpecialChars("&lt;p&gt;&amp;lt;toto&amp;gt;&lt;/p&gt;"));
        self::assertTrue($ai->canApplyHtmlSpecialChars("<p>&lt;toto&gt;</p>"));

        self::assertFalse($ai->canApplyHtmlSpecialChars("test&lt;br/&gt;"));
        self::assertTrue($ai->canApplyHtmlSpecialChars("test<br/>"));
    }

    /**
     * This case is impossible to catch so it's a known error.
     *
     * It might happens if, on the web, the user entered *as text* HTML entities
     * (for instance &lt;), then exported it in CSV and finaly imported it with
     * CSV as well.
     */
    public function testUnCatchableStrings(): void
    {
        $ai = $this->getMockBuilder(\ArtifactImport::class)->disableOriginalConstructor()->onlyMethods([])->getMock();

        self::assertFalse($ai->canApplyHtmlSpecialChars("Test&amp;lt;"));
        // Should be assertTrue here
        self::assertFalse($ai->canApplyHtmlSpecialChars("Test&lt;"));
    }

    public function testCanApplyHtmlSpecialCharsWithRealTextTricks(): void
    {
        $ai = $this->getMockBuilder(\ArtifactImport::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        self::assertTrue($ai->canApplyHtmlSpecialChars('"Description"'));
        self::assertFalse($ai->canApplyHtmlSpecialChars("Following today's Codex framework update, it looks better in the sense I now have access to all charts."));
        self::assertTrue($ai->canApplyHtmlSpecialChars('&&lt;'));
        self::assertTrue($ai->canApplyHtmlSpecialChars('&&gt;'));
        self::assertTrue($ai->canApplyHtmlSpecialChars('&&amp;'));
        self::assertTrue($ai->canApplyHtmlSpecialChars('&&quot;'));
    }

    public function testCheckCommentExistInLegacyFormat(): void
    {
        $this->da->method('numRows')->willReturn(1);
        $this->da->method('fetchArray')->willReturn(['new_value' => '<pre> testing issue </pre>']);
        $this->da->method('dataSeek')->willReturn(true);
        $this->dar->method('getResult')->willReturn(true);
        $artImp       = $this->getMockBuilder(\ArtifactImport::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $artId        = 12237;
        $parsedFollow = ['comment' => '<pre> testing issue </pre>'];
        self::assertTrue($artImp->checkCommentExistInLegacyFormat($parsedFollow, $artId));
    }
}
