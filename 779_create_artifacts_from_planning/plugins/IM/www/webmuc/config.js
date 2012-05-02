/*
 * MUCkl - A Web Based Groupchat Application
 * Copyright (C) 2004 Stefan Strigler <steve@zeank.in-berlin.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

/* ***
 * This is the main configuration file for the chat client itself.
 * You have to edit this before you can start using MUCkl on your website!
 * ***
 */

/* BACKENDTYPE - the type of backend to be used
 * 
 * Either 'polling' for HTTP Polling
 * Or     'binding' for HTTP Binding
 */
var BACKENDTYPE = 'binding';

/* HTTPBASE - base URI to contact HTTP Polling service
 * 
 * This must be local to your web server which serves MUCkl. If
 * HTTP Polling service is not local to your web server you have to
 * define a rewrite rule which matches this address and redirects to
 * the real HTTP Polling URI.
 * 
 * [refers to step 2 of installation instructions]
 */

//var HTTPBASE = "/tomcat/JHB/";
var HTTPBASE = "/http-bind/";

/* Login Data - the user to login
 * 
 * [refers to step 3 of installation instructions]
 */

//var XMPPDOMAIN = "codendi4.xrce.xerox.com"; // domain name of jabber service to be used

var AUTHTYPE = 'nonsasl';
//var AUTHHOST = "anon.localhost"; // hostname of sasl anonymous service 

//var MUCKLJID = "marcus"; // username
//var MUCKLPASS = "marcus"; // password

/* ROOMS
 *
 * Which chat room to join
 * 
 */


var ROOMS =
[
        {
                name:'',
                description:'',
                server:''
        }
];




/* CONFERENCENOHIST
 * whether to not show room history upon joining 
 */
var CONFERENCENOHIST = false;

/* DEFAULT_LOCK_MINS
 * time a user is being locked out if not otherwise indicated by kick reason
 */
var DEFAULT_LOCK_MINS = 1;

/* MAX_LOCK_MINS
 * maximum allowed number of minutes a user may be locked out by kick reason
 * on ban this is used as default value
 */
var MAX_LOCK_MINS = 60;

/* ***
 * some internally used vars - don't change except you really know
 * what you are doing
 * ***
 */

var timerval = 5000; // poll frequency in msec

var stylesheet = "muckl.css";
var THEMESDIR = "themes";

/* debugging options */
var DEBUG = false; // turn debugging on
var DEBUG_LVL = 2; // debug-level 0..4 (4 = very noisy)

/* ** Don't touch ** */
var VERSION = "0.4.3";
