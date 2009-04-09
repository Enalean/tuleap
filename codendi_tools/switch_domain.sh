#!/bin/bash
PERL='/usr/bin/perl'

[ `id -u` -ne 0 ] && die "Must be root to execute this script"

substitute() {
  # $1: filename, $2: string to match, $3: replacement string
  # Allow '/' is $3, so we need to double-escape the string
  if [ -e $1 ] ; then
    replacement=`echo $3 | sed "s|/|\\\\\/|g"`
    $PERL -pi -e "s/$2/$replacement/g" $1
  fi
}

old_domain=`grep ServerName /etc/httpd/conf/httpd.conf | grep -v '#' | head -1 | cut -d " " -f 2 ;`
echo "Current domain = $old_domain"
read -p "New domain: " new_domain


substitute '/etc/codendi/conf/local.inc' "$old_domain" "$new_domain" 
substitute '/etc/codendi/conf.d/codendi_aliases.conf' "$old_domain" "$new_domain" 
substitute '/etc/httpd/conf/httpd.conf' "$old_domain" "$new_domain"
if [ -e '/etc/codendi/documentation/user_guide/xml/ParametersLocal.dtd' ] ; then
    substitute '/etc/codendi/documentation/user_guide/xml/ParametersLocal.dtd' "$old_domain" "$new_domain" 
fi
if [ -e '/etc/codendi/documentation/cli/xml/ParametersLocal.dtd' ] ; then
    substitute '/etc/codendi/documentation/cli/xml/ParametersLocal.dtd' "$old_domain" "$new_domain" 
fi
if [ -e '/var/named/chroot/var/named/codendi_full.zone' ] ; then
    substitute '/var/named/chroot/var/named/codendi_full.zone' "$old_domain" "$new_domain" 
fi
if [ -e '/etc/mail/local-host-names' ] ; then
    substitute '/etc/mail/local-host-names' "$old_domain" "$new_domain" 
fi

# TODO
# Mailman? /usr/lib/mailman/Mailman/mm_cfg.py + existing mailing lists...
# DB: user email, group http_domain, homepage service
# OpenFire: must re-install jabbex
