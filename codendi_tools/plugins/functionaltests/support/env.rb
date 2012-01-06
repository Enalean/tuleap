require 'rubygems'
require 'capybara/cucumber'
require 'capybara/dsl'
include RSpec::Matchers
include Capybara::DSL

Capybara.app_host = 'https://tuleap-host/'
Capybara.run_server = false

# Register firefox
Capybara.register_driver :firefox do |app|
  Capybara::Selenium::Driver.new(app, {:browser => :remote, :url => "http://lxc-selenium-server:4444/wd/hub"})
end

# Register IE
Capybara.register_driver :ie7 do |app|
  Capybara::Selenium::Driver.new(app, {:browser => :remote, :url => "http://lxc-selenium-serve:4444/wd/hub", :desired_capabilities => :internet_explorer})
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
