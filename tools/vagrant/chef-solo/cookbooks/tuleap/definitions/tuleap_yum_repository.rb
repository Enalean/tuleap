define :tuleap_yum_repository do
  yum_repository "tuleap-#{params[:name]}" do
    action :remove
  end
  
  yum_repository "tuleap-#{params[:name]}" do
    description "Tuleap - #{params[:description]}"
    url         params[:url]
    enabled     (node['tuleap']['yum_repo'] == params[:name] ? 1 : 0)
    make_cache  false
    action      :add
  end
end
