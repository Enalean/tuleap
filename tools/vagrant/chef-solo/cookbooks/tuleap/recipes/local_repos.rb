#
# Cookbook Name:: tuleap
# Recipe:: local_repos
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

## Create all the local Tuleap repos
node['tuleap']['createrepos'].each do |path|
  createrepo path do
    user node['tuleap']['packaging_user']
  end
end

## Add YUM configuration file
php53 = (node['tuleap']['php_base'] == 'php53' ? '-php53' : '')

tuleap_yum_repository 'local' do
  description 'Local Repository'
  url         "file:///home/vagrant/repos/centos/5/$basearch#{php53}"
end

yum_clean
