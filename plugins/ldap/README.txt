LDAP Plugin
===========

This is the LDAP plugin for Codendi. It provides:
- LDAP authentication for web access (both browsing & soap) and SVN http(s).
- Querying for directory in "global search"
- Binding with LDAP groups. Ability to bind a codendi user group or project 
  members with any LDAP group.
- Autocompletion of ldap logins where user name is required
- Automatic registeration of ldap users when they are referenced. You can add
  as a project member an LDAP user that is not already a "registered user".

See the Codendi Installation Guide for more information.

Installation
------------

1. Install perl-LDAP and python-LDAP RPM (mandatory because backend scripts rely
   on it)
2. Copy /plugins/ldap/etc/ldap.inc in /etc/codendi/plugins/ldap/etc/ldap.inc and
   adjust variables.
3. Go to the "Plugins Administration" web page and finish the installation of
   the plugin.
4. Switch "$sys_auth_type" variable in /etc/codendi/conf/local.inc to "ldap".

If there are $sys_ldap* variables defined in /etc/codendi/conf/local.inc, remove
them (it comes from a previous installation of Codendi).

Customization
-------------

To adapt LDAP->Codendi behaviour you should copy one of the customization files
(.txt) available in /plugins/ldap/site-content/en_US/ into the configuration
area, /etc/codendi/plugins/ldap/site-content/en_US.
Once copied, you can adapt the file to fullfill your requirements.

There are 3 files you can customize:
- (synchronize_user.txt) How Codendi will synchronize user account & LDAP
  directory informations. By default, real name (cn), email and ldap login (uid)
  will be synchronized, everytime the user log on the platform.

- In public user profile (http://codendi.org/users/john_doe)
-- (user_home.txt) The info to display from the directory (phone number,
   location, etc)
-- (directory_redirect.txt) The link on LDAP login in order to redirect on your
   directory web interface (if any).
