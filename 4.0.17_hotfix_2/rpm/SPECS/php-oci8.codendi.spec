Name:           php-oci8
Version:        4.3.9
Release:        3%{?dist}.codendi
Summary:        PHP Oracle support
Group:          Development/Tools
License:        GPL
Source:         php-oci8-4.3.9.tar.gz
BuildRoot:      %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)
Requires:	oracle-instantclient-basic >= 10.2.0.3
#Trick because Oracple instantclient does not properly registers its RPM signatures.
Provides: libclntsh.so.10.1

%description
This is a Codendi package for Oracle support in PHP.
This RPM requires oracle-instantclient-basic-10.2.0.3-1 RPM.
It is a patched version of:
ftp://fr.rpmfind.net/linux/freshrpms/redhat/testing/EL4/oracle/i386/php-oci8-4.3.9-2.2.el4.i386.rpm
We modified the hard-coded path from version 10.2.0.2 to 10.2.0.3.


%prep
%setup -q
#%setup -q -n installers


%build


%install
[ "%{buildroot}" != '/' ] && rm -rf %{buildroot}
# all the stuff from these tarballs is ready to install as-is ...
install -d %{buildroot}%{_libdir}/php4
install -d %{buildroot}%{_sysconfdir}/php.d
cp oci8.so %{buildroot}%{_libdir}/php4
cp oci8.ini %{buildroot}%{_sysconfdir}/php.d
#tar -x -C %{buildroot}%{_libdir}/%{instdir} -f php-oci8-4.3.9.tar.gz


%clean
[ "%{buildroot}" != '/' ] && rm -rf %{buildroot}


%files
%defattr(-,root,root)
%doc  CREDITS
%{_sysconfdir}/php.d/oci8.ini
%{_libdir}/php4/oci8.so
#%{_bindir}/acroread
#%{_libdir}/%{instdir}
#%{_libdir}/%{plugdst}/%{plugin}
#%{_bindir}/cvsgraph
#%{_mandir}/man[15]/cvsgraph*.[15]*


%changelog
* Mon Feb 26 2007 Nicolas Guerin <nicolas.guerin@xrce.xerox.com> - 4.3.9-3.codendi
- Initial version

