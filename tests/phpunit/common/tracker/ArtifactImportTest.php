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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class ArtifactImportTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration, \Tuleap\GlobalLanguageMock;

    protected function setUp() : void
    {
        parent::setUp();
        $this->da  = \Mockery::spy(\Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface::class);
        $this->dar = \Mockery::spy(\DataAccessResult::class);
        $this->da->shouldReceive('query')->andReturns($this->dar);
        CodendiDataAccess::setInstance($this->da);
    }

    protected function tearDown() : void
    {
        parent::tearDown();
        CodendiDataAccess::clearInstance();
        unset($GLOBALS['sys_lf'], $GLOBALS['user_id'], $GLOBALS['db_qhandle']);
    }

    public function testALL() : void
    {
        $GLOBALS['Language'] = \Mockery::spy(\BaseLanguage::class);
        $GLOBALS['Language']->shouldReceive('getText')->with('global', 'on')->andReturns('on');
        $GLOBALS['Language']->shouldReceive('getText')->with('global', 'by')->andReturns('by');
        $GLOBALS['Language']->shouldReceive('getText')->with('global', 'none')->andReturns('none');
        $GLOBALS['Language']->shouldReceive('getText')->with('tracker_import_utils', 'date')->andReturns('date');

      /***************** var setup ***********************
       */

        $at = \Mockery::mock(\ArtifactType::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $at->shouldReceive('getName')->andReturns('TestTracker');
        $at->shouldReceive('allowsAnon')->andReturns(false);
        $at->shouldReceive('getID')->andReturns('123');
        $at->shouldReceive('userIsAdmin')->andReturns(true);

        $submitted_by = \Mockery::spy(ArtifactField::class);
        $submitted_by->shouldReceive('getLabel')->andReturns('Submitted By');
        $submitted_by->shouldReceive('getName')->andReturns('submitted_by');
        $submitted_by->shouldReceive('isEmptyOk')->andReturns(false);
        $submitted_by->shouldReceive('getDisplayType')->andReturns('SB');
        $submitted_by->shouldReceive('isDateField')->andReturns(false);
        $submitted_by->shouldReceive('isSelectBox')->andReturns(false);
        $submitted_by->shouldReceive('isMultiSelectBox')->andReturns(false);

        $submitted_on = \Mockery::spy(ArtifactField::class);
        $submitted_on->shouldReceive('getLabel')->andReturns('Submitted On');
        $submitted_on->shouldReceive('getName')->andReturns('open_date');
        $submitted_on->shouldReceive('isEmptyOk')->andReturns(false);
        $submitted_on->shouldReceive('getDisplayType')->andReturns('DF');
        $submitted_on->shouldReceive('isDateField')->andReturns(false);
        $submitted_on->shouldReceive('isSelectBox')->andReturns(false);
        $submitted_on->shouldReceive('isMultiSelectBox')->andReturns(false);

        $last_update_date = \Mockery::spy(ArtifactField::class);
        $last_update_date->shouldReceive('getLabel')->andReturns('Last Modified On');
        $last_update_date->shouldReceive('getName')->andReturns('last_update_date');
        $last_update_date->shouldReceive('isEmptyOk')->andReturns(false);
        $last_update_date->shouldReceive('getDisplayType')->andReturns('DF');
        $last_update_date->shouldReceive('isDateField')->andReturns(true);
        $last_update_date->shouldReceive('isSelectBox')->andReturns(false);
        $last_update_date->shouldReceive('isMultiSelectBox')->andReturns(false);

        $artifact_id = \Mockery::spy(ArtifactField::class);
        $artifact_id->shouldReceive('getLabel')->andReturns('Artifact Id');
        $artifact_id->shouldReceive('getName')->andReturns('artifact_id');
        $artifact_id->shouldReceive('isEmptyOk')->andReturns(false);
        $artifact_id->shouldReceive('getDisplayType')->andReturns('TF');
        $artifact_id->shouldReceive('isDateField')->andReturns(false);
        $artifact_id->shouldReceive('isSelectBox')->andReturns(false);
        $artifact_id->shouldReceive('isMultiSelectBox')->andReturns(false);

        $comment_type_id = \Mockery::spy(ArtifactField::class);
        $comment_type_id->shouldReceive('getLabel')->andReturns('Comment Type');
        $comment_type_id->shouldReceive('getName')->andReturns('comment_type_id');
        $comment_type_id->shouldReceive('isEmptyOk')->andReturns(true);
        $comment_type_id->shouldReceive('getDisplayType')->andReturns('TF');
        $comment_type_id->shouldReceive('isDateField')->andReturns(false);
        $comment_type_id->shouldReceive('isSelectBox')->andReturns(false);
        $comment_type_id->shouldReceive('isMultiSelectBox')->andReturns(false);

        $assigned_to = \Mockery::spy(ArtifactField::class);
        $assigned_to->shouldReceive('getLabel')->andReturns('Assigned To');
        $assigned_to->shouldReceive('getName')->andReturns('assigned_to');
        $assigned_to->shouldReceive('isEmptyOk')->andReturns(false);
        $assigned_to->shouldReceive('getDisplayType')->andReturns('SB');
        $assigned_to->shouldReceive('isDateField')->andReturns(false);
        $assigned_to->shouldReceive('isSelectBox')->andReturns(true);
        $assigned_to->shouldReceive('isMultiSelectBox')->andReturns(false);

        $orig_subm = \Mockery::spy(ArtifactField::class);
        $orig_subm->shouldReceive('getLabel')->andReturns('Original Submission');
        $orig_subm->shouldReceive('getName')->andReturns('details');
        $orig_subm->shouldReceive('isEmptyOk')->andReturns(false);
        $orig_subm->shouldReceive('getDisplayType')->andReturns('TA');
        $orig_subm->shouldReceive('isDateField')->andReturns(false);
        $orig_subm->shouldReceive('isSelectBox')->andReturns(false);
        $orig_subm->shouldReceive('isMultiSelectBox')->andReturns(false);

        $atf = \Mockery::spy(\ArtifactFieldFactory::class);
        $atf->shouldReceive('getAllUsedFields')->andReturns(array($submitted_by,$submitted_on,$artifact_id,$comment_type_id,$assigned_to,$orig_subm));
        $atf->shouldReceive('getFieldFromName')->with('submitted_by')->andReturns($submitted_by);
        $atf->shouldReceive('getFieldFromName')->with('open_date')->andReturns($submitted_on);
        $atf->shouldReceive('getFieldFromName')->with('last_update_date')->andReturns($last_update_date);
        $atf->shouldReceive('getFieldFromName')->with('artifact_id')->andReturns($artifact_id);
        $atf->shouldReceive('getFieldFromName')->with('assigned_to')->andReturns($assigned_to);
        $atf->shouldReceive('getFieldFromName')->with('comment_type_id')->andReturns($comment_type_id);
        $atf->shouldReceive('getFieldFromName')->with('details')->andReturns($orig_subm);

      /**************** test parseFieldNames ************
       */

        $test = new ArtifactImport($at, $atf, 'group');
        $test->parseFieldNames(array('Submitted By','Submitted On','Assigned To','Original Submission'));
        $this->assertFalse($test->isError());

      // need mandatory field assigned_to
        $test = new ArtifactImport($at, $atf, 'group');
        $test->parseFieldNames(array('Submitted By'));
        $this->assertTrue($test->isError());

      //comment type is not taken into account
        $test = new ArtifactImport($at, $atf, 'group');
        $test->parseFieldNames(array('Comment Type','Assigned To','Original Submission'));
        $this->assertTrue($test->isError());

        $test = new ArtifactImport($at, $atf, 'group');
        $test->parseFieldNames(array($GLOBALS['Language']->getText('project_export_artifact_export', 'follow_up_comments'),
                                   $GLOBALS['Language']->getText('project_export_artifact_export', 'depend_on'),
                                   $GLOBALS['Language']->getText('project_export_artifact_export', 'add_cc_lbl'),
                                   $GLOBALS['Language']->getText('project_export_artifact_export', 'cc_comment_lbl'),
                                   'Assigned To','Original Submission'));
        $this->assertFalse($test->isError());

      /***************** test checkValues *****************
       */

      // can not check submitted_by values (can not get user_id from here)
        $test = new ArtifactImport($at, $atf, 'group');
        $test->parseFieldNames(array($GLOBALS['Language']->getText('project_export_artifact_export', 'follow_up_comments'),
                                   $GLOBALS['Language']->getText('project_export_artifact_export', 'depend_on'),
                                   $GLOBALS['Language']->getText('project_export_artifact_export', 'add_cc_lbl'),
                                   $GLOBALS['Language']->getText('project_export_artifact_export', 'cc_comment_lbl'),
                                   'Assigned To','Original Submission'));
        $test->predefined_values = array();
        $test->predefined_values[4] = array('schneide' => '');
        $data = array($GLOBALS['Language']->getText('tracker_import_utils', 'no_followups'),'','','','schneide','my original submission');
        $test->checkValues('1', $data, false);
        $this->assertFalse($test->isError());

      // schnuffi not in predefined values of assigned_to
        $data = array($GLOBALS['Language']->getText('tracker_import_utils', 'no_followups'),'','','','schnuffi','my original submission');
        $test->checkValues('1', $data, false);
        $this->assertTrue($test->isError());
        $test->clearError();

      //test mb fields
        $mbox_field = \Mockery::spy(ArtifactField::class);
        $mbox_field->shouldReceive('getLabel')->andReturns('MB Field');
        $mbox_field->shouldReceive('getName')->andReturns('mbox_field');
        $mbox_field->shouldReceive('isEmptyOk')->andReturns(true);
        $mbox_field->shouldReceive('getDisplayType')->andReturns('MB');
        $mbox_field->shouldReceive('isDateField')->andReturns(false);
        $mbox_field->shouldReceive('isSelectBox')->andReturns(false);
        $mbox_field->shouldReceive('isMultiSelectBox')->andReturns(true);

        $sbox_field = \Mockery::spy(ArtifactField::class);
        $sbox_field->shouldReceive('getLabel')->andReturns('SB Field');
        $sbox_field->shouldReceive('getName')->andReturns('sbox_field');
        $sbox_field->shouldReceive('isEmptyOk')->andReturns(false);
        $sbox_field->shouldReceive('getDisplayType')->andReturns('SB');
        $sbox_field->shouldReceive('isDateField')->andReturns(false);
        $sbox_field->shouldReceive('isSelectBox')->andReturns(true);
        $sbox_field->shouldReceive('isMultiSelectBox')->andReturns(false);

        $atf = \Mockery::spy(\ArtifactFieldFactory::class);
        $atf->shouldReceive('getAllUsedFields')->andReturns(array($submitted_by,$submitted_on,$artifact_id,$comment_type_id,$assigned_to,$orig_subm,$mbox_field,$sbox_field));
        $atf->shouldReceive('getFieldFromName')->with('submitted_by')->andReturns($submitted_by);
        $atf->shouldReceive('getFieldFromName')->with('open_date')->andReturns($submitted_on);
        $atf->shouldReceive('getFieldFromName')->with('last_update_date')->andReturns($last_update_date);
        $atf->shouldReceive('getFieldFromName')->with('artifact_id')->andReturns($artifact_id);
        $atf->shouldReceive('getFieldFromName')->with('assigned_to')->andReturns($assigned_to);
        $atf->shouldReceive('getFieldFromName')->with('comment_type_id')->andReturns($comment_type_id);
        $atf->shouldReceive('getFieldFromName')->with('details')->andReturns($orig_subm);
        $atf->shouldReceive('getFieldFromName')->with('mbox_field')->andReturns($mbox_field);
        $atf->shouldReceive('getFieldFromName')->with('sbox_field')->andReturns($sbox_field);

        $test = new ArtifactImport($at, $atf, 'group');
        $test->parseFieldNames(array($GLOBALS['Language']->getText('project_export_artifact_export', 'follow_up_comments'),
                                   $GLOBALS['Language']->getText('project_export_artifact_export', 'depend_on'),
                                   $GLOBALS['Language']->getText('project_export_artifact_export', 'add_cc_lbl'),
                                   $GLOBALS['Language']->getText('project_export_artifact_export', 'cc_comment_lbl'),
                                   'Assigned To','Original Submission','MB Field','SB Field'));
        $test->predefined_values = array();
        $test->predefined_values[4] = array('schneide' => '');
        $test->predefined_values[6] = array('one' => '','two' => '','three' => '');
        $test->predefined_values[7] = array('blue' => '','yellow' => '','red' => '');
        $data = array($GLOBALS['Language']->getText('tracker_import_utils', 'no_followups'),'','','','schneide','my original submission',
        'one,two,' . $GLOBALS['Language']->getText('global', 'none'),'yellow');
        $test->checkValues('1', $data, false);
        $this->assertFalse($test->isError());

        $data = array($GLOBALS['Language']->getText('tracker_import_utils', 'no_followups'),'','','','schneide','my original submission','one,two,four','yellow');
        $test->checkValues('1', $data, false);
        $this->assertTrue($test->isError());
        $test->clearError();

      //check mandatory fields
        $data = array($GLOBALS['Language']->getText('tracker_import_utils', 'no_followups'),'','','','schneide','','one,two,four','yellow');
        $test->checkValues('1', $data, false);
        $this->assertTrue($test->isError());
        $test->clearError();

        $data = array($GLOBALS['Language']->getText('tracker_import_utils', 'no_followups'),'','','','schneide','my original submission','one,two,four',$GLOBALS['Language']->getText('global', 'none'));
        $test->checkValues('1', $data, false);
        $this->assertTrue($test->isError());
        $test->clearError();

      //test date format
      //submitted on is allowed to be void, we set it to current date on insert into DB
        $test = new ArtifactImport($at, $atf, 'group');
        $test->parseFieldNames(array($GLOBALS['Language']->getText('project_export_artifact_export', 'follow_up_comments'),
                                   $GLOBALS['Language']->getText('project_export_artifact_export', 'depend_on'),
                                   $GLOBALS['Language']->getText('project_export_artifact_export', 'add_cc_lbl'),
                                   $GLOBALS['Language']->getText('project_export_artifact_export', 'cc_comment_lbl'),
                                   'Assigned To','Original Submission','MB Field','SB Field','Submitted On'));
        $data = array($GLOBALS['Language']->getText('tracker_import_utils', 'no_followups'),'','','','schneide','my original submission','one,two,four','yellow','');
        $test->checkValues('1', $data, false);
        $this->assertFalse($test->isError());

      //sys_date_fmt
        $data = array($GLOBALS['Language']->getText('tracker_import_utils', 'no_followups'),'','','','schneide','my original submission','one,two,four','yellow','2004-Feb-03 16:13');
        $test->checkValues('1', $data, false);
        $this->assertFalse($test->isError());

      //xls date format
        $data = array($GLOBALS['Language']->getText('tracker_import_utils', 'no_followups'),'','','','schneide','my original submission','one,two,four','yellow','2/3/2004 16:13');
        $test->checkValues('1', $data, false);
        $this->assertFalse($test->isError());

      //short sys_date_fmt
        $data = array($GLOBALS['Language']->getText('tracker_import_utils', 'no_followups'),'','','','schneide','my original submission','one,two,four','yellow','2004-Feb-03');
        $test->checkValues('1', $data, false);
        $this->assertFalse($test->isError());

      //short xls date format
        $data = array($GLOBALS['Language']->getText('tracker_import_utils', 'no_followups'),'','','','schneide','my original submission','one,two,four','yellow','2/3/2004');
        $test->checkValues('1', $data, false);
        $this->assertFalse($test->isError());

      /***************** test parseFollowUpComments *****************
       */

        $aff = \Mockery::spy(\ArtifactFieldFactory::class);
        $aff->shouldReceive('getAllUsedFields')->andReturns(array());
        $aff->shouldReceive('getFieldFromName')->with('submitted_by')->andReturns($submitted_by);
        $aff->shouldReceive('getFieldFromName')->with('open_date')->andReturns($submitted_on);
        $aff->shouldReceive('getFieldFromName')->with('last_update_date')->andReturns($last_update_date);

        $test = new ArtifactImport($at, $aff, 'group');
        $test->parseFieldNames(array($GLOBALS['Language']->getText('project_export_artifact_export', 'follow_up_comments')));
        $parsed_comments = array();
        $art_id = '1149';

        $followup_comments = "Follow-ups
**********

------------------------------------------------------------------
" . $GLOBALS['Language']->getText('tracker_import_utils', 'date') . ": 2005-09-02 18:18              " . $GLOBALS['Language']->getText('global', 'by') . ": doswald
8/17/2004 4:21:57 PM New Entry
8/17/2004 4:24:38 PM DCO: Accepted for investigation, Prio Major 2 Assigned Cyrkin, Tonya
9/14/2004 2:13:03 PM DCO: Waiting on new database from Craig DeHond.
";

        $um = \Mockery::spy(\UserManager::class);
        $ai = \Mockery::mock(\ArtifactImport::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $user = \Mockery::spy(\PFUser::class);

        $ai->shouldReceive('getUserManager')->andReturns($um);
        $um->shouldReceive('getUserByUserName')->andReturns($user);

        $ai->__construct($at, $aff, 'group');

        $ai->parseFollowUpComments($followup_comments, $parsed_comments, $art_id, true);

        $this->assertFalse($ai->isError());
        $this->assertEquals('2005-09-02 18:18', $parsed_comments[0]['date']);
        $this->assertEquals($GLOBALS['Language']->getText('global', 'none'), $parsed_comments[0]['type']);
        $this->assertEquals('doswald', $parsed_comments[0]['by']);

        $parsed_comments = array();
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
        $this->assertFalse($ai->isError());
        $this->assertEquals('2005-10-19 18:28', $parsed_comments[0]['date']);
        $this->assertEquals($GLOBALS['Language']->getText('global', 'none'), $parsed_comments[0]['type'], $GLOBALS['Language']->getText('global', 'none'));
        $this->assertEquals('doswald', $parsed_comments[0]['by']);
        $this->assertEquals('2005-09-02 16:51', $parsed_comments[1]['date']);
        $this->assertEquals($GLOBALS['Language']->getText('global', 'none'), $parsed_comments[1]['type']);
        $this->assertEquals('doswald', $parsed_comments[1]['by']);

        $parsed_comments = array();

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
        $this->assertFalse($test->isError());
        $this->assertEquals('2000-12-09 00:08', $parsed_comments[0]['date']);
        $this->assertEquals($GLOBALS['Language']->getText('global', 'none'), $parsed_comments[0]['type']);
        $this->assertEquals('jstidd', $parsed_comments[0]['by']);
        $this->assertEquals('2000-12-08 23:06', $parsed_comments[1]['date']);
        $this->assertEquals($GLOBALS['Language']->getText('global', 'none'), $parsed_comments[1]['type']);
        $this->assertEquals('jstidd', $parsed_comments[1]['by']);
        $this->assertEquals($GLOBALS['Language']->getText('global', 'none'), $parsed_comments[2]['type']);
        $this->assertEquals('2000-12-08 22:30', $parsed_comments[2]['date']);
        $this->assertEquals($GLOBALS['Language']->getText('global', 'none'), $parsed_comments[3]['type']);
        $this->assertEquals('2000-12-08 22:27', $parsed_comments[3]['date']);

        $parsed_comments = array();
        $test->parseFollowUpComments($followup_comments, $parsed_comments, $art_id, true);
        $this->assertFalse($test->isError());
        $this->assertEquals('2000-12-09 00:08', $parsed_comments[0]['date']);
        $this->assertEquals($GLOBALS['Language']->getText('global', 'none'), $parsed_comments[0]['type']);
        $this->assertEquals('jstidd', $parsed_comments[0]['by']);
        $this->assertEquals('2000-12-08 23:06', $parsed_comments[1]['date']);
        $this->assertEquals($GLOBALS['Language']->getText('global', 'none'), $parsed_comments[1]['type']);
        $this->assertEquals('jstidd', $parsed_comments[1]['by']);
        $this->assertEquals($GLOBALS['Language']->getText('global', 'none'), $parsed_comments[2]['type']);
        $this->assertEquals('2000-12-08 22:30', $parsed_comments[2]['date']);
        $this->assertEquals($GLOBALS['Language']->getText('global', 'none'), $parsed_comments[3]['type']);
        $this->assertEquals('2000-12-08 22:27', $parsed_comments[3]['date']);

      /**
      check by hand:
      *  double submission (can not access DB from here)
      *  not enough columns in a row
      *  real insertion, real update (can not access DB)
      *  follow-up comment is already in DB or not
      */
    }

    public function testSplitFollowUpComments() : void
    {
        $aitv = \Mockery::mock(\ArtifactImport::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $followup_comments = file_get_contents(__DIR__ . '/_fixtures/followup_comments1.txt');
        $comments = $aitv->splitFollowUpComments($followup_comments);
        $this->assertCount(4 + 1, $comments); // + 1 because the follow-up comments header is returned
    }

    public function testCanApplyHtmlSpecialCharsWithBaseTranslation() : void
    {
        $ai = \Mockery::mock(\ArtifactImport::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->assertTrue($ai->canApplyHtmlSpecialChars('"'));
        $this->assertTrue($ai->canApplyHtmlSpecialChars('<'));
        $this->assertTrue($ai->canApplyHtmlSpecialChars('>'));
        $this->assertTrue($ai->canApplyHtmlSpecialChars('&'));
        $this->assertFalse($ai->canApplyHtmlSpecialChars("'"));
    }

    public function testCanApplyHtmlSpecialCharsWithTranslatedChars() : void
    {
        $ai = \Mockery::mock(\ArtifactImport::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->assertFalse($ai->canApplyHtmlSpecialChars('&quot;'));
        $this->assertFalse($ai->canApplyHtmlSpecialChars('&lt;'));
        $this->assertFalse($ai->canApplyHtmlSpecialChars('&gt;'));
        $this->assertFalse($ai->canApplyHtmlSpecialChars('&amp;'));
        $this->assertTrue($ai->canApplyHtmlSpecialChars('&#039;'));
    }

    public function testCanApplyHtmlSpecialCharsWithAdvancedHTMLTricks() : void
    {
        $ai = \Mockery::mock(\ArtifactImport::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->assertFalse($ai->canApplyHtmlSpecialChars("&lt;p&gt;this is 'my test'&lt;/p&gt;"));
        $this->assertTrue($ai->canApplyHtmlSpecialChars("<p>this is 'my test'</p>"));
        $this->assertEquals("&lt;p&gt;this is 'my test'&lt;/p&gt;", htmlspecialchars("<p>this is 'my test'</p>"));

        $this->assertFalse($ai->canApplyHtmlSpecialChars("&lt;p&gt;&amp;lt;toto&amp;gt;&lt;/p&gt;"));
        $this->assertTrue($ai->canApplyHtmlSpecialChars("<p>&lt;toto&gt;</p>"));
        $this->assertEquals("&lt;p&gt;&amp;lt;toto&amp;gt;&lt;/p&gt;", htmlspecialchars("<p>&lt;toto&gt;</p>"));

        $this->assertFalse($ai->canApplyHtmlSpecialChars("test&lt;br/&gt;"));
        $this->assertTrue($ai->canApplyHtmlSpecialChars("test<br/>"));
        $this->assertEquals("test&lt;br/&gt;", htmlspecialchars("test<br/>"));
    }

    /**
     * This case is impossible to catch so it's a known error.
     *
     * It might happens if, on the web, the user entered *as text* HTML entities
     * (for instance &lt;), then exported it in CSV and finaly imported it with
     * CSV as well.
     */
    public function testUnCatchableStrings() : void
    {
        $ai = \Mockery::mock(\ArtifactImport::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $this->assertFalse($ai->canApplyHtmlSpecialChars("Test&amp;lt;"));
        $this->assertEquals("Test&amp;lt;", htmlspecialchars("Test&lt;"));
        // Should be assertTrue here
        $this->assertFalse($ai->canApplyHtmlSpecialChars("Test&lt;"));
    }

    public function testCanApplyHtmlSpecialCharsWithRealTextTricks() : void
    {
        $ai = \Mockery::mock(\ArtifactImport::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->assertTrue($ai->canApplyHtmlSpecialChars('"Description"'));
        $this->assertFalse($ai->canApplyHtmlSpecialChars("Following today's Codex framework update, it looks better in the sense I now have access to all charts."));
        $this->assertTrue($ai->canApplyHtmlSpecialChars('&&lt;'));
        $this->assertTrue($ai->canApplyHtmlSpecialChars('&&gt;'));
        $this->assertTrue($ai->canApplyHtmlSpecialChars('&&amp;'));
        $this->assertTrue($ai->canApplyHtmlSpecialChars('&&quot;'));
    }

    public function testCheckCommentExistInLegacyFormat() : void
    {
        $this->da->shouldReceive('numRows')->andReturns(1);
        $this->da->shouldReceive('fetchArray')->andReturns(array ('new_value' => '<pre> testing issue </pre>'));
        $this->dar->shouldReceive('getResult')->andReturns(true);
        $artImp = \Mockery::mock(\ArtifactImport::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $artId = 12237;
        $parsedFollow = array('comment' => '<pre> testing issue </pre>');
        $this->assertTrue($artImp->checkCommentExistInLegacyFormat($parsedFollow, $artId));
    }
}
