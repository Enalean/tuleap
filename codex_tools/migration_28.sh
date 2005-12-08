ALTER TABLE `plugin` CHANGE `enabled` `available` TINYINT( 4 ) DEFAULT '0' NOT NULL 

ajouter $sys_custompluginsroot dans local.inc



-- SR #282 on partners: simplify status field
-- create stage field for Bug, Tasks, SR and Empty tracker templates (Patch tracker already has the field response)

INSERT INTO artifact_field VALUES (30,1,'stage',2,'SB','','Stage','Stage in the life cycle of the artifact','',0,0,1,0,NULL,'1');
INSERT INTO artifact_field VALUES (15,2,'stage',2,'SB','','Stage','Stage in the life cycle of the artifact','',0,0,1,0,NULL,'1');
INSERT INTO artifact_field VALUES (12,3,'stage',2,'SB','','Stage','Stage in the life cycle of the artifact','',0,0,1,0,NULL,'1');
INSERT INTO artifact_field VALUES (11,4,'stage',2,'SB','','Stage','Stage in the life cycle of the artifact','',0,0,1,0,NULL,'1');

DELETE FROM artifact_field WHERE group_artifact_id = 5 AND field_id = 12;
INSERT INTO artifact_field VALUES (12,5,'stage',2,'SB','','Stage','Stage in the life cycle of the artifact','P',0,1,1,0,NULL,'1');

INSERT INTO artifact_field_usage VALUES (30,1,0,0);
INSERT INTO artifact_field_usage VALUES (15,2,0,0);
INSERT INTO artifact_field_usage VALUES (12,3,0,0);
INSERT INTO artifact_field_usage VALUES (11,4,0,0);

-- delete values for status field

DELETE FROM artifact_field_value_list WHERE group_artifact_id = 1 AND field_id = 2 AND status != 'P' AND value_id > 3;
DELETE FROM artifact_field_value_list WHERE group_artifact_id = 2 AND field_id = 11 AND status != 'P' AND value_id > 3;
DELETE FROM artifact_field_value_list WHERE group_artifact_id = 3 AND field_id = 7 AND status != 'P' AND value_id > 3;
DELETE FROM artifact_field_value_list WHERE group_artifact_id = 4 AND field_id = 6 AND status != 'P' AND value_id > 3;
DELETE FROM artifact_field_value_list WHERE group_artifact_id = 5 AND field_id = 9 AND status != 'P' AND value_id > 3;

-- create field values for new stage field

INSERT INTO artifact_field_value_list VALUES (30,1,1,'New','The artifact has just been submitted',20,'A');
INSERT INTO artifact_field_value_list VALUES (30,1,2,'Analyzed','The cause of the artifact has been identified and documented',30,'A');
INSERT INTO artifact_field_value_list VALUES (30,1,3,'Accepted','The artifact will be worked on.',40,'A');
INSERT INTO artifact_field_value_list VALUES (30,1,4,'Under Implementation','The artifact is being worked on.',50,'A');

INSERT INTO artifact_field_value_list VALUES (30,1,5,'Ready for Review','Updated/Created non-software work product (e.g. documentation) is ready for review and approval.',60,'A');
INSERT INTO artifact_field_value_list VALUES (30,1,6,'Ready for Test','Updated/Created software is ready to be included in the next build',70,'A');
INSERT INTO artifact_field_value_list VALUES (30,1,7,'In Test','Updated/Created software is in the build and is ready to enter the test phase',80,'A');
INSERT INTO artifact_field_value_list VALUES (30,1,8,'Approved','The artifact fix has been succesfully tested. It is approved and awaiting release.',90,'A');
INSERT INTO artifact_field_value_list VALUES (30,1,9,'Declined','The artifact was not accepted.',100,'A');
INSERT INTO artifact_field_value_list VALUES (30,1,10,'Done','The artifact is closed.',110,'A');

INSERT INTO artifact_field_value_list VALUES (15,2,1,'New','The artifact has just been submitted',20,'A');
INSERT INTO artifact_field_value_list VALUES (15,2,2,'Analyzed','The cause of the artifact has been identified and documented',30,'A');
INSERT INTO artifact_field_value_list VALUES (15,2,3,'Accepted','The artifact will be worked on.',40,'A');
INSERT INTO artifact_field_value_list VALUES (15,2,4,'Under Implementation','The artifact is being worked on.',50,'A');

INSERT INTO artifact_field_value_list VALUES (15,2,5,'Ready for Review','Updated/Created non-software work product (e.g. documentation) is ready for review and approval.',60,'A');
INSERT INTO artifact_field_value_list VALUES (15,2,6,'Ready for Test','Updated/Created software is ready to be included in the next build',70,'A');
INSERT INTO artifact_field_value_list VALUES (15,2,7,'In Test','Updated/Created software is in the build and is ready to enter the test phase',80,'A');
INSERT INTO artifact_field_value_list VALUES (15,2,8,'Approved','The artifact fix has been succesfully tested. It is approved and awaiting release.',90,'A');
INSERT INTO artifact_field_value_list VALUES (15,2,9,'Declined','The artifact was not accepted.',100,'A');
INSERT INTO artifact_field_value_list VALUES (15,2,10,'Done','The artifact is closed.',110,'A');


INSERT INTO artifact_field_value_list VALUES (12,3,1,'New','The artifact has just been submitted',20,'A');
INSERT INTO artifact_field_value_list VALUES (12,3,2,'Analyzed','The cause of the artifact has been identified and documented',30,'A');
INSERT INTO artifact_field_value_list VALUES (12,3,3,'Accepted','The artifact will be worked on.',40,'A');
INSERT INTO artifact_field_value_list VALUES (12,3,4,'Under Implementation','The artifact is being worked on.',50,'A');

INSERT INTO artifact_field_value_list VALUES (12,3,5,'Ready for Review','Updated/Created non-software work product (e.g. documentation) is ready for review and approval.',60,'A');
INSERT INTO artifact_field_value_list VALUES (12,3,6,'Ready for Test','Updated/Created software is ready to be included in the next build',70,'A');
INSERT INTO artifact_field_value_list VALUES (12,3,7,'In Test','Updated/Created software is in the build and is ready to enter the test phase',80,'A');
INSERT INTO artifact_field_value_list VALUES (12,3,8,'Approved','The artifact fix has been succesfully tested. It is approved and awaiting release.',90,'A');
INSERT INTO artifact_field_value_list VALUES (12,3,9,'Declined','The artifact was not accepted.',100,'A');
INSERT INTO artifact_field_value_list VALUES (12,3,10,'Done','The artifact is closed.',110,'A');


INSERT INTO artifact_field_value_list VALUES (11,4,1,'New','The artifact has just been submitted',20,'A');
INSERT INTO artifact_field_value_list VALUES (11,4,2,'Analyzed','The cause of the artifact has been identified and documented',30,'A');
INSERT INTO artifact_field_value_list VALUES (11,4,3,'Accepted','The artifact will be worked on.',40,'A');
INSERT INTO artifact_field_value_list VALUES (11,4,4,'Under Implementation','The artifact is being worked on.',50,'A');

INSERT INTO artifact_field_value_list VALUES (11,4,5,'Ready for Review','Updated/Created non-software work product (e.g. documentation) is ready for review and approval.',60,'A');
INSERT INTO artifact_field_value_list VALUES (11,4,6,'Ready for Test','Updated/Created software is ready to be included in the next build',70,'A');
INSERT INTO artifact_field_value_list VALUES (11,4,7,'In Test','Updated/Created software is in the build and is ready to enter the test phase',80,'A');
INSERT INTO artifact_field_value_list VALUES (11,4,8,'Approved','The artifact fix has been succesfully tested. It is approved and awaiting release.',90,'A');
INSERT INTO artifact_field_value_list VALUES (11,4,9,'Declined','The artifact was not accepted.',100,'A');
INSERT INTO artifact_field_value_list VALUES (11,4,10,'Done','The artifact is closed.',110,'A');


DELETE FROM artifact_field_value_list WHERE group_artifact_id = 5 AND field_id = 12;

INSERT INTO artifact_field_value_list VALUES (12,5,1,'New','The artifact has just been submitted',20,'A');
INSERT INTO artifact_field_value_list VALUES (12,5,2,'Analyzed','The cause of the artifact has been identified and documented',30,'A');
INSERT INTO artifact_field_value_list VALUES (12,5,3,'Accepted','The artifact will be worked on.',40,'A');
INSERT INTO artifact_field_value_list VALUES (12,5,4,'Under Implementation','The artifact is being worked on.',50,'A');

INSERT INTO artifact_field_value_list VALUES (12,5,5,'Ready for Review','Updated/Created non-software work product (e.g. documentation) is ready for review and approval.',60,'A');
INSERT INTO artifact_field_value_list VALUES (12,5,6,'Ready for Test','Updated/Created software is ready to be included in the next build',70,'A');
INSERT INTO artifact_field_value_list VALUES (12,5,7,'In Test','Updated/Created software is in the build and is ready to enter the test phase',80,'A');
INSERT INTO artifact_field_value_list VALUES (12,5,8,'Approved','The artifact fix has been succesfully tested. It is approved and awaiting release.',90,'A');
INSERT INTO artifact_field_value_list VALUES (12,5,9,'Declined','The artifact was not accepted.',100,'A');
INSERT INTO artifact_field_value_list VALUES (12,5,10,'Done','The artifact is closed.',110,'A');