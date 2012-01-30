require 'rubygems'
require 'capybara/cucumber'
require 'capybara-screenshot/cucumber'
require 'capybara/dsl'
include RSpec::Matchers
include Capybara::DSL

$tuleap_host = ENV['TULEAP_HOST'] 
if ! $tuleap_host
  raise ("ERROR : you must specify the hostname of the tuleap server you are testing, ex export TULEAP_HOST=myhost.mydomain.com")
end
Capybara.app_host = "https://#{$tuleap_host}/"
Capybara.run_server = false
selenium_server = "http://lxc-selenium-server:4444/wd/hub"


# Register firefox
Capybara.register_driver :firefox do |app|
  Capybara::Selenium::Driver.new(app, {:browser => :remote, :url => selenium_server})
end

# Register IE
Capybara.register_driver :ie7 do |app|
  Capybara::Selenium::Driver.new(app, {:browser => :remote, :url => selenium_server, :desired_capabilities => :internet_explorer})
end

# Register webkit
begin
  require 'capybara/webkit'
  Capybara.register_driver :webkit_ignore_ssl do |app|
    browser = Capybara::Driver::Webkit::Browser.new(:ignore_ssl_errors => true)
    Capybara::Driver::Webkit.new(app, :browser => browser)
  end
rescue LoadError
end

#Capybara.default_driver = :firefox
Capybara.default_driver = :webkit_ignore_ssl


