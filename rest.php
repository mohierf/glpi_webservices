<?php
/**
 * @version $Id: rest.php 395 2014-11-16 18:39:27Z yllen $
 -------------------------------------------------------------------------
LICENSE

 This file is part of Webservices plugin for GLPI.

 Webservices is free software: you can redistribute it and/or modify
 it under the terms of the GNU Affero General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 Webservices is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU Affero General Public License for more details.

 You should have received a copy of the GNU Affero General Public License
 along with Webservices. If not, see <http://www.gnu.org/licenses/>.

 @package   Webservices
 @author    Nelly Mahu-Lasson
 @copyright Copyright (c) 2009-2014 Webservices plugin team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://forge.indepnet.net/projects/webservices
 @link      http://www.glpi-project.org/
 @since     2009
 --------------------------------------------------------------------------
 */

if (!function_exists("json_encode")) {
   header("HTTP/1.0 500 Extension json not loaded");
   die("Extension json not loaded");
}

define('DO_NOT_CHECK_HTTP_REFERER', 1);
if (! defined('GLPI_USE_CSRF_CHECK')) {
	define('GLPI_USE_CSRF_CHECK', 0);
}
define('GLPI_ROOT', '../..');

// define session_id before any other thing
if (isset($_GET['session']) || isset($_POST['session'])) {
   include_once ("inc/methodcommon.class.php");
   include_once ("inc/methodsession.class.php");
   $session = new PluginWebservicesMethodSession();
   $session->setSession($_GET['session']);
}

include (GLPI_ROOT . "/inc/includes.php");

Plugin::load('webservices', true);

Plugin::doHook("webservices");
plugin_webservices_registerMethods();

error_reporting(E_ALL);

// Fred : begin CORS OPTIONS HTTP request ...
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
   header('Access-Control-Allow-Origin: *');
//   header('Access-Control-Allow-Origin: http://admin.example.com');  
//   header("Access-Control-Allow-Credentials: true");
//   header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
//   header('Access-Control-Max-Age: 1000');
   header('Access-Control-Allow-Headers: Content-Type, Content-Range, Content-Disposition, Content-Description');
   header("HTTP/1.0 200");
   die("CORS headers");
}
// Fred : end ...

// Strip slashes in filter used in Select ... Where
if (isset($_GET['filter'])) {
   $_GET['filter'] = stripslashes($_GET['filter']);
}

// Manage POST/GET interface
$resp = array();
if (isset($_POST['method'])) {
   $session = new PluginWebservicesMethodSession();
   $resp    = $session->execute($_POST['method'], $_POST, WEBSERVICE_PROTOCOL_REST);
} else if (isset($_GET['method'])) {
   $session = new PluginWebservicesMethodSession();
   $resp    = $session->execute($_GET['method'], $_GET, WEBSERVICE_PROTOCOL_REST);
} else {
   header("HTTP/1.0 500 Missing 'method' parameter !");
}

// Send headers
header("Content-Type: application/json; charset=UTF-8");
if (isset($_POST['callback'])) {
   echo $_POST['callback'] . '('.json_encode($resp).')';
} else if (isset($_GET['callback'])) {
   echo $_GET['callback'] . '('.json_encode($resp).')';
} else {
   echo json_encode($resp);
}
?>