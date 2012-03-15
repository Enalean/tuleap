%define real_name VuFind

Summary:	A library resource discovery portal
Name:		vufind
Version:	0.8
Release:	1
Copyright:	GPL
Group:		Applications/Internet
URL:		http://vufind.org/
Source:		%{Name}-%{Version}.tgz
Packager:	Andrew Nagy <andrew.nagy@villanova.edu>
BuildRoot:	%{_tmppath}/%{Name}
BuildArch:	noarch
Requires:	httpd >= 2.0
Requires:	mysql-client >= 5.0
Requires:	mysql-server >= 5.0
Requires:       php >= 5.2
Requires: 	php-mysql
Requires: 	php-pear
Requires: 	php-xsl
Requires: 	php-pspell
Requires: 	php-pdo

%description
VuFind is a library resource portal designed and developed for libraries by
libraries. The goal of VuFind is to enable your users to search and browse
through all of your library's resources by replacing the traditional OPAC.

%prep
%setup -q
%build
%install

mv $RPM_BUILD_ROOT /usr/local

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root)
%doc README ChangeLog
%config web/config
