default['tuleap']['php_base']       = 'php'
default['tuleap']['install_dir']    = '/usr/share/codendi'
default['tuleap']['yum_repo']       = 'stable'
default['tuleap']['packaging_user'] = 'tuleap-dev'
default['tuleap']['manifest_dir']   = '/mnt/tuleap/manifest'
default['tuleap']['source_dir']     = '/mnt/tuleap/tuleap'
default['tuleap']['org_name']       = 'Tuleap'

default['tuleap']['createrepos'] = %w(
  /home/vagrant/repos/centos/5/i386
  /home/vagrant/repos/centos/5/i386-php53
  /home/vagrant/repos/centos/5/SRPMS
  /home/vagrant/repos/centos/5/x86_64
  /home/vagrant/repos/centos/5/x86_64-php53
)
