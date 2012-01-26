Before do
  puts "reinit database"
  codendi_dir = "/usr/share/codendi"
  fixture_file = "codendi_tools/plugins/tests/functional/fixture.sql"
  system "ssh root@#{$tuleap_host} -C \"mysql -B -pwelcome0 -ucodendiadm codendi < #{codendi_dir}/#{fixture_file}\"" 
  #to export the state of the base and replace the current fixture file
  #ssh root@piton -C "mysqldump -pwelcome0 -ucodendiadm codendi > /usr/share/codendi/codendi_tools/plugins/tests/functional/fixture.sql"
end

Before do
  # Start on the home page
  visit('/')
end
After do |scenario|
  if scenario.failed?
    screenshot_path = Capybara::Screenshot::Cucumber.screen_shot_and_save_page[:image]
    # Trying to embed the screenshot into our output."
  end
end
# open up rb_dsl and add method BeforeFeature
#def BeforeFeature(name, block) 
#  @before_feature_hooks ||= Hash.new { [] } 
#  @before_feature_hooks[name] << block 
#end 
#Before  do
#   if scenario.feature.name != @current_feature_name 
#     @current_feature_name = scenario.feature.name 
#     @before_feature_hooks[@current_feature_name].each { |hook| hook.call } 
#   end 
#end 

