#
# Cookbook Name:: tuleap
# Recipe:: build
#
# Copyright 2012, Enalean
#
# All rights reserved - Do Not Redistribute
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
