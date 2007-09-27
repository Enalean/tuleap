
Summary: PHP-based Wiki webapplication
Name: phpwiki
Version: 1.3.11
Release: 1


#############################################
# User options here
#############################################

#These are setup mostly for my local config. 
#Edit to taste, add salt, and boil for 3 minutes.

%define WIKI_NAME NU-Wiki
%define ADMIN_USER	<PHPWiki admin account name here>
%define ADMIN_PASSWD	<encrypted admin account PW here, see passencrypt.php>

%define DB_NAME		<database name>
%define DB_USER		<database user account>
%define DB_PASSWD	<database account password>

%define HTTPD_UID	apache

%define ACCESS_LOG	/var/log/httpd/phpwiki_access.log
%define DATABASE_TYPE	SQL
%define DATABASE_DSN	mysql://%{admin_user}:%{admin_passwd}
%define DEBUG		0
%define USER_AUTH_ORDER	"PersonalPage"
%define LDAP_AUTH_USER	""
%define LDAP_AUTH_PASSWORD	""
%define LDAP_SEARCH_FIELD	""
%define IMAP_AUTH_HOST	""
%define POP3_AUTH_HOST	""
%define AUTH_USER_FILE	""
%define AUTH_SESS_USER	""
%define AUTH_SESS_LEVEL	""
%define AUTH_GROUP_FILE	""



Group: Applications/Internet
License: GPL
URL: http://sourceforge.net/projects/phpwiki/

Packager: Jesse Becker <jbecker@northwestern.edu>
Vendor: Northwestern University

Source: http://easynews.dl.sourceforge.net/sourceforge/phpwiki/%{name}-%{version}.tar.gz
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root


#Relocation!
Prefix: /var/www

Requires: php php-mysql

# For some systems (like older RH)
Requires:  apache
#For newer systems
#Requires: httpd, php-pear

Autoreq: 0

%define dest %{buildroot}/%{prefix}/%{name}

%description
PhpWiki is a WikiWikiWeb clone in PHP. A WikiWikiWeb is a site where
anyone can edit the pages through an HTML form. Multiple storage
backends, dynamic hyperlinking, themeable, scriptable by plugins, full
authentication, ACL's.

%prep
%setup 

%install
%{__rm} -rf %{buildroot}
%{__mkdir} -p %{dest}
%{__cp} -r config lib locale pgsrc themes schemas uploads %{dest}
%{__cp} favicon.ico *.php *.wsdl *.wdsl wiki %{dest}

cd %{dest}/config
perl -p	\
	-e 's,^(WIKI_NAME)\s*=.*,$1 = %{WIKI_NAME},;'	\
	-e 's,^[;\s]*(ADMIN_USER)\s*=.*,$1 = %{ADMIN_USER},;'	\
	-e 's,^[;\s]*(ADMIN_PASSWD)\s*=.*,$1 = %{ADMIN_PASSWD},;'	\
	-e 's,^[;\s]*(ACCESS_LOG)\s*=.*,$1 = %{ACCESS_LOG},;'	\
	-e 's,^[;\s]*(DATABASE_TYPE)\s*=.*,$1 = %{DATABASE_TYPE},;'	\
	-e 's,^[;\s]*(DATABASE_DSN)\s*=.*,$1 = mysql://%{DB_USER}:%{DB_PASSWD}\@localhost/%{DB_NAME},;'	\
	-e 's,^[;\s]*(DEBUG)\s*=.*,$1 = %{DEBUG},;'	\
	-e 's,^[;\s]*(USER_AUTH_ORDER)\s*=.*,$1 = %{USER_AUTH_ORDER},;'	\
	-e 's,^[;\s]*(USER_AUTH_ORDER)\s*=.*,$1 = %{USER_AUTH_ORDER},;'	\
	-e 's,^[;\s]*(LDAP_AUTH_USER)\s*=.*,$1 = %{LDAP_AUTH_USER},;'	\
	-e 's,^[;\s]*(LDAP_AUTH_PASSWORD)\s*=.*,$1 = %{LDAP_AUTH_PASSWORD},;'	\
	-e 's,^[;\s]*(LDAP_SEARCH_FIELD)\s*=.*,$1 = %{LDAP_SEARCH_FIELD},;'	\
	-e 's,^[;\s]*(IMAP_AUTH_HOST)\s*=.*,$1 = %{IMAP_AUTH_HOST},;'	\
	-e 's,^[;\s]*(POP3_AUTH_HOST)\s*=.*,$1 = %{POP3_AUTH_HOST},;'	\
	-e 's,^[;\s]*(AUTH_USER_FILE)\s*=.*,$1 = %{AUTH_USER_FILE},;'	\
	-e 's,^[;\s]*(AUTH_SESS_USER)\s*=.*,$1 = %{AUTH_SESS_USER},;'	\
	-e 's,^[;\s]*(AUTH_SESS_LEVEL)\s*=.*,$1 = %{AUTH_SESS_LEVEL},;'	\
	-e 's,^[;\s]*(AUTH_GROUP_FILE)\s*=.*,$1 = %{AUTH_GROUP_FILE},;'	\
	config-dist.ini > config.ini



%clean
%{__rm} -rf %{buildroot}

%post
touch %{ACCESS_LOG}
if [ -f %{ACCESS_LOG} ]; then
	chown %{HTTPD_UID} %{ACCESS_LOG}
	chmod 644 %{ACCESS_LOG}
fi

cd %{prefix}/%{name}
mysqladmin create %{DB_NAME}

echo 'GRANT select, insert, update, delete, lock tables 
ON %{DB_NAME}.* 
TO %{DB_USER}@localhost 
IDENTIFIED BY "%{DB_PASSWD}"' | mysql

mysqladmin reload

cat schemas/mysql.sql | mysql %{DB_NAME} 


%files
%defattr(-, root, root, 0755)
%doc README UPGRADING LICENSE INSTALL doc Makefile tests

%{prefix}/%{name}/*.php
%{prefix}/%{name}/*.wsdl
%{prefix}/%{name}/wiki

%{prefix}/%{name}/lib
%{prefix}/%{name}/locale
%{prefix}/%{name}/pgsrc
%{prefix}/%{name}/themes
%{prefix}/%{name}/schemas
%{prefix}/%{name}/config/config-default.ini
%dir %{prefix}/%{name}/uploads

%config %{prefix}/%{name}/uploads/.htaccess
%config %{prefix}/%{name}/config/config.ini



%changelog
* Tue May 19 2005 Jesse Becker <jbecker@northwestern.edu>
- Initial build
