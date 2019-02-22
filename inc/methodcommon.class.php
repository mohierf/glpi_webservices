<?php
/**
 * @version $Id: methodcommon.class.php 460 2018-10-04 14:16:44Z yllen $
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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

// Standard error Code
define('WEBSERVICES_ERROR_NOTFOUND',         10);
define('WEBSERVICES_ERROR_MISSINGPARAMETER', 11);
define('WEBSERVICES_ERROR_LOGINFAILED',      12);
define('WEBSERVICES_ERROR_NOTAUTHENTICATED', 13);
define('WEBSERVICES_ERROR_BADPARAMETER',     14);
define('WEBSERVICES_ERROR_FAILED',           15);
define('WEBSERVICES_ERROR_NOTALLOWED',       16);
define('WEBSERVICES_ERROR_NOTALLOWEDl',      17);

define('WEBSERVICES_REGEX_DATETIME', '/^(19|20)\d{2}-(0|1)\d-[0-3]\d [0-2]\d:[0-5]\d:[0-5]\d$/');
define('WEBSERVICES_REGEX_DATE',     '/^(19|20)\d{2}-(0|1)\d-[0-3]\d$/');

//Communication protocols
define('WEBSERVICE_PROTOCOL_XMLRPC', 'xml-rpc');
define('WEBSERVICE_PROTOCOL_SOAP', 'soap');
define('WEBSERVICE_PROTOCOL_REST', 'rest');
define('LOGFILENAME', 'webservices');


class PluginWebservicesMethodCommon {

   static function methodTest($params) {
      global $PLUGIN_HOOKS;

      if (isset($params['help'])) {
         return ['help' => 'bool,optional'];
      }

      $resp = ['glpi' => GLPI_VERSION];

      $plugin = new Plugin();
      foreach ($PLUGIN_HOOKS['webservices'] as $name => $fct) {
         if ($plugin->getFromDBbyDir($name)) {
            $resp[$name] = $plugin->fields['version'];
         }
      }

      return $resp;
   }


   /**
    * Build a XML-RPC Response with an error status
    *
    * @param $protocol string, communication protocol used
    * @param $code      integer  value of the error code
    * @param $message   string   description of the error (default '')
    * @param $more      string   additional $message (default '')
    *
    * @return array|SoapFault, response (fault) ready to be encode
   **/
   static function Error($protocol, $code, $message='', $more='') {

      if (empty($message)) {
         switch($code) {
            case WEBSERVICES_ERROR_NOTFOUND :
               $message = 'Not found';
               break;

            case WEBSERVICES_ERROR_MISSINGPARAMETER :
               $message = 'Missing parameter';
               break;

            case WEBSERVICES_ERROR_LOGINFAILED :
               $message = 'Login failed';
               break;

            case WEBSERVICES_ERROR_NOTAUTHENTICATED :
               $message = 'Not authenticated';
               break;

            case WEBSERVICES_ERROR_BADPARAMETER :
               $message = 'Bad parameter';
               break;

            case WEBSERVICES_ERROR_FAILED :
               $message = 'Command failed';
               break;

            case WEBSERVICES_ERROR_NOTALLOWED :
               $message = 'Command not allowed';
               break;

            default :
               $message = 'Error';
         }
      }

      if (!empty($more)) {
         $message .= ' (' . $more . ')';
      }

      switch ($protocol) {
         case WEBSERVICE_PROTOCOL_SOAP :
            return new SoapFault((string)$code, $message);

         default :
            return ["faultCode"   => intval($code),
                    "faultString" => $message];
      }
   }


   static function getDisplayError() {

      if (isset($_SESSION["MESSAGE_AFTER_REDIRECT"])
          && !empty($_SESSION["MESSAGE_AFTER_REDIRECT"])) {

         $ret = "";
         foreach ($_SESSION["MESSAGE_AFTER_REDIRECT"] as $type => $msgs) {
            foreach ($msgs as $msg) {
               $ret .= Html::clean($msg)."\n";
            }
         }
         $_SESSION["MESSAGE_AFTER_REDIRECT"] = [];
         return $ret;
      }
      return '';
   }


   static function isError($protocol, $response) {

      switch ($protocol) {
         case WEBSERVICE_PROTOCOL_SOAP :
            return ($response instanceof SoapFault);

         default :
            return (isset($response['faultCode']));
      }
   }


   /**
    * This method return the list of all methods
    *
    * TODO: to be filtered for the client rights
    *
    * @param $params    array of option : ignored
    * @param $protocol  string, communication protocol used
    *
    * @return array, response ready to be encode
   **/
   static function methodList($params) {
      global $WEBSERVICES_METHOD;

      if (isset($params['help'])) {
         return ['help' => 'bool,optional'];
      }
      return $WEBSERVICES_METHOD;
   }


   /**
    * This method return GLPI status (same as status.php)
    *
    * @param $params    array of option : ignored
    * @return array, response ready to be encode
   **/
   static function methodStatus($params) {

      if (isset($params['help'])) {
         return ['help' => 'bool,optional'];
      }

      $resp       = [];
      $ok_master  = true;
      $ok_slave   = true;

      // Check slave server connection
      if (DBConnection::isDBSlaveActive()) {
         $DBslave = DBConnection::getDBSlaveConf();
         if (is_array($DBslave->dbhost)) {
            $hosts = $DBslave->dbhost;
         } else {
            $hosts = [$DBslave->dbhost];
         }

         foreach ($hosts as $num => $name) {
            $diff = DBConnection::getReplicateDelay($num);
            if ($diff > 1000000000) {
               $resp['slavedb_'.$num] = "offline";
               $ok_slave = false;
            } else if ($diff) {
               $resp['slavedb_'.$num] = $diff;
               if ($diff > HOUR_TIMESTAMP) {
                  $ok_slave = false;
               }
            } else {
               $resp['slavedb_'.$num] = "ok";
            }
         }
      } else {
         $resp['slavedb'] = "not configured";
      }

      // Check main server connection
      if (DBConnection::establishDBConnection(false, true, false)) {
         $resp['maindb'] = "ok";
      } else {
         $resp['slavedb'] = "offline";
         $ok_master = false;
      }
      // Slave and master ok;
      $ok = $ok_slave && $ok_master;

      // Check session dir (usefull when NFS mounted))
      if (is_dir(GLPI_SESSION_DIR) && is_writable(GLPI_SESSION_DIR)) {
         $resp['sessiondir'] = "ok";
      } else {
         $resp['sessiondir'] = "not writable";
         $ok = false;
      }

      // Reestablished DB connection
      if (($ok_master || $ok_slave)
          && DBConnection::establishDBConnection(false, false, false)) {

         // Check Auth connections
         $auth = new Auth();
         $auth->getAuthMethods();
         $ldap_methods = $auth->authtypes["ldap"];

         if (count($ldap_methods)) {
            foreach ($ldap_methods as $method) {
               if ($method['is_active']) {
                  if (AuthLdap::tryToConnectToServer($method, $method["rootdn"],
                                                     Toolbox::decrypt($method["rootdn_passwd"],
                                                                      GLPIKEY))) {
                     $resp['LDAP_' . $method['name']] = "ok";
                  } else {
                     $resp['LDAP_' . $method['name']] = "offline";
                     $ok = false;
                  }
               }
            }
         }
      }

      if ($ok) {
         $resp['glpi'] = "ok";
      } else {
         $resp['glpi'] = "error";
      }
      return $resp;
   }


   /**
    * This method return the entities list for the client
    *
    * @param $params    array of option : ignored
    * @param $protocol string, communication protocol used
    *
    * @return array, response ready to be encode (ID + completename)
   **/
   static function methodListEntities($params, $protocol) {
      global $DB, $CFG_GLPI;

      if (isset($params['help'])) {
         return ['count' => 'bool,optional',
                 'start' => 'integer,optional',
                 'limit' => 'integer,optional',
                 'help'  => 'bool,optional'];
      }

      // Should never occurs, just to show howto to handle an error
      if (!count($_SESSION['glpiactiveentities'])) {
         return self::Error($protocol, WEBSERVICES_ERROR_NOTFOUND);
      }

      $resp = [];

      // Count only
      if (isset($params['count'])) {
         $resp['count'] = count($_SESSION['glpiactiveentities']);
         return $resp;
      }

      $start = 0;
      $limit = $CFG_GLPI["list_limit_max"];
      if (isset($params['limit']) && is_numeric($params['limit'])) {
         $limit = $params['limit'];
      }
      if (isset($params['start']) && is_numeric($params['start'])) {
         $start = $params['start'];
      }

      // Root entity
      if (isset($_SESSION['glpiactiveentities'][0]) && !$start) {
         $resp[] = ['id'           => 0,
                    'completename' => __('Root entity')];
         $limit--;
      }

      // Other allowed entities
      foreach ($DB->request("glpi_entities", ['WHERE' => ['id' => $_SESSION['glpiactiveentities']],
                                              'ORDER' => 'completename',
                                              'START' => $start,
                                              'LIMIT' => $limit]) as $entity) {
         // Specific - provide more information than only id and completename!
         $resp[] = [
            'id'             => $entity['id'],
            'name'           => $entity['name'],
            'completename'   => $entity['completename'],
            'comment'        => $entity['comment'],
            'level'          => $entity['level'],
            'address'        => $entity['address'],
            'postcode'       => $entity['postcode'],
            'town'           => $entity['town'],
            'state'          => $entity['state'],
            'country'        => $entity['country'],
            'website'        => $entity['website'],
            'phonenumber'    => $entity['phonenumber'],
            'fax'            => $entity['fax'],
            'email'          => $entity['email'],
            'tag'            => $entity['tag']
         ];
      }

      return $resp;
   }


   /**
    * This method manage upload of files into GLPI
    *
    * @param $params          array of parameters
    * @param $protocol        string   protocol used for remote call
    * @param $filepath        string   path of the file on the filesystem
    *
    * @return array|boolean if an error, or boolean true
   **/
   static function uploadDocument($params, $protocol, $filepath) {

      if (!isset($params['uri']) && !isset($params['base64'])) {
         return self::Error($protocol, WEBSERVICES_ERROR_MISSINGPARAMETER, '','uri or base64');
      }

      if (isset($params['uri'])) {
         $content = @file_get_contents($params['uri']);
         if (!$content) {
            return self::Error($protocol, WEBSERVICES_ERROR_NOTFOUND, '', $params['uri']);
         }

      } else if (isset($params['base64'])) {
         $content = base64_decode($params['base64']);
         if (!$content) {
            return self::Error($protocol, WEBSERVICES_ERROR_BADPARAMETER, '', $params['base64']);
         }
      }

      $size = file_put_contents($filepath, $content);
      if (!$size) {
         return self::Error($protocol, WEBSERVICES_ERROR_FAILED, '', $filepath);
      }

      return true;
   }


   /**
    * Return data formatted
    *
    * @param $params array of the needed parameters
    * @param $output array which contains the data to be sent to the client
   **/
   static function formatDataForOutput($params, &$output) {

      $dbu = new DbUtils();

      $blacklisted_fields = ['items_id'];

      $p['searchOptions'] = [];
      $p['data']          = [];
      $p['options']       = [];
      $p['subtype']       = false;

      foreach ($params as $key => $value) {
         $p[$key] = $value;
      }

      $p['table']          = $dbu->getTableForItemType($p['options']['itemtype']);
      $p['show_label']     = $p['options']['show_label'];
      $p['show_name']      = $p['options']['show_name'];
      $p['return_fields']  = $p['options']['return_fields'];

      $p['searchOptions'][999]['table']       = $p['table'];
      $p['searchOptions'][999]['field']       = 'id';
      $p['searchOptions'][999]['linkfield']   = 'id';
      $p['searchOptions'][999]['name']        = __('Login');

      $tmp = [];
      foreach($p['searchOptions'] as $id => $option) {
         if (isset($option['table'])) {
            if (!isset($option['linkfield']) || empty($option['linkfield'])) {
               if ($p['table'] == $option['table']) {
                  $linkfield = $option['name'];
               } else {
                  $linkfield = getForeignKeyFieldForTable($p['table']);
               }
            } else {
               $linkfield = $option['linkfield'];
            }

            if (isset($p['data'][$linkfield])
                && ($p['data'][$linkfield] != '')
                && (empty($p['return_fields'][$p['options']['itemtype']])
                    || (!empty($p['return_fields'][$p['options']['itemtype']])
                        && in_array($linkfield, $p['return_fields'][$p['options']['itemtype']])))) {

               $tmp[$linkfield] = $p['data'][$linkfield];
               if ($p['show_label']) {
                  $tmp[$linkfield."_label"] = $option['name'];
               }
               if ($p['show_name']) {
                   //If field is an FK and is not blacklisted !
                   if (self::isForeignKey($linkfield)
                       && !in_array($linkfield, $blacklisted_fields)
                       && (!isset($option['datatype'])
                           || isset($option['datatype']) && ($option['datatype'] != 'itemlink'))) {

                      $option_name = str_replace("_id", "_name", $linkfield);
                      $result      = Dropdown::getDropdownName($option['table'],
                                                               $p['data'][$linkfield]);
                      if ($result != '&nbsp;') {
                         $tmp[$option_name] = $result;
                      }

                   } else {
                      //Should exists if we could get results directly from the search engine...
                      if (isset($option['datatype'])) {
                         $option_name = $linkfield."_name";
                         switch ($option['datatype']) {
                            case 'date':
                               $tmp[$linkfield] = Html::convDateTime($p['data'][$linkfield]);
                               break;

                            case 'bool':
                               $tmp[$option_name] = Dropdown::getYesNo($p['data'][$linkfield]);
                               break;

                            case 'itemlink':
                               /* @var $obj CommonDBTM */
                                  if (isset($option['itemlink_type'])) {
                                     $obj = new $option['itemlink_type']();
                                  } else {
                                     $itemtype = $dbu->getItemTypeForTable($option['table']);
                                     $obj = new $itemtype();
                                  }
                                  $obj->getFromDB($p['data'][$linkfield]);
                                  $tmp[$linkfield]   = $p['data'][$linkfield];
                                  $tmp[$option_name] = $obj->getField($option['field']);
                               break;

                            case 'itemtype':
                               if ($obj = $dbu->getItemForItemtype($p['data'][$linkfield])) {
                                  $tmp[$option_name] = $obj->getTypeName();
                               }
                               break;
                         }
                      }
                   }
               }
            }
         }
      }
      if (!empty($tmp)) {
         $output = $tmp;
      }
   }


   static public function isForeignKey($field) {

      if (preg_match("/s_id/",$field)) {
         return true;
      }
      return false;
   }


   /**
    * return the content of hardcoded dropdown
    *
    * @param $name string name f the dropdown
    *
    * @return array (or false if unknown name)
   **/
   private static function listSpecialDropdown($name='') {
      global $CFG_GLPI;

      $resp = [];

      switch (strtolower($name)) {
         case 'ticketstatus' :
            $tab = Ticket::getAllStatusArray();
            foreach ($tab as $id => $label) {
               $resp[] = ['id'    => $id,
                          'name'  => $label];
            }
            break;

         case 'ticketurgency' :
            for ($i=1 ; $i<=5 ; $i++) {
               if (($i == 3)
                   || ($CFG_GLPI['urgency_mask'] & (1<<$i))) {
                  $resp[] = ['id'    => $i,
                             'name'  => Ticket::getUrgencyName($i)];
               }
            }
            break;

         case 'ticketimpact' :
            for ($i=1 ; $i<=5 ; $i++) {
               if (($i == 3)
                   || ($CFG_GLPI['impact_mask'] & (1<<$i))) {
                  $resp[] = ['id'    => $i,
                             'name'  => Ticket::getImpactName($i)];
               }
            }
            break;

         case 'tickettype' :
            foreach ([Ticket::INCIDENT_TYPE, Ticket::DEMAND_TYPE] as $type) {
               $resp[] = ['id'    => $type,
                          'name'  => Ticket::getTicketTypeName($type)];
            }
            break;

         case 'ticketpriority' :
            for ($i=1 ; $i<=5 ; $i++) {
               $resp[] = ['id'    => $i,
                          'name'  => Ticket::getPriorityName($i)];
            }
            break;

         case 'ticketglobalvalidation' :
            $tab = TicketValidation::getAllStatusArray(false, true);
            foreach ($tab as $id => $label) {
               $resp[] = ['id'    => $id,
                          'name'  => $label];
            }
            break;

         case 'ticketvalidationstatus' :
            $tab = TicketValidation::getAllStatusArray();
            foreach ($tab as $id => $label) {
               $resp[] = ['id'    => $id,
                          'name'  => $label];
            }
            break;

         default:
            $resp = false;
      }
      return $resp;
   }


   /**
    * List value for a dropdown, with search criterias
    * for an authenticated user
    *
    * @param $params    array of options (dropdown, id, parent, name)
    * @param $protocol  string, commonication protocol used
    *
    * @return array of hashtable
   **/
   static function methodListDropdownValues($params, $protocol) {
      global $DB;

      if (isset($params['help'])) {
         return ['dropdown' => 'string,mandatory',
                 'id'       => 'integer,optional',
                 'parent'   => 'integer,optional',
                 'criteria' => 'string, optional',
                 'name'     => 'string,optional',
                 'help'     => 'bool,optional',
                 'start'    => 'integer,optional',
                 'limit'    => 'integer,optional'];
      }

      $dbu = new DbUtils();

      if (!Session::getLoginUserID()) {
         return self::Error($protocol, WEBSERVICES_ERROR_NOTAUTHENTICATED);
      }

      if (!isset($params['dropdown'])) {
         return self::Error($protocol, WEBSERVICES_ERROR_MISSINGPARAMETER);
      }

      $resp = self::listSpecialDropdown($params['dropdown']);
      if (is_array($resp)) {
         return $resp;
      }

      if (class_exists($type=$params['dropdown'])) {
         $table = $dbu->getTableForItemType($type);
      } else if ($DB->tableExists($table='glpi_' . $params['dropdown'])) {
         $type = $dbu->getItemTypeForTable($table);
      }

      if (!($item = $dbu->getItemForItemtype($type))) {
         return self::Error($protocol, WEBSERVICES_ERROR_BADPARAMETER, '', $params['dropdown']);
      }

      // Right check
      if (!($item instanceof CommonDropdown)
          && !$item->canView()) {
         return self::Error($protocol, WEBSERVICES_ERROR_NOTALLOWED);
      }

      $start = 0;
      $limit = $_SESSION['glpilist_limit'];
      if (isset($params['limit']) && is_numeric($params['limit'])) {
         $limit = $params['limit'];
      }
      if (isset($params['start']) && is_numeric($params['start'])) {
         $start = $params['start'];
      }

      // Minimal visible fields
      $fields = ['name', 'completename', 'comment', 'entities_id', 'locations_id', 'is_recursive',
                       'is_incident', 'is_request', 'is_uploadable', 'ext'];
      $fields[] = getForeignKeyFieldForTable($table);

      $query = "SELECT `id`";
      foreach ($fields as $field) {
         if ($item->isField($field)) {
            $query .= ", `$field`";
         }
      }
      $query .= "\nFROM `$table`";

      if (isset($params['id']) && is_numeric($params['id'])) {
         $query .= " WHERE `id` = '" . $params['id'] . "'";
      } else {
         $query .= " WHERE 1";
      }

      if ($item->isEntityAssign()) {
         $query .= $dbu->getEntitiesRestrictRequest(" AND ", $table, '', '', $item->maybeRecursive());
      }
      if (isset($params['parent'])
          && is_numeric($params['parent'])
          && ($item instanceof CommonTreeDropdown)) {

         $query .= " AND ".getForeignKeyFieldForTable($table)."='" . $params['parent'] . "'";
      }
      if (isset($params['helpdesk'])   // deprecated, use criteria= helpdeskvisible
          && $params['helpdesk']
          && $item->isField('is_helpdeskvisible')) {
         $query .= " AND `is_helpdeskvisible` ";
      }
      if (isset($params['criteria'])
          && $params['criteria']
          && $item->isField('is_'.$params['criteria'])) {
         $query .= " AND `is_".$params['criteria']."` ";
      }
      if (isset($params['name'])) {
         if ($item instanceof CommonTreeDropdown) {
            $query .= " AND `completename` LIKE '" . addslashes($params['name']) . "'";
         } else {
            $query .= " AND `name` LIKE '" . addslashes($params['name']) . "'";
         }
      }
      $query.= " LIMIT $start,$limit";

      $resp = [];
      foreach ($DB->request($query) as $data) {
         $resp[] = $data;
      }
      return $resp;
   }


   /**
    * function to checks of a user passed by parameters in a method
    *
    * @param $user       integer                id of the user to check rights
    * @param $right      string                 right to check
    * @param $valright   integer/string/array   value of the rights searched
    * @param $entity     integer                id of the entity
    *
    * @return boolean
    */
   static function checkUserRights($user, $right, $valright, $entity) {
      global $DB;

      $dbu = new DbUtils();

      $query = "SELECT `glpi_profilerights`.`rights`
                FROM `glpi_profilerights`
                LEFT JOIN `glpi_profiles`
                   ON (`glpi_profiles`.`id` = `glpi_profilerights`.`profiles_id`)
                INNER JOIN `glpi_profiles_users`
                   ON (`glpi_profiles`.`id` = `glpi_profiles_users`.`profiles_id`)
                WHERE `glpi_profiles_users`.`users_id` = '$user'
                      AND `glpi_profilerights`.`name` = '$right'
                      AND (`glpi_profilerights`.`rights` & ". $valright.") ".
                      $dbu->getEntitiesRestrictRequest(" AND ", "glpi_profiles_users", '', $entity, true);

      if ($result = $DB->request($query)) {
         if (count($result)) {
            return true;
         }
      }
      return false;

   }

}
