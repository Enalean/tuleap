LDAP Plugin
===========

This is the LDAP plugin for Tuleap. It provides:
- LDAP authentication for web access and SVN http(s).
- Querying for directory in "global search"
- Binding with LDAP groups. Ability to bind a Tuleap user group or project
  members with any LDAP group.
- Autocompletion of ldap logins where user name is required
- Automatic registeration of ldap users when they are referenced. You can add
  as a project member an LDAP user that is not already a "registered user".

See the Tuleap Installation Guide for more information.

Installation
------------
1. Install tuleap-plugin-ldap RPM
2. Go to the "Plugins Administration" web page and install the plugin.
3. Go in LDAP plugin properties and adjust to your environement.
   You should customize it in /etc/tuleap/plugins/ldap/etc/ldap.inc directly
   because it contains comments that are useful for beginners. There are two further templates
   located at /etc/tuleap/plugins/ldap/etc/OpenLDAP.inc and /etc/tuleap/plugins/ldap/etc/ActiveDirectory.inc
4. Enable ldap plugin in Plugin Administration
5. Switch "$sys_auth_type" variable in /etc/tuleap/conf/local.inc to "ldap".
6. If you want to authenticate SVN users with tokens or with modperl you also
need to grant more right to the MySQL user dbauthuser:
    GRANT SELECT ON plugin_ldap_user TO dbauthuser;
    FLUSH PRIVILEGES;

Customization
-------------

To adapt LDAP / Tuleap behaviour you should copy one of the customization files
(.txt) available in /plugins/ldap/site-content/en_US/ into the configuration
area, /etc/tuleap/plugins/ldap/site-content/en_US.
Once copied, you can adapt the file to fullfill your requirements.

There are 2 files you can customize:
- (synchronize_user.txt) How Tuleap will synchronize user account & LDAP
  directory informations. By default, real name (cn), email and ldap login (uid)
  will be synchronized, everytime the user log on the platform.
- (directory_redirect.txt) The link on LDAP login in order to redirect on your
   directory web interface (if any).
