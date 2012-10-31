define :disable_yum_repository do
  execute "sed -i 's/enabled=1/enabled=0/g' /etc/yum.repos.d/#{params[:name]}.repo" do
    command "sed -i 's/enabled=1/enabled=0/g' /etc/yum.repos.d/#{params[:name]}.repo"
    only_if do File.exists?("/etc/yum.repos.d/#{params[:name]}.repo") end
  end
end
