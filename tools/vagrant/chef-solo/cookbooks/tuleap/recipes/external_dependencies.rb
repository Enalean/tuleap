#
# Cookbook Name:: tuleap
# Recipe:: external_dependencies
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

# FIXME: These dependencies should be mirrored in Tuleap yum repositories,
#        so that people don't need to use other RPM sources.

## Install some packages from EPEL
epel_package 'git'
epel_package 'perl-HTML-Template'

## Download and install openfire directly from website
remote_rpm 'openfire-3.6.4-1' do
  source 'http://download.igniterealtime.org/openfire/openfire-3.6.4-1.i386.rpm'
end
