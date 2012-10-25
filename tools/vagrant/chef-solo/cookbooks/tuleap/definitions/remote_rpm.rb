define :remote_rpm do
  tmp_path = "/tmp/#{File.basename(params[:source])}"
  
  remote_file tmp_path do
    source params[:source]
    not_if "rpm -q #{params[:name]}"
  end

  rpm_package params[:name] do
    source tmp_path
  end
end
