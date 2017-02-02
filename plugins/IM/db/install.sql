## 
## Sql Install Script
##

# Create IM Admin group
# Do not add real users to this group.
INSERT INTO groups SET 
  group_id = '99', 
  group_name = 'IM Administrators', 
  access = 'private',
  status = 'A', 
  unix_group_name = 'imadmingroup', 
  unix_box = 'shell1', 
  http_domain = null, 
  short_description = 'Group administrators of the IM server (OpenFire). DO *NOT* add real users to this group.', 
  register_time = 940000000, 
  rand_hash = '', 
  type = '1';


# Services
insert into service (group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (99, 'service_summary_lbl_key', 'service_summary_desc_key', 'summary', '/projects/sitenews/', 1, 1, 'system', 10);
insert into service (group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (99, 'service_admin_lbl_key', 'service_admin_desc_key', 'admin', '/project/admin/?group_id=46', 1, 1, 'system', 20);



# imadmin-bot user
# This is a "bot": do not try to log on the Web interface or a shell with this user!
# His passwd is "1M@dm1n" (needed by openfire)
INSERT INTO user SET 
        user_id = '99', 
        user_name = 'imadmin-bot', 
        email = '', 
        user_pw = '5127685c3da658593e6bac55e441c175', 
        realname = 'IM Administrator (do not delete)', 
        register_purpose = NULL, 
        status = 'S',
        shell = '/sbin/nologin',
        unix_pw = '***',
        unix_status = 'N',
        unix_uid = 0,
        unix_box = 'shell1',
        ldap_id = NULL, 
        add_date = 940000000, 
        confirm_hash = NULL, 
        authorized_keys = NULL, 
        timezone = 'GMT',
        language_id = 'en_US', 
        last_pwd_update = '0'; 

INSERT INTO user_access (user_id, last_access_date) VALUES (99 , '0');

# Make the 'imadmin' user part of the IM Admin Project so that he
# is also an openfire admin.

INSERT INTO user_group SET  user_id=99, group_id=99, admin_flags='A';

INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES ( 100 , 'plugin_im:service_lbl_key' , 'plugin_im:service_desc_key' , 'IM', '/plugins/IM/?group_id=$group_id', 1 , 0 , 'system',  210 );

-- Create service for all other projects (but disabled)
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank)
SELECT DISTINCT group_id , 'plugin_im:service_lbl_key' , 'plugin_im:service_desc_key' , 'IM', CONCAT('/plugins/IM/?group_id=', group_id), 1 , 0 , 'system',  210
FROM service
WHERE group_id NOT IN (SELECT group_id
    FROM service
    WHERE short_name
    LIKE 'IM');
    
INSERT INTO reference SET 
    keyword='chat', 
    description='plugin_im:reference_chat_desc_key', 
    link='/plugins/IM/?group_id=$group_id&action=viewchatlog&chat_log=$1', 
    scope='S', 
    service_short_name='IM',
    nature='im_chat';
INSERT INTO reference_group (reference_id, group_id, is_active)
SELECT last_insert_id, group_id, 1
FROM (SELECT LAST_INSERT_ID() as last_insert_id) AS R, groups; 
