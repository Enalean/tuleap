#
# Cookbook Name:: tuleap
# Recipe:: rpm
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

## Remove existing manual install
link node['tuleap']['install_dir'] do
  action :delete
  only_if "test -L #{node['tuleap']['install_dir']}"
end

## Install JSON PECL extension for PHP (needed by Tuleap):
##   - The PHP 5.1 version is php-pecl-json
##   - The PHP 5.3 version is included in php53-common
package('php-pecl-json') { only_if { node['tuleap']['php_base'] == 'php' } }

## Install and set up Tuleap
package 'tuleap-all'

## Reinitialize the mysql root password (it may have been set up during a previous install)
script "UPDATE mysql.user SET password=PASSWORD('') WHERE user='root'" do
  user        'root'
  interpreter 'bash'
  environment 'HOME' => '/root'
  code        <<-SHELL
                mysql_root_passwd=`test -f /root/.tuleap_passwd && \
                                   grep -i "Mysql root" /root/.tuleap_passwd | \
                                   cut -d: -f2 | \
                                   tr -d ' '`
                
                if [ ! -z "$mysql_root_passwd" ]; then
                  mysql --password=$mysql_root_passwd \
                    -e "UPDATE mysql.user SET password=PASSWORD('') WHERE user='root'; FLUSH PRIVILEGES;"
                fi
              SHELL
end

## Set up Tuleap
script '/usr/share/tuleap-install/setup.sh' do
  user        'root'
  interpreter 'bash'
  environment 'HOME' => '/root'
  code        <<-SHELL
                yes | /usr/share/tuleap-install/setup.sh \
                      --sys-default-domain   #{node['tuleap']['fqdn']} \
                      --sys-fullname         #{node['tuleap']['fqdn']} \
                      --disable-subdomains   \
                      --sys-ip-address       #{node['tuleap']['ip_address']} \
                      --sys-org-name         "#{node['tuleap']['org_name']}" \
                      --sys-long-org-name    "#{node['tuleap']['org_name']}" \
                      --auto-passwd \
                  2>&1 | tee /var/log/tuleap-install.log
              SHELL
end
