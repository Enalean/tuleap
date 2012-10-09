#
# Cookbook Name:: tuleap
# Recipe:: packaging_tools
#
# Copyright 2012, Enalean
#
# All rights reserved - Do Not Redistribute
#

php_base = node['tuleap']['php_base']

package 'rpm-build'
package 'mock'
package 'make'
package 'createrepo'

## XXX: The following dependencies were needed when using rpmbuild instead of
##      mock. Since we went back to rpmbuild for building Tuleap, some of them
##      may be required again. In which case one would just have to uncomment
##      the corresponding package to fix the build.
# package 'byacc'
# package 'enscript'
# package 'flex'
# package 'freetype-devel'
# package 'gcc-c++'
# package 'gd-devel'
# package 'httpd-devel'
# package 'java-1.6.0-openjdk'
# package 'krb5-devel'
# package 'libjpeg-devel'
# package 'libpng-devel'
# package 'libtool'
# package 'mysql-devel'
# package 'MySQL-python'
# package 'pam-devel'
# package 'pcre-devel'
package "#{php_base}-devel"
# package "#{php_base}-gd"
# package "#{php_base}-mbstring"
# package "#{php_base}-mysql"
# package "#{php_base}-process"
# package "#{php_base}-soap"
# package "#{php_base}-xml"
# package 'python-devel'
# package 'rcs'
package 'libxslt'
package 'zip'

group 'mock' do
  action :manage
  members [node['tuleap']['packaging_user']]
  append true
end
