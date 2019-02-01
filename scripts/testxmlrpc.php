<?php
/**
 * @version $Id: testxmlrpc.php 452 2018-03-16 15:51:45Z yllen $
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

// ----------------------------------------------------------------------
// Purpose of file: Test the XML-RPC plugin from Command Line
// ----------------------------------------------------------------------

if (!extension_loaded("xmlrpc")) {
   die("Extension xmlrpc not loaded\n");
}
chdir(dirname($_SERVER["SCRIPT_FILENAME"]));
chdir("../../..");
$url = "/".basename(getcwd())."/plugins/webservices/xmlrpc.php";

$confname = getcwd();
$configfile = (getenv('HOME') ? getenv('HOME') . '/.config' :  sys_get_temp_dir()) . '/glpi_webservices_config' ;

$args = [];
if ($_SERVER['argc']>1) {
   for ($i=1 ; $i<count($_SERVER['argv']) ; $i++) {
      $it           = explode("=",$argv[$i],2);
      $it[0]        = preg_replace('/^--/','',$it[0]);
      $args[$it[0]] = (isset($it[1]) ? $it[1] : true);
   }
}

if (isset($args['help']) && !isset($args['method'])) {
   echo "\nusage : ".$_SERVER["SCRIPT_FILENAME"]." [ options] \n\n";

   echo "\thelp     : display this screen\n";
   echo "\thost     : server name or IP, default : localhost\n";
   echo "\thttps    : use https (instead of http)\n";
   echo "\turl      : XML-RPC plugin URL, default : $url\n";
   echo "\tusername : User name for security check (optionnal)\n";
   echo "\tpassword : User password (optionnal)\n";
   echo "\tmethod   : XML-RPC method to call, default : glpi.test\n";
   echo "\tdeflate  : allow server to compress response (if supported)\n";

   die( "\nOther options are used for XML-RPC call.\n\n");
}

$fullconf = [];
if (file_exists($configfile)) {
   if ($txt = file_get_contents($configfile)) {
      if ($fullconf = json_decode($txt, true)) {
         echo "+ Configuration read from '$configfile'\n";
      }
      $conf = (isset($fullconf[$confname]) ? $fullconf[$confname] : false);
   }
}

if (isset($args['url'])) {
   $url = $args['url'];
   unset($args['url']);
} else if (isset($conf['url'])) {
   $url = $conf['url'];
}

if (isset($args['https'])) {
   $proto = 'https';
   unset($args['https']);
} else if (isset($conf['proto'])) {
   $proto = $conf['proto'];
} else {
   $proto = 'http';
}

if (isset($args['host'])) {
   $host = $args['host'];
   unset($args['host']);
} else if (isset($conf['host'])) {
   $host = $conf['host'];
} else {
   $host = 'localhost';
}

if (isset($args['method'])) {
   $method = $args['method'];
   unset($args['method']);
} else {
   $method = 'glpi.test';
}

if (isset($args['session'])) {
   $url .= '?session='.$args['session'];
   unset($args['session']);
} if (isset($conf['session'])) {
   $url .= '?session='.$conf['session'];
}

$header = "Content-Type: text/xml";

if (isset($args['deflate'])) {
   unset($args['deflate']);
   $header .= "\nAccept-Encoding: deflate";
}

if (isset($args['base64'])) {
   $content = @file_get_contents($args['base64']);
   if (!$content) {
      die ("File not found or empty (".$args['base64'].")\n");
   }
   $args['base64'] = base64_encode($content);
}

foreach($args as $key => $value) {
   if (substr($value, 0, 5)=='json:') {
      $args[$key] = json_decode(substr($value, 5), true);
   }
}
echo "+ Calling '$method' on $proto://$host/$url\n";

$request = xmlrpc_encode_request($method, $args);
$context = stream_context_create(['http' => ['method'  => "POST",
                                             'header'  => $header,
                                             'content' => $request]]);

$file = file_get_contents("$proto://$host/$url", false, $context);
if (!$file) {
   die("+ No response\n");
}

if (in_array('Content-Encoding: deflate', $http_response_header)) {
   $lenc=strlen($file);
   echo "+ Compressed response : $lenc\n";
   $file = gzuncompress($file);
   $lend = strlen($file);
   echo "+ Uncompressed response : $lend (".round(100.0*$lenc/$lend)."%)\n";
}
$response = xmlrpc_decode($file, "UTF-8");
if (!is_array($response)) {
   echo $file;
   die ("+ Bad response\n");
}

if (xmlrpc_is_fault($response)) {
    echo("xmlrpc error(".$response['faultCode']."): ".$response['faultString']."\n");
} else {
   if (isset($response['base64']) && isset($response['name'])) {
      $dest = sys_get_temp_dir() . '/' . basename($response['name']);
      if (file_put_contents($dest, base64_decode($response['base64']))) {
         $response['base64'] = "** saved in '$dest' **";
      }
   }
   if (($method == 'glpi.doLogin') && isset($response['session'])) {
      $fullconf[$confname] = ['proto'   => $proto,
                              'host'    => $host,
                              'url'     => preg_replace('/\?session=.*$/', '', $url),
                              'session' => $response['session']];
      $json = json_encode($fullconf, (defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : 0));
      if (file_put_contents($configfile, $json)) {
         echo "+ Configuration saved in '$configfile'\n";
      }
   }
   echo "+ Response: ";
   print_r($response);
}
