## 
## Sql Install Script
##

# Create IM Admin group
# Do not add real users to this group.
INSERT INTO groups SET 
  group_id = '47', 
  group_name = 'IM Administrators', 
  is_public = '0', 
  status = 'A', 
  unix_group_name = 'imadmingroup', 
  unix_box = 'shell1', 
  http_domain = null, 
  short_description = 'Group administrators of the IM server (OpenFire). DO *NOT* add real users to this group.', 
  register_time = 940000000, 
  rand_hash = '', 
  hide_members = '0', 
  type = '1';


# Services
insert into service (group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (47, 'service_summary_lbl_key', 'service_summary_desc_key', 'summary', '/projects/sitenews/', 1, 1, 'system', 10);
insert into service (group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (47, 'service_admin_lbl_key', 'service_admin_desc_key', 'admin', '/project/admin/?group_id=46', 1, 1, 'system', 20);



# imadmin-bot user
# This is a "bot": do not try to log on the Web interface with this user!
# His passwd is "1M@dm1n" (needed by openfire)
INSERT INTO user SET 
        user_id = '47', 
        user_name = 'imadmin-bot', 
        email = '', 
        user_pw = '5127685c3da658593e6bac55e441c175', 
        realname = 'IM Administrator (do not delete)', 
        register_purpose = NULL, 
        status = 'A', 
        shell = '0', 
        unix_pw = '0', 
        unix_status = '0', 
        unix_uid = 0, 
        unix_box = '0', 
        ldap_id = NULL, 
        add_date = 940000000, 
        confirm_hash = NULL, 
        authorized_keys = NULL, 
        people_view_skills = 0, 
        timezone = 'GMT', 
        windows_pw = '', 
        language_id = 1, 
        last_pwd_update = '0', 
        last_access_date = '0';

# Make the 'imadmin' user part of the IM Admin Project so that he
# is also an openfire admin.

INSERT INTO user_group SET  user_id=47, group_id=47, admin_flags='A';



