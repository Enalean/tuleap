#
# Cookbook Name:: tuleap
# Recipe:: build
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

# execute 'make clone' do
#   user node['tuleap']['packaging_user']
#   cwd  node['tuleap']['manifest_dir']
# end


packager      = node['tuleap']['packaging_user']
arch          = `uname -m`.strip
php_base      = node['tuleap']['php_base']
php_suffix    = (php_base == 'php53' ? '-php53' : '')
platform      = "centos-5-#{arch}#{php_suffix}"
packager_home = (packager == 'root' ? '/root' : "/home/#{packager}")
repo_path     = "#{packager_home}/repos/centos/5/#{arch}#{php_suffix}"
build_log     = "#{packager_home}/logs/build.log"

## XXX:
##   Both the `script` and `execute` resources don't instanciate a login shell
##   to run their commands. This means that, even if `user` is set, both the
##   `HOME` environment variable and the user groups will be inherited from
##   `root`.
##   So we need to set `HOME` in the `environment` attribute, and to add `root`
##   to the `mock` group, even if the actual user will not be `root`.
group 'mock' do
  action :manage
  members ['root']
  append true
end

script 'build tuleap dependencies' do
  user        packager
  cwd         node['tuleap']['manifest_dir']
  interpreter 'bash'
  environment 'HOME' => packager_home
  code        <<-SHELL
                echo > #{build_log}
                make PLATEFORMS="#{platform}" BUILD_DIR=#{packager_home} 2>&1 | tee #{build_log}
              SHELL
end

script 'build tuleap' do
  user        packager
  cwd         "#{node['tuleap']['source_dir']}/tools/rpm"
  interpreter 'bash'
  environment 'HOME' => packager_home
  code        <<-SHELL
                make PHP_BASE=#{php_base} 2>&1 | tee #{build_log}
                mv #{packager_home}/rpmbuild/RPMS/noarch/* #{repo_path}
                createrepo #{repo_path}
              SHELL
end

yum_clean
