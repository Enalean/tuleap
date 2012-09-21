#!/bin/bash
PERL='/usr/bin/perl'

[ `id -u` -ne 0 ] && die "Must be root to execute this script"

substitute() {
  # $1: filename, $2: string to match, $3: replacement string
  # Allow '/' is $3, so we need to double-escape the string
  replacement=`echo $3 | sed "s|/|\\\\\/|g"`
  $PERL -pi -e "s/$2/$replacement/g" $1
}

old_ip_address=`grep NameVirtualHost /etc/httpd/conf/httpd.conf | grep -v '#' | cut -d " " -f 2 | cut -d ":" -f 1`
echo "Current ip_address = $old_ip_address"
read -p "New IP address: " new_ip_address


substitute '/etc/httpd/conf/httpd.conf' "$old_ip_address" "$new_ip_address"
if [ -e '/var/named/chroot/var/named/codendi.zone' ] ; then
  substitute '/var/named/chroot/var/named/codendi.zone' "$old_ip_address" "$new_ip_address"
fi


