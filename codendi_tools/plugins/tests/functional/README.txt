#
# Copyright (c) STMicroelectronics 2011. All rights reserved
#

This is a solution to run integration tests using Selenium Server

LICENSE
=======
This code is distributed under the GPL v2 Licence. See the file COPYING for details.

INSTALLATION
============
Before installing, you need to understand that this code deals with three different machines:
1. The machine from which you may launch tests having a phpunit. Let's call it "Launcher".
2. The machine on which web application to be tested is deployed. Let's call it "Server".
3. The machine from which tests will be launched, we use a web browser installed on it. Let's call it "Client".

To make it simple as a first installation please consider having 1- & 3- on the same machine: your desktop and 2- as are remote web server.
You will install pear, phpunit & selenium on your desktop and run the tests again your server.


On Launcher
-----------
Install PEAR 1.9.2 by typing

    pear install PEAR-1.9.2

Then install PHPUnit:

    pear channel-discover pear.phpunit.de
    pear channel-discover components.ez.no
    pear channel-discover pear.symfony-project.com
    pear install phpunit/PHPUnit
    
this would install Selenium extention with it.

On Client
---------
You need to download selenium-server-standalone-2.5.0.jar from http://seleniumhq.org/download

If you use firefox for tests & daily usage then create a Firefox profile for selenium (you may need to delete extentions.rdf for addons popup)

Then run Selenium by

    java -jar selenium-server-standalone-2.5.0.jar -singlewindow -firefoxProfileTemplate "/path/to/firefox/profile/" -trustAllSSLCertificates

Selenium Server must be always running on client machine as a service to make launching tests from Launcher possible.

To keep the same session for all tests add the option -browserSessionReuse

For headless Client (aka firefox running in da cloud). You will have to use a fake X server.

    yum install firefox xorg-x11-server-Xvfb cairo
    # you may have to install also xauth?

edit /etc/rc.d/rc.local

    Xvfb :99 -ac -noreset &

And reboot. Now you can run 
 
    DISPLAY=:99 java -jar selenium-server-standalone-2.5.0.jar -singlewindow -trustAllSSLCertificates
    


RUN TESTS
=========
You probably need to modify tests settings in include/set.php file.

Then you can run tests in command line by just typing:

    phpunit codendi_tools/plugins/tests/functional/

