#
# Cookbook Name:: tuleap
# Recipe:: yum
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

## Set up EPEL repo (disabled by default)
include_recipe 'yum::epel'
disable_yum_repository 'epel'
disable_yum_repository 'epel-testing'

## Set up Tuleap stable repo
tuleap_yum_repository 'stable' do
  description 'Official Releases'
  url         'https://tuleap.net/pub/tuleap/yum/4.0/$basearch/'
end

## Set up Tuleap development repo
tuleap_yum_repository 'dev' do
  description 'Main Development Branch'
  url         'ftp://ci.tuleap.net/yum/tuleap/dev/$basearch'
end
