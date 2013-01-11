name 'tuleap'
description 'A Tuleap instance'
run_list 'recipe[tuleap::base]',
         'recipe[tuleap::rpm_deployment]'
default_attributes 'tuleap' => {'yum_repo' => 'stable'}
