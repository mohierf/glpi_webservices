<?php
/**
 * @version $Id: client.class.php 465 2018-11-29 14:50:19Z yllen $
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

class PluginWebservicesClient extends CommonDBTM {

   public $dohistory = true;

   static $rightname = 'config';



   static function canCreate() {
      return Session::haveRight(static::$rightname, UPDATE);
   }


   /**
    * @since version 0.85
   **/
   static function canPurge() {
      return Session::haveRight(static::$rightname, UPDATE);
   }


   static function getTypeName($nb=0) {
      return _n('Client', 'Clients', $nb, 'webservices');
   }


   function defineTabs($options=[]) {

      $ong = [];
      $this->addDefaultFormTab($ong)
         ->addStandardTab(__CLASS__, $ong, $options)
         ->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if ($item->getType() == __CLASS__) {
         return __('Methods', 'webservices');
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType() == __CLASS__) {
         $item->showMethods();
         return true;
      }
      return false;
   }


   function prepareInputForAdd($input) {
      return $this->prepareInputForUpdate($input);
   }


   function prepareInputForUpdate($input) {

      if (isset($input['username'])) {
         if (empty($input['username'])) {
            $input['username'] = "NULL";
            $input['password'] = "NULL";
         } else {
            $input['password'] = md5(isset($input['password']) ? $input['password'] : '');
         }
      }

      if (isset($input['_start']) && isset($input['_end'])) {
         if (empty($input['_start'])) {
            $input['ip_start'] = "NULL";
            $input['ip_end'] = "NULL";
         } else {
            $input['ip_start'] = ip2long($input['_start']);
            if (empty($input['_end'])) {
               $input['ip_end'] = $input['ip_start'];
            } else {
               $input['ip_end'] = ip2long($input['_end']);
            }
            if ($input['ip_end'] < $input['ip_start']) {
               $tmp = $input['ip_end'];
               $input['ip_end'] = $input['ip_start'];
               $input['ip_start'] = $tmp;
            }
         }
      }
      return $input;
   }


   function showForm ($ID, $options=[]) {

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td><td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td rowspan='11' class='middle right'>".__('Comments')."</td>";
      echo "<td class='center middle' rowspan='11'><textarea cols='45' rows='16' name='comment' >".
             $this->fields["comment"]."</textarea></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td >".__('Enabled services', 'webservices')."</td><td>";
      Dropdown::showYesNo("is_active",$this->fields["is_active"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Compression enabled', 'webservices')."</td><td>";
      Dropdown::showYesNo("deflate",$this->fields["deflate"]);
      echo "<tr><td></td><td><i>". nl2br("Global configuration : edit the bundled .htaccess\n".
                                         "Dynamic deactivation (by client) available\n".
                                         "Dynamic activation requires > 5.3.0\n", "webservices");
      echo "</i></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td >".__('Log connections', 'webservices')."</td><td>";
      Dropdown::showFromArray("do_log", [0 => __('No'),
                                         1 => __('Historical'),
                                         2 => _n('Log', 'Logs', 2)],
                              ['value' => $this->fields["do_log"]]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td >".__('Debug')."</td><td>";
      Dropdown::showYesNo("debug",$this->fields["debug"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td >".__('SQL pattern for services', 'webservices')."</td><td>";
      Html::autocompletionTextField($this, "pattern");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td >".__('IPv4 address range', 'webservices')."</td><td>";
      echo "<input type='text' name='_start' value='".
            ($this->fields["ip_start"] ? long2ip($this->fields["ip_start"]) : '') .
            "' size='17'> - ";
      echo "<input type='text' name='_end' value='" .
            ($this->fields["ip_end"] ? long2ip($this->fields["ip_end"]) : '') .
            "' size='17'></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td >".__('IPv6 address', 'webservices')."</td><td>";
      echo "<input type='text' name='ipv6' value='".$this->fields['ipv6'].
            "' size='40'></td></tr>";

      echo "<tr class='tab_bg_1'><";
      echo "td >".__('User name', 'webservices')."</td><td>";
      Html::autocompletionTextField($this, "username");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td >".__('Password')."</td><td>";
      echo "<input type='text' name='password' size='40' />";
      echo "</td></tr>";

      $this->showFormButtons($options);

   }


   function showMethods() {
      global $WEBSERVICES_METHOD, $CFG_GLPI;

      $dbu = new DbUtils();

      echo "<div class='center'><br><table class='tab_cadre_fixehov'>";
      echo "<tr><th colspan='4'>".__('Method list - defined and allowed by this rule', 'webservices').
           "</th></tr>";
      echo "<tr><th>".__('Method name', 'webservices')."</th>" .
           "<th>".__('Provider plugin', 'webservices')."</th>" .
           "<th>".__('Internal function name', 'webservices')."</th>" .
           "<th>".__('Function is available', 'webservices')."</th></tr>";

      // Allow all plugins to register their methods
      $WEBSERVICES_METHOD = [];
      Plugin::doHook("webservices");

      foreach ($WEBSERVICES_METHOD as $method => $function) {
         // Display if MySQL REGEXP match
         if ($dbu->countElementsInTable($this->getTable(),
                 ['WHERE' => "ID='".$this->fields['id']."' AND '".addslashes($method)."' REGEXP pattern"]) > 0) {
            $result = $function;
            if (is_array($function)) {
               if ($tmp = isPluginItemType($function[0])) {
                  $plugin = $tmp['plugin'];
               } else {
                  $plugin="&nbsp;";
               }
               $result = implode('::',$function);
            } else if (preg_match('/^plugin_(.*)_method/', $function, $res)) {
               $plugin = $res[1];
            } else {
               $plugin = "&nbsp;";
            }
            $call  = (is_callable($function) ? __('Yes') : __('No'));
            $color = (is_callable($function) ? "greenbutton" : "redbutton");
            echo "<tr class='tab_bg_1'><td class='b'>$method</td><td>$plugin</td>".
                  "<td>$result</td><td class='center'>".
                  "<img src=\"".$CFG_GLPI['root_doc']."/pics/$color.png\" alt='ok'>&nbsp;$call</td></tr>";
         }
      }
      echo "</table></div>";
   }


   function rawSearchOptions() {

      $tab     = [];

      $tab[]   = ['id'              => 'common',
                  'name'            =>  __('Web Services', 'webservices')];

      $tab[]   = ['id'              => 1,
                  'table'           => $this->getTable(),
                  'field'           => 'name',
                  'name'            =>  __('Name'),
                  'datatype'        => 'itemlink'];

      $tab[]   = ['id'              => 3,
                  'table'           =>  $this->getTable(),
                  'field'           => 'comment',
                  'name'            => __('Comments'),
                  'datatype'        => 'text'];

      $tab[]   = ['id'              => 8,
                  'table'           => $this->getTable(),
                  'field'           => 'is_active',
                  'name'            => __('Enabled services', 'webservices'),
                  'datatype'        => 'bool'];

      $tab[]   = ['id'              => 9,
                  'table'           => $this->getTable(),
                  'field'           => 'do_log',
                  'name'            => __('Log connections', 'webservices')];

      $tab[]   = ['id'              => 10,
                  'table'           => $this->getTable(),
                  'field'           => 'deflate',
                  'name'            => __('Compression enabled', 'webservices')];

      $tab[]   = ['id'              => 13,
                  'table'           => $this->getTable(),
                  'field'           => 'ip',
                  'name'            => __('IP'),
                  'massiveaction'   => false];

      $tab[]   = ['id'              => 14,
                  'table'           => $this->getTable(),
                  'field'           => 'ipv6',
                  'name'            => __('IPv6 address', 'webservices')];

      $tab[]   = ['id'              => 17,
                  'table'           => $this->getTable(),
                  'field'           => 'pattern',
                  'name'            => __('SQL pattern for services', 'webservices')];

      return $tab;
   }


   static function install(Migration $migration) {
      global $DB;

      $table = 'glpi_plugin_webservices_clients';

      $migration->renameTable('glpi_plugin_webservices', $table);

      if ($DB->tableExists('glpi_plugin_webservices_clients')) {

         $migration->changeField($table, 'ID', 'id', 'autoincrement');
         $migration->changeField($table, 'FK_entities', 'entities_id', 'integer');
         $migration->changeField($table, 'recursive', 'is_recursive', 'bool');
         $migration->changeField($table, 'active', 'is_active', 'bool');
         $migration->changeField($table, 'comments', 'comment', 'text');
         $migration->changeField($table, 'FK_entities', 'entities_id', 'integer');

         $migration->addField($table, 'deflate', 'bool', ['after' => 'is_active']);
         $migration->addField($table, 'debug', 'bool', ['after' => 'do_log']);

         $migration->addKey($table, 'entities_id');

         // Version 1.3.0
         $opt = ['after'     => 'ip_end',
                 'update'    => "'::1'",
                 'condition' => "WHERE `ip_start`=INET_ATON('127.0.0.1')"];
         $migration->addField($table, 'ipv6', 'string', $opt);

      } else {
         $sql = "CREATE TABLE `glpi_plugin_webservices_clients` (
                  `id` INT NOT NULL AUTO_INCREMENT,
                  `entities_id` INT NOT NULL DEFAULT '0',
                  `is_recursive` TINYINT( 1 ) NOT NULL DEFAULT '0',
                  `name` VARCHAR( 255 ) NOT NULL ,
                  `pattern` VARCHAR( 255 ) NOT NULL ,
                  `ip_start` BIGINT NULL ,
                  `ip_end` BIGINT NULL ,
                  `ipv6`  VARCHAR( 255 ) NULL,
                  `username` VARCHAR( 255 ) NULL ,
                  `password` VARCHAR( 255 ) NULL ,
                  `do_log` TINYINT NOT NULL DEFAULT '0',
                  `debug` TINYINT NOT NULL DEFAULT '0',
                  `is_active` TINYINT NOT NULL DEFAULT '0',
                  `deflate` TINYINT NOT NULL DEFAULT '0',
                  `comment` TEXT NULL ,
                  PRIMARY KEY (`id`),
                  KEY `entities_id` (`entities_id`)
                ) ENGINE = InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
         $DB->queryOrDie($sql, "SQL Error");

         $sql = "INSERT INTO
                 `glpi_plugin_webservices_clients` (`id`, `entities_id`, `is_recursive`, `name`,
                                                    `pattern`, `ip_start`, `ip_end` , `ipv6`,
                                                    `do_log`, `is_active`, `comment`)
                 VALUES (NULL, 0, 1, '".__('Local', 'webservices')."',
                         '.*', INET_ATON('127.0.0.1'), INET_ATON('127.0.0.1'), '::1',
                         1, 1, '".__('Allow all from local', 'webservices')."')";
         $DB->query($sql);
      }
   }


   static function uninstall() {
      global $DB;

      $tables = ['glpi_plugin_webservices',
                 'glpi_plugin_webservices_clients'];

      foreach ($tables as $table) {
         $query = "DROP TABLE IF EXISTS `$table`;";
         $DB->query($query) or die($DB->error());
      }
   }


   /**
    * @since version 0.85
   **/
   static function getMenuName() {
      return __('Webservices');
   }


   /**
    * @since version 0.85
   **/
   static function getMenuContent() {

      $menu          = [];
      $menu['title'] = self::getMenuName();
      $menu['page']  = "/plugins/webservices/front/client.php";

      $menu['title']           = self::getMenuName();
      $menu['page']            = self::getSearchURL(false);

      if (Session::haveRight("config", UPDATE)) {
         $menu['links']['add'] = self::getFormURL(false);
      }
      return $menu;
   }


}
