<?php

/* *******************************************
// Copyright 2010, Anthony Hand
//
// LICENSE INFORMATION
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//        http://www.apache.org/licenses/LICENSE-2.0
// Unless required by applicable law or agreed to in writing,
// software distributed under the License is distributed on an
// "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND,
// either express or implied. See the License for the specific
// language governing permissions and limitations under the License.
//
//
// ABOUT THIS PROJECT
//   Project Owner: Anthony Hand
//   Email: anthony.hand@gmail.com
//   Web Site: http://www.mobileesp.com
//   Source Files: http://code.google.com/p/mobileesp/
//  
//   Versions of this code are available for:
//      PHP, JavaScript, Java, and ASP.NET (C#)
//
// *******************************************
*/



//**************************
// The uagent_info class encapsulates information about
//   a browser's connection to your web site.
//   You can use it to find out whether the browser asking for
//   your site's content is probably running on a mobile device.
//   The methods were written so you can be as granular as you want.
//   For example, enquiring whether it's as specific as an iPod Touch or
//   as general as a smartphone class device.
//   The object's methods return 1 for true, or 0 for false.
class uagent_info
{
   var $useragent = "";
   var $httpaccept = "";

   //standardized values for true and false.
   var $true = 1;
   var $false = 0;

   //Optional: store values for quickly accessing same info multiple times.
   //  Call InitDeviceScan() to initialize these values.
   var $isIphone = 0; //Stores whether the device is an iPhone or iPod Touch.
   var $isTierIphone = 0; //Stores whether is the iPhone tier of devices.
   var $isTierRichCss = 0; //Stores whether the device can probably support Rich CSS, but JavaScript support is not assumed. (e.g., newer BlackBerry, Windows Mobile)
   var $isTierGenericMobile = 0; //Stores whether it is another mobile device, which cannot be assumed to support CSS or JS (eg, older BlackBerry, RAZR)

   //Initialize some initial smartphone string variables.
   var $engineWebKit = 'webkit';
   var $deviceAndroid = 'android';
   var $deviceIphone = 'iphone';
   var $deviceIpod = 'ipod';

   var $deviceNuvifone = 'nuvifone'; //Garmin Nuvifone

   var $deviceSymbian = 'symbian';
   var $deviceS60 = 'series60';
   var $deviceS70 = 'series70';
   var $deviceS80 = 'series80';
   var $deviceS90 = 'series90';
   
   var $deviceWinMob = 'windows ce';
   var $deviceWindows = 'windows';
   var $deviceIeMob = 'iemobile';
   var $enginePie = "wm5 pie"; //An old Windows Mobile
   
   var $deviceBB = 'blackberry';  
   var $vndRIM = 'vnd.rim'; //Detectable when BB devices emulate IE or Firefox
   var $deviceBBStorm = 'blackberry95';  //Storm 1 and 2
   var $deviceBBBold = 'blackberry97'; //Bold
   var $deviceBBTour = 'blackberry96'; //Tour
   var $deviceBBCurve = 'blackberry89'; //Curve2
   
   var $devicePalm = 'palm';
   var $deviceWebOS = 'webos'; //For Palm's new WebOS devices
   var $engineBlazer = 'blazer'; //Old Palm browser
   var $engineXiino = 'xiino'; //Another old Palm
   
   //Initialize variables for mobile-specific content.
   var $vndwap = 'vnd.wap';
   var $wml = 'wml';  
   
   //Initialize variables for other random devices and mobile browsers.
   var $deviceBrew = 'brew';
   var $deviceDanger = 'danger';
   var $deviceHiptop = 'hiptop';
   var $devicePlaystation = 'playstation';
   var $deviceNintendoDs = 'nitro';
   var $deviceNintendo = 'nintendo';
   var $deviceWii = 'wii';
   var $deviceXbox = 'xbox';
   var $deviceArchos = 'archos';
   
   var $engineOpera = 'opera'; //Popular browser
   var $engineNetfront = 'netfront'; //Common embedded OS browser
   var $engineUpBrowser = 'up.browser'; //common on some phones
   var $engineOpenWeb = 'openweb'; //Transcoding by OpenWave server
   var $deviceMidp = 'midp'; //a mobile Java technology
   var $uplink = 'up.link';
   var $engineTelecaQ = 'teleca q'; //a modern feature phone browser
   
   var $devicePda = 'pda'; //some devices report themselves as PDAs
   var $mini = 'mini';  //Some mobile browsers put 'mini' in their names.
   var $mobile = 'mobile'; //Some mobile browsers put 'mobile' in their user agent strings.
   var $mobi = 'mobi'; //Some mobile browsers put 'mobi' in their user agent strings.
   
   //Use Maemo, Tablet, and Linux to test for Nokia's Internet Tablets.
   var $maemo = 'maemo';
   var $maemoTablet = 'tablet';
   var $linux = 'linux';
   var $qtembedded = 'qt embedded'; //for Sony Mylo and others
   var $mylocom2 = 'com2'; //for Sony Mylo also
   
   //In some UserAgents, the only clue is the manufacturer.
   var $manuSonyEricsson = "sonyericsson";
   var $manuericsson = "ericsson";
   var $manuSamsung1 = "sec-sgh";
   var $manuSony = "sony";

   //In some UserAgents, the only clue is the operator.
   var $svcDocomo = "docomo";
   var $svcKddi = "kddi";
   var $svcVodafone = "vodafone";



   //**************************
   //The constructor. Initializes several default variables.
   function uagent_info()
   {
       $this->useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
       $this->httpaccept = strtolower($_SERVER['HTTP_ACCEPT']);
   }
   
   //**************************
   // Initialize Key Stored Values.
   function InitDeviceScan()
   {
        global $isIphone, $isTierIphone, $isTierRichCss, $isTierGenericMobile;
       
        $this->isIphone = $this->DetectIphoneOrIpod();
        $this->isTierIphone = $this->DetectTierIphone();
        $this->isTierRichCss = $this->DetectTierRichCss();
        $this->isTierGenericMobile = $this->DetectTierOtherPhones();
   }

   //**************************
   //Returns the contents of the User Agent value, in lower case.
   function Get_Uagent()
   {
       return $this->useragent;
   }

   //**************************
   //Returns the contents of the HTTP Accept value, in lower case.
   function Get_HttpAccept()
   {
       return $this->httpaccept;
   }

   //**************************
   // Detects if the current device is an iPhone.
   function DetectIphone()
   {
      if (stripos($this->useragent, $this->deviceIphone) > -1)
      {
         //The iPod touch says it's an iPhone! So let's disambiguate.
         if ($this->DetectIpod() == $this->true)
         {
            return $this->false;
         }
         else
            return $this->true;
      }
      else
         return $this->false;
   }

   //**************************
   // Detects if the current device is an iPod Touch.
   function DetectIpod()
   {
      if (stripos($this->useragent, $this->deviceIpod) > -1)
         return $this->true;
      else
         return $this->false;
   }

   //**************************
   // Detects if the current device is an iPhone or iPod Touch.
   function DetectIphoneOrIpod()
   {
       //We repeat the searches here because some iPods may report themselves as an iPhone, which would be okay.
       if (stripos($this->useragent, $this->deviceIphone) > -1 ||
           stripos($this->useragent, $this->deviceIpod) > -1)
         return $this->true;
      else
         return $this->false;
   }


   //**************************
   // Detects if the current device is an Android OS-based device.
   function DetectAndroid()
   {
      if (stripos($this->useragent, $this->deviceAndroid) > -1)
         return $this->true;
      else
         return $this->false;
   }

   //**************************
   // Detects if the current device is an Android OS-based device and
   //   the browser is based on WebKit.
   function DetectAndroidWebKit()
   {
      if ($this->DetectAndroid() == $this->true)
      {
         if ($this->DetectWebkit() == $this->true)
         {
            return $this->true;
         }
         else
            return $this->false;
      }
      else
         return $this->false;
   }

   //**************************
   // Detects if the current browser is based on WebKit.
   function DetectWebkit()
   {
      if (stripos($this->useragent, $this->engineWebKit) > -1)
         return $this->true;
      else
         return $this->false;
   }


   //**************************
   // Detects if the current browser is the Nokia S60 Open Source Browser.
   function DetectS60OssBrowser()
   {
      //First, test for WebKit, then make sure it's either Symbian or S60.
      if ($this->DetectWebkit() == $this->true)
      {
        if (stripos($this->useragent, $this->deviceSymbian) > -1 ||
            stripos($this->useragent, $this->deviceS60) > -1)
        {
           return $this->true;
        }
        else
           return $this->false;
      }
      else
         return $this->false;
   }
   
   //**************************
   // Detects if the current device is any Symbian OS-based device,
   //   including older S60, Series 70, Series 80, Series 90, and UIQ,
   //   or other browsers running on these devices.
   function DetectSymbianOS()
   {
       if (stripos($this->useragent, $this->deviceSymbian) > -1 ||
           stripos($this->useragent, $this->deviceS60) > -1 ||
           stripos($this->useragent, $this->deviceS70) > -1 ||
           stripos($this->useragent, $this->deviceS80) > -1 ||
           stripos($this->useragent, $this->deviceS90) > -1)
         return $this->true;
      else
         return $this->false;
   }

   //**************************
   // Detects if the current browser is a Windows Mobile device.
   function DetectWindowsMobile()
   {
      //Most devices use 'Windows CE', but some report 'iemobile'
      //  and some older ones report as 'PIE' for Pocket IE.
      if (stripos($this->useragent, $this->deviceWinMob) > -1 ||
          stripos($this->useragent, $this->deviceIeMob) > -1 ||
          stripos($this->useragent, $this->enginePie) > -1)
         return $this->true;
      if ($this->DetectWapWml() == $this->true &&
          stripos($this->useragent, $this->deviceWindows) > -1)
         return $this->true;
      else
         return $this->false;
   }

   //**************************
   // Detects if the current browser is a BlackBerry of some sort.
   function DetectBlackBerry()
   {
       if (stripos($this->useragent, $this->deviceBB) > -1)
         return $this->true;
       if (stripos($this->httpaccept, $this->vndRIM) > -1)
         return $this->true;
       else
         return $this->false;
   }

   //**************************
   // Detects if the current browser is a BlackBerry Touch
   //    device, such as the Storm.
   function DetectBlackBerryTouch()
   {
       if (stripos($this->useragent, $this->deviceBBStorm) > -1)
         return $this->true;
       else
         return $this->false;
   }
   
   //**************************
   // Detects if the current browser is a BlackBerry device AND
   //    has a more capable recent browser.
   //    Examples, Storm, Bold, Tour, Curve2
   function DetectBlackBerryHigh()
   {
      if ($this->DetectBlackBerry() == $this->true)
      {
          if ((DetectBlackBerryTouch() == $this->true) ||
            stripos($this->useragent, $this->deviceBBBold) > -1 ||
            stripos($this->useragent, $this->deviceBBTour) > -1 ||
            stripos($this->useragent, $this->deviceBBCurve) > -1)
          {
             return $this->true;
          }
          else
            return $this->false;
      }
      else
        return $this->false;
   }

   //**************************
   // Detects if the current browser is a BlackBerry device AND
   //    has an older, less capable browser.
   //    Examples: Pearl, 8800, Curve1.
   function DetectBlackBerryLow()
   {
      if ($this->DetectBlackBerry() == $this->true)
      {
          //Assume that if it's not in the High tier, then it's Low.
          if (DetectBlackBerryHigh() == $this->true)
             return $this->false;
          else
            return $this->true;
      }
      else
        return $this->false;
   }

   //**************************
   // Detects if the current browser is on a PalmOS device.
   function DetectPalmOS()
   {
      //Most devices nowadays report as 'Palm', but some older ones reported as Blazer or Xiino.
      if (stripos($this->useragent, $this->devicePalm) > -1 ||
          stripos($this->useragent, $this->engineBlazer) > -1 ||
          stripos($this->useragent, $this->engineXiino) > -1)
      {
         //Make sure it's not WebOS first
         if ($this->DetectPalmWebOS() == $this->true)
            return $this->false;
         else
            return $this->true;
      }
      else
         return $this->false;
   }


   //**************************
   // Detects if the current browser is on a Palm device
   //   running the new WebOS.
   function DetectPalmWebOS()
   {
      if (stripos($this->useragent, $this->deviceWebOS) > -1)
         return $this->true;
      else
         return $this->false;
   }

   //**************************
   // Detects if the current browser is a
   //   Garmin Nuvifone.
   function DetectGarminNuvifone()
   {
      if (stripos($this->useragent, $this->deviceNuvifone) > -1)
         return $this->true;
      else
         return $this->false;
   }


   //**************************
   // Check to see whether the device is any device
   //   in the 'smartphone' category.
   function DetectSmartphone()
   {
      if ($this->DetectIphoneOrIpod() == $this->true)
         return $this->true;
      if ($this->DetectS60OssBrowser() == $this->true)
         return $this->true;
      if ($this->DetectSymbianOS() == $this->true)
         return $this->true;
      if ($this->DetectAndroid() == $this->true)
         return $this->true;
      if ($this->DetectWindowsMobile() == $this->true)
         return $this->true;
      if ($this->DetectBlackBerry() == $this->true)
         return $this->true;
      if ($this->DetectPalmWebOS() == $this->true)
         return $this->true;
      if ($this->DetectPalmOS() == $this->true)
         return $this->true;
      if ($this->DetectGarminNuvifone() == $this->true)
         return $this->true;
      else
         return $this->false;
   }



   //**************************
   // Detects whether the device is a Brew-powered device.
   function DetectBrewDevice()
   {
       if (stripos($this->useragent, $this->deviceBrew) > -1)
         return $this->true;
      else
         return $this->false;
   }

   //**************************
   // Detects the Danger Hiptop device.
   function DetectDangerHiptop()
   {
      if (stripos($this->useragent, $this->deviceDanger) > -1 ||
          stripos($this->useragent, $this->deviceHiptop) > -1)
         return $this->true;
      else
         return $this->false;
   }

   //**************************
   // Detects if the current browser is Opera Mobile or Mini.
   function DetectOperaMobile()
   {
      if (stripos($this->useragent, $this->engineOpera) > -1)
      {
         if ((stripos($this->useragent, $this->mini) > -1) ||
          (stripos($this->useragent, $this->mobi) > -1))
            return $this->true;
         else
            return $this->false;
      }
      else
         return $this->false;
   }

   //**************************
   // Detects whether the device supports WAP or WML.
   function DetectWapWml()
   {
       if (stripos($this->httpaccept, $this->vndwap) > -1 ||
           stripos($this->httpaccept, $this->wml) > -1)
         return $this->true;
      else
         return $this->false;
   }
   
   //**************************
   // The quick way to detect for a mobile device.
   //   Will probably detect most recent/current mid-tier Feature Phones
   //   as well as smartphone-class devices.
   function DetectMobileQuick()
   {
      //Most mobile browsing is done on smartphones
      if ($this->DetectSmartphone() == $this->true)
         return $this->true;

      if ($this->DetectWapWml() == $this->true)
         return $this->true;
      if ($this->DetectBrewDevice() == $this->true)
         return $this->true;
      if ($this->DetectOperaMobile() == $this->true)
         return $this->true;
         
      if (stripos($this->useragent, $this->engineNetfront) > -1)
         return $this->true;
      if (stripos($this->useragent, $this->engineUpBrowser) > -1)
         return $this->true;
      if (stripos($this->useragent, $this->engineOpenWeb) > -1)
         return $this->true;
         
      if ($this->DetectDangerHiptop() == $this->true)
         return $this->true;

      if ($this->DetectMidpCapable() == $this->true)
         return $this->true;

      if ($this->DetectMaemoTablet() == $this->true)
         return $this->true;
      if ($this->DetectArchos() == $this->true)
         return $this->true;

       if (stripos($this->useragent, $this->devicePda) > -1)
         return $this->true;
       if (stripos($this->useragent, $this->mobile) > -1)
         return $this->true;

      else
         return $this->false;
   }
   
   //**************************
   // Detects if the current device is a Sony Playstation.
   function DetectSonyPlaystation()
   {
      if (stripos($this->useragent, $this->devicePlaystation) > -1)
         return $this->true;
      else
         return $this->false;
   }

   //**************************
   // Detects if the current device is a Nintendo game device.
   function DetectNintendo()
   {
      if (stripos($this->useragent, $this->deviceNintendo) > -1 ||
           stripos($this->useragent, $this->deviceWii) > -1 ||
           stripos($this->useragent, $this->deviceNintendoDs) > -1)
         return $this->true;
      else
         return $this->false;
   }

   //**************************
   // Detects if the current device is a Microsoft Xbox.
   function DetectXbox()
   {
      if (stripos($this->useragent, $this->deviceXbox) > -1)
         return $this->true;
      else
         return $this->false;
   }
   
   //**************************
   // Detects if the current device is an Internet-capable game console.
   function DetectGameConsole()
   {
      if ($this->DetectSonyPlaystation() == $this->true)
         return $this->true;
      else if ($this->DetectNintendo() == $this->true)
         return $this->true;
      else if ($this->DetectXbox() == $this->true)
         return $this->true;
      else
         return $this->false;
   }
   
   //**************************
   // Detects if the current device supports MIDP, a mobile Java technology.
   function DetectMidpCapable()
   {
       if (stripos($this->useragent, $this->deviceMidp) > -1 ||
           stripos($this->httpaccept, $this->deviceMidp) > -1)
         return $this->true;
      else
         return $this->false;
   }
   
   //**************************
   // Detects if the current device is on one of the Maemo-based Nokia Internet Tablets.
   function DetectMaemoTablet()
   {
      if (stripos($this->useragent, $this->maemo) > -1)
         return $this->true;
      //Must be Linux + Tablet, or else it could be something else.
      if (stripos($this->useragent, $this->maemoTablet) > -1 &&
          stripos($this->useragent, $this->linux) > -1)
         return $this->true;
      else
         return $this->false;
   }

   //**************************
   // Detects if the current device is an Archos media player/Internet tablet.
   function DetectArchos()
   {
      if (stripos($this->useragent, $this->deviceArchos) > -1)
         return $this->true;
      else
         return $this->false;
   }

   //**************************
   // Detects if the current browser is a Sony Mylo device.
   function DetectSonyMylo()
   {
      if (stripos($this->useragent, $this->manuSony) > -1)
      {
         if ((stripos($this->useragent, $this->qtembedded) > -1) ||
          (stripos($this->useragent, $this->mylocom2) > -1))
         {
            return $this->true;
         }
         else
            return $this->false;
      }
      else
         return $this->false;
   }

   //**************************
   // The longer and more thorough way to detect for a mobile device.
   //   Will probably detect most feature phones,
   //   smartphone-class devices, Internet Tablets,
   //   Internet-enabled game consoles, etc.
   //   This ought to catch a lot of the more obscure and older devices, also --
   //   but no promises on thoroughness!
   function DetectMobileLong()
   {
      if ($this->DetectMobileQuick() == $this->true)
         return $this->true;
      if ($this->DetectGameConsole() == $this->true)
         return $this->true;
      if ($this->DetectSonyMylo() == $this->true)
         return $this->true;


       //Detect older phones from certain manufacturers and operators.
       if (stripos($this->useragent, $this->uplink) > -1)
         return $this->true;
       if (stripos($this->useragent, $this->manuSonyEricsson) > -1)
         return $this->true;
       if (stripos($this->useragent, $this->manuericsson) > -1)
         return $this->true;

       if (stripos($this->useragent, $this->manuSamsung1) > -1)
         return $this->true;
       if (stripos($this->useragent, $this->svcDocomo) > -1)
         return $this->true;
       if (stripos($this->useragent, $this->svcKddi) > -1)
         return $this->true;
       if (stripos($this->useragent, $this->svcVodafone) > -1)
         return $this->true;

      else
         return $this->false;
   }



  //*****************************
  // For Mobile Web Site Design
  //*****************************


   //**************************
   // The quick way to detect for a tier of devices.
   //   This method detects for devices which can
   //   display iPhone-optimized web content.
   //   Includes iPhone, iPod Touch, Android, WebOS, etc.
   function DetectTierIphone()
   {
      if ($this->DetectIphoneOrIpod() == $this->true)
         return $this->true;
      if ($this->DetectAndroid() == $this->true)
         return $this->true;
      if ($this->DetectAndroidWebKit() == $this->true)
         return $this->true;
      if ($this->DetectPalmWebOS() == $this->true)
         return $this->true;
      if ($this->DetectGarminNuvifone() == $this->true)
         return $this->true;
      if ($this->DetectMaemoTablet() == $this->true)
         return $this->true;
      else
         return $this->false;
   }
   
   //**************************
   // The quick way to detect for a tier of devices.
   //   This method detects for devices which are likely to be capable
   //   of viewing CSS content optimized for the iPhone,
   //   but may not necessarily support JavaScript.
   //   Excludes all iPhone Tier devices.
   function DetectTierRichCss()
   {
      if ($this->DetectMobileQuick() == $this->true)
      {
        if ($this->DetectTierIphone() == $this->true)
           return $this->false;
           
        //The following devices are explicitly ok.
        if ($this->DetectWebkit() == $this->true) //Any WebKit
           return $this->true;
        if ($this->DetectS60OssBrowser() == $this->true)
           return $this->true;
           
        //Note: 'High' BlackBerry devices ONLY
        if ($this->DetectBlackBerryHigh() == $this->true)
           return $this->true;
       
        if ($this->DetectWindowsMobile() == $this->true)
           return $this->true;
        if (stripos($this->useragent, $this->engineTelecaQ) > -1)
           return $this->true;
           
        //default
        else
           return $this->false;
      }
      else
         return $this->false;
   }

   //**************************
   // The quick way to detect for a tier of devices.
   //   This method detects for all other types of phones,
   //   but excludes the iPhone and RichCSS Tier devices.
   function DetectTierOtherPhones()
   {
      if ($this->DetectMobileLong() == $this->true)
      {
        //Exclude devices in the other 2 categories
        if ($this->DetectTierIphone() == $this->true)
           return $this->false;
        if ($this->DetectTierRichCss() == $this->true)
           return $this->false;
       
        //Otherwise, it's a YES
        else
           return $this->true;
      }
      else
         return $this->false;
   }
     

}



?>