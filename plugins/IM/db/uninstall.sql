##
## Sql Uninstall Script for IM plugin
##

# Remove IM Admin  user and group.
DELETE FROM groups WHERE group_id='99';
DELETE FROM user   WHERE user_id ='99';
DELETE FROM service WHERE group_id='99';
DELETE FROM user_group WHERE user_id='99';

