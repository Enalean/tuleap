#
# Cookbook Name:: tuleap
# Recipe:: packaging_tools
#
# Copyright (c) Enalean, 2012. All Rights Reserved.
#
# This file is a part of Tuleap.
#
# Tuleap is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# Tuleap is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
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
