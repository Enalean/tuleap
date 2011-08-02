<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
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
require_once(dirname(__FILE__).'/../include/Tracker_Artifact_Changeset.class.php');
Mock::generatePartial(
    'Tracker_Artifact_Changeset', 
    'Tracker_Artifact_ChangesetTestVersion', 
    array(
        'getId', 
        'getValueDao', 
        'getFormElementFactory', 
        'getArtifact',
        'sendNotification',
        'getUserManager',
        'getTracker',
    )
);

Mock::generate('Tracker_Artifact_Changeset');
require_once(dirname(__FILE__).'/../include/Tracker_FormElement_Field_Date.class.php');
Mock::generate('Tracker_FormElement_Field_Date');
require_once(dirname(__FILE__).'/../include/dao/Tracker_Artifact_Changeset_ValueDao.class.php');
Mock::generate('Tracker_Artifact_Changeset_ValueDao');
require_once('common/dao/include/DataAccessResult.class.php');
Mock::generate('DataAccessResult');
require_once(dirname(__FILE__).'/../include/Tracker_FormElementFactory.class.php');
Mock::generate('Tracker_FormElementFactory');
require_once(dirname(__FILE__).'/../include/Tracker_Artifact_ChangesetValue_Date.class.php');
Mock::generate('Tracker_Artifact_ChangesetValue_Date');
require_once(dirname(__FILE__).'/../include/Tracker_Artifact_ChangesetValue_List.class.php');
Mock::generate('Tracker_Artifact_ChangesetValue_List');
require_once(dirname(__FILE__).'/../include/Tracker_FormElement_Field_Selectbox.class.php');
Mock::generate('Tracker_FormElement_Field_Selectbox');
require_once(dirname(__FILE__).'/../include/Tracker_Artifact.class.php');
Mock::generate('Tracker_Artifact');
require_once(dirname(__FILE__).'/../include/Tracker.class.php');
Mock::generate('Tracker');
require_once('common/user/UserManager.class.php');
Mock::generate('UserManager');

class Tracker_Artifact_ChangesetTest extends UnitTestCase {
    function _testGetValue() {
        $field = new MockTracker_FormElement_Field_Date();
        $value = new MockTracker_Artifact_ChangesetValue_Date();
        $dao   = new MockTracker_Artifact_Changeset_ValueDao();
        $dar   = new MockDataAccessResult();
        $fact  = new MockTracker_FormElementFactory();
        $value = new MockTracker_Artifact_ChangesetValue_Date();
        $um    = new MockUserManager();
        
        $dar->setReturnValue('current', array('changeset_id' => 1, 'field_id' => 2, 'id' => 3, 'has_changed' => 0));
        $dar->setReturnValueAt(0, 'valid', true);
        $dar->setReturnValue('valid', false);
        $dao->setReturnReference('searchById', $dar);
        
        $fact->setReturnReference('getFieldById', $field);
        
        $field->setReturnValue('getId', 2);
        $field->setReturnReference('getChangesetValue', $value);
        
        $changeset = new Tracker_Artifact_ChangesetTestVersion();
        $changeset->setReturnReference('getValueDao', $dao);
        $changeset->setReturnReference('getFormElementFactory', $fact);
        $changeset->setReturnReference('getUserManager', $um);
        
        $this->assertIsA($changeset->getValue($field), 'Tracker_Artifact_ChangesetValue_Date');
    }
    
    function testDiffToPrevious() {
        $field1             = new MockTracker_FormElement_Field_Date();
        $value1_previous    = new MockTracker_Artifact_ChangesetValue_Date();
        $value1_current     = new MockTracker_Artifact_ChangesetValue_Date();
        $field2             = new MockTracker_FormElement_Field_Date();
        $value2_previous    = new MockTracker_Artifact_ChangesetValue_Date();
        $value2_current     = new MockTracker_Artifact_ChangesetValue_Date();
        $dao                = new MockTracker_Artifact_Changeset_ValueDao();
        $dar                = new MockDataAccessResult();
        $fact               = new MockTracker_FormElementFactory();
        $value              = new MockTracker_Artifact_ChangesetValue_Date();
        $artifact           = new MockTracker_Artifact();
        $previous_changeset = new MockTracker_Artifact_Changeset();
        $um                 = new MockUserManager();
        
        $current_changeset = new Tracker_Artifact_ChangesetTestVersion();
        
        $previous_changeset->setReturnValue('getId', 65);
        $previous_changeset->setReturnReference('getValue', $value1_previous, array($field1));
        $previous_changeset->setReturnReference('getValue', $value2_previous, array($field2));
        $previous_changeset->setReturnReference('getUserManager', $um);
        
        $artifact->setReturnReference('getPreviousChangeset', $previous_changeset, array(66));
        
        $dar->setReturnValueAt(0, 'current', array('changeset_id' => 66, 'field_id' => 1, 'id' => 11, 'has_changed' => 1));
        $dar->setReturnValueAt(1, 'current', array('changeset_id' => 66, 'field_id' => 2, 'id' => 21, 'has_changed' => 0));
        $dar->setReturnValue('valid', true);
        $dar->setReturnValueAt(2, 'valid', false);
        $dao->setReturnReference('searchById', $dar);
        
        $fact->setReturnReference('getFieldById', $field1, array(1));
        $fact->setReturnReference('getFieldById', $field2, array(2));
        
        $field1->setReturnValue('getId', 1);
        $field1->setReturnValue('getLabel', 'field1');
        $field1->setReturnValue('userCanRead', true);
        $field1->setReturnReference('getChangesetValue', $value1_current, array('*', 11, 1));
        
        $value1_previous->expectNever('hasChanged');
        $value1_current->setReturnValue('hasChanged', true);
        $value1_current->setReturnValue('diff', 'has changed', array($value1_previous, '*'));
        
        $field2->setReturnValue('getId', 2);
        $field2->setReturnValue('getLabel', 'field2');
        $field2->setReturnValue('userCanRead', true);
        $field2->setReturnReference('getChangesetValue', $value2_current, array('*', 21, 0));
        
        $value2_previous->expectNever('hasChanged');
        $value2_current->setReturnValue('hasChanged', false);
        $value2_current->expectNever('diff'); 
        
        
        
        $current_changeset->setReturnValue('getId', 66);
        $current_changeset->setReturnReference('getValueDao', $dao);
        $current_changeset->setReturnReference('getFormElementFactory', $fact);
        $current_changeset->setReturnReference('getArtifact', $artifact);
        $current_changeset->setReturnReference('getUserManager', $um);
        
        $this->assertPattern('/field1/', $current_changeset->diffToprevious());
        $this->assertNoPattern('/field2/', $current_changeset->diffToprevious());
    }
    
    function testNotify() {
        $defs = array(
            array(
                'has_changed'              => 1,
                'isNotificationsSupported' => 1,
                'hasNotifications'         => 0,
                'recipients'               => array(),
            ),
            array(
                'has_changed'              => 0,
                'isNotificationsSupported' => 1,
                'hasNotifications'         => 1,
                'recipients'               => array('a_user'),
            ),
            array(
                'has_changed'              => 0,
                'isNotificationsSupported' => 1,
                'hasNotifications'         => 0,
                'recipients'               => array('should_not_appear'),
            ),
            array(
                'has_changed'              => 0,
                'isNotificationsSupported' => 0,
                'hasNotifications'         => 1,
                'recipients'               => array('should_not_appear_(not_supported)'),
            ),
            array(
                'has_changed'              => 0,
                'isNotificationsSupported' => 1,
                'hasNotifications'         => 1,
                'recipients'               => array('multiple_users', 'email@example.com'),
            ),
        );
        
        $fact     = new MockTracker_FormElementFactory();
        $dar      = new MockDataAccessResult();
        $dao      = new MockTracker_Artifact_Changeset_ValueDao();
        $artifact = new MockTracker_Artifact();
        $tracker  = new MockTracker();
        $um       = new MockUserManager();
        
        $i = 0;
        // try DRY in unit tests also... build mocks automatically
        foreach ($defs as $d) {
            $id = $i + 1;
            $changeset_value_id = 1000 + $id;
            $dar->setReturnValueAt($i++, 'current', array(
                'changeset_id' => 66, 
                'field_id'     => $id, 
                'id'           => $changeset_value_id, 
                'has_changed'  => $d['has_changed'],
            ));
            $f = new MockTracker_FormElement_Field_Selectbox();
            $fact->setReturnReference('getFieldById', $f, array($id));
            $f->setReturnValue('getId', $id);
            $f->setReturnValue('getLabel', ('field_'.$id));
            $f->setReturnValue('isNotificationsSupported', $d['isNotificationsSupported']);
            $f->setReturnValue('hasNotifications', $d['hasNotifications']);
            $f->setReturnValue('getRecipients', $d['recipients']);
            
            $p = new MockTracker_Artifact_ChangesetValue_List();
            $c = new MockTracker_Artifact_ChangesetValue_List();
            $c->setReturnValue('hasChanged', $d['has_changed']);
            $c->setReturnValue('diff', 'has changed', array($p));
            $f->setReturnReference('getChangesetValue', $c, array('*', $changeset_value_id, $d['has_changed']));
            unset($f);
        }
        $dar->setReturnValue('valid', true);
        $dar->setReturnValueAt($i, 'valid', false);
        $dao->setReturnReference('searchById', $dar);
        
        $artifact->setReturnReference('getTracker', $tracker);
        $artifact->setReturnValue('getCommentators', array('comment1', 'comment2'));
        $artifact->setReturnValue('getId', 666);
        
        $tracker->setReturnValue('getItemName', 'story');
        $tracker->setReturnValue('isNotificationStopped', false);
        $tracker->setReturnValue('getRecipients', array(
            array(
                'recipients'        => array(
                    'global1',
                    'global2',
                ),
                'on_updates'        => false,
                'check_permissions' => true,
            ),
            array(
                'recipients'        => array(
                    'dont_check_perms',
                    'global3',
                    'email@example.com',
                ),
                'on_updates'        => true,
                'check_permissions' => false,
            ),
        ));
        $current_changeset = new Tracker_Artifact_ChangesetTestVersion();
        $current_changeset->setReturnValue('getId', 66);
        $current_changeset->setReturnReference('getValueDao', $dao);
        $current_changeset->setReturnReference('getFormElementFactory', $fact);
        $current_changeset->setReturnReference('getArtifact', $artifact);
        $current_changeset->setReturnReference('getUserManager', $um);
        $current_changeset->setReturnReference('getTracker', $tracker);
        
        $expected_body = <<<BODY
story #666
<http://{$GLOBALS['sys_default_domain']}/tracker/?aid=666>

BODY;
        $current_changeset->expect(
            'sendNotification', 
            array(
                //the recipients
                array(
                    'a_user', 
                    'multiple_users', 
                    'email@example.com', 
                    'dont_check_perms', 
                    'global3',
                    'comment1',
                    'comment2',
                ),
                
                //the headers
                array(),
                
                //the subject
                '[story #666]',
                
                //the body
                $expected_body
            )
        );
        $current_changeset->notify();
    }
    
    function testNotifyStopped() {
        $changeset = new Tracker_Artifact_ChangesetTestVersion();
        $tracker  = new MockTracker();
        $tracker->setReturnValue('isNotificationStopped', true);
        $changeset->setReturnReference('getTracker', $tracker);
        $changeset->expectNever('getFormElementFactory');
        $changeset->expectNever('sendNotification');
        $changeset->notify();
    }

}
?>
