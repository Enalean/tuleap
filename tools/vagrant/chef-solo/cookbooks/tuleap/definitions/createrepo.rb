define :createrepo, :user => 'root' do
  repo_path     = params[:name]
  repodata_path = "#{repo_path}/repodata"
  repo_owner    = params[:user]
  
  directory repo_path do
    recursive true
    user      repo_owner
    not_if    "test -d #{repo_path}"
  end
  
  script "createrepo #{repo_path}" do
    interpreter 'bash'
    user        repo_owner
    cwd         repo_path
    not_if      "test -d #{repodata_path}"
    code        <<-SH
                  createrepo --update .
                  chmod -R o+r .
                  path="#{repodata_path}"
                  while [ "$path" != "/" ]; do
                    chmod o+x $path
                    path=`dirname $path`
                  done
                SH
  end
end
