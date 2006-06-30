


Various Notes concerning CodeX 2.8 to 3.0 upgrade.


Done in 2.8 support branch:
- copy new backup_job in /home/tools
- add sys_default_trove_cat in local.inc
- redirect commit-email.pl to /dev/null


TODO in migration_30
- when moving httpd to httpd_28, don t forget to move the '.subversion' directory back
- Convert BDB to FSFS?


RHEL4 Testing:

/usr/sbin/groupadd -g "104" sourceforge
/usr/sbin/groupadd -g "96" ftpadmin
/usr/sbin/useradd  -c 'Owner of CodeX directories' -M -d '/home/httpd' -p "$1$h67e4niB$xUTI.9DkGdpV.B65r1NVl/" -u 104 -g 104 -s '/bin/bash' -G ftpadmin sourceforge
