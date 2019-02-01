<?php
/**
 * @version $Id: rest.php 449 2018-03-15 14:59:12Z yllen $
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
 @copyright Copyright (c) 2009-2018 Webservices plugin team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://forge.glpi-project.org/projects/webservices
 @link      http://www.glpi-project.org/
 @since     2009
 --------------------------------------------------------------------------
 */

if (!function_exists("json_encode")) {
   header("HTTP/1.0 500 Extension json not loaded");
   die("Extension json not loaded");
}

define('DO_NOT_CHECK_HTTP_REFERER', 1);
// Specific - start
if (! defined('GLPI_USE_CSRF_CHECK')) {
   define('GLPI_USE_CSRF_CHECK', 0);
}
// Specific - stop
define('GLPI_ROOT', '../..');

// define session_id before any other thing
// Specific - $_POST
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
// todo: check if really necessary!
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
   header('Access-Control-Allow-Origin: *');
   header('Access-Control-Allow-Headers: Content-Type, Content-Range, Content-Disposition, Content-Description');
   header("HTTP/1.0 200");
   die("CORS headers");
}
// Fred : end ...

$params = $_GET;
if (isset($_POST['method'])) {
   $params = $_POST;
}
$resp = [];
if (isset($params['fields'])) {
   $params['fields'] = json_decode(stripslashes($params['fields']), true);
}
// Strip slashes in filter used in Select ... Where
if (isset($params['filter'])) {
   $params['filter'] = stripslashes($params['filter']);
}

$method  = (isset($params['method']) ? $params['method'] : '');
if (empty($method)) {
   header("HTTP/1.0 500 Missing 'method' parameter !");
} else {
   $session = new PluginWebservicesMethodSession();
   $resp    = $session->execute($method, $params, WEBSERVICE_PROTOCOL_REST);
}

// Send UTF8 headers
// Specific, set application/json rather than text/html!
header("Content-Type: application/json; charset=UTF-8");
if (isset($_POST['callback'])) {
   echo $_POST['callback'] . '('.json_encode($resp).')';
} else if (isset($_GET['callback'])) {
   echo $_GET['callback'] . '('.json_encode($resp).')';
} else {
   echo json_encode($resp);
}
?>