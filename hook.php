<?php
/**
 * @version $Id: hook.php 452 2018-03-16 15:51:45Z yllen $
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

function plugin_webservices_registerMethods() {
   global $WEBSERVICES_METHOD;

   // Not authenticated method
   $WEBSERVICES_METHOD['glpi.test']
                              = ['PluginWebservicesMethodCommon','methodTest'];
   $WEBSERVICES_METHOD['glpi.status']
                              = ['PluginWebservicesMethodCommon','methodStatus'];
   $WEBSERVICES_METHOD['glpi.listAllMethods']
                              = ['PluginWebservicesMethodCommon','methodList'];
   $WEBSERVICES_METHOD['glpi.listEntities']
                              = ['PluginWebservicesMethodCommon','methodListEntities'];
   $WEBSERVICES_METHOD['glpi.doLogin']
                              = ['PluginWebservicesMethodSession','methodLogin'];
   $WEBSERVICES_METHOD['glpi.listKnowBaseItems']
                              = ['PluginWebservicesMethodTools','methodListKnowBaseItems'];
   $WEBSERVICES_METHOD['glpi.getKnowBaseItem']
                              = ['PluginWebservicesMethodTools','methodGetKnowBaseItem'];
   $WEBSERVICES_METHOD['glpi.getDocument']
                              = ['PluginWebservicesMethodInventaire','methodGetDocument'];

   // Authenticated method - Session
   $WEBSERVICES_METHOD['glpi.doLogout']
                              = ['PluginWebservicesMethodSession','methodLogout'];
   $WEBSERVICES_METHOD['glpi.getMyInfo']
                              = ['PluginWebservicesMethodSession','methodGetMyInfo'];
   $WEBSERVICES_METHOD['glpi.listMyProfiles']
                              = ['PluginWebservicesMethodSession','methodListMyProfiles'];
   $WEBSERVICES_METHOD['glpi.setMyProfile']
                              = ['PluginWebservicesMethodSession','methodSetMyProfile'];
   $WEBSERVICES_METHOD['glpi.listMyEntities']
                              = ['PluginWebservicesMethodSession','methodListMyEntities'];
   $WEBSERVICES_METHOD['glpi.setMyEntity']
                              = ['PluginWebservicesMethodSession','methodSetMyEntity'];

   // Authenticated method - Others
   $WEBSERVICES_METHOD['glpi.listDropdownValues']
                              = ['PluginWebservicesMethodCommon','methodListDropdownValues'];
   $WEBSERVICES_METHOD['glpi.listHelpdeskTypes']
                              = ['PluginWebservicesMethodHelpdesk','methodListHelpdeskTypes'];
   $WEBSERVICES_METHOD['glpi.listHelpdeskItems']
                              = ['PluginWebservicesMethodHelpdesk','methodListHelpdeskItems'];
   $WEBSERVICES_METHOD['glpi.listTickets']
                              = ['PluginWebservicesMethodHelpdesk','methodListTickets'];
   $WEBSERVICES_METHOD['glpi.listGroups']
                              = ['PluginWebservicesMethodInventaire','methodListGroups'];
   $WEBSERVICES_METHOD['glpi.listUsers']
                              = ['PluginWebservicesMethodInventaire','methodListUsers'];

   // Inventory
   $WEBSERVICES_METHOD['glpi.listInventoryObjects']
                              = ['PluginWebservicesMethodInventaire','methodListInventoryObjects'];
   $WEBSERVICES_METHOD['glpi.listObjects']
                              = ['PluginWebservicesMethodInventaire','methodListObjects'];
   $WEBSERVICES_METHOD['glpi.getObject']
                              = ['PluginWebservicesMethodInventaire','methodGetObject'];

   // Inventory : write methods
   $WEBSERVICES_METHOD['glpi.createObjects']
                              = ['PluginWebservicesMethodInventaire','methodCreateObjects'];
   $WEBSERVICES_METHOD['glpi.deleteObjects']
                              = ['PluginWebservicesMethodInventaire','methodDeleteObjects'];
   $WEBSERVICES_METHOD['glpi.updateObjects']
                              = ['PluginWebservicesMethodInventaire','methodUpdateObjects'];
   $WEBSERVICES_METHOD['glpi.linkObjects']
                              = ['PluginWebservicesMethodInventaire','methodLinkObjects'];

   //Inventor : generic methods
   $WEBSERVICES_METHOD['glpi.getInfocoms']
                              = ['PluginWebservicesMethodInventaire','methodGetItemInfocoms'];
   $WEBSERVICES_METHOD['glpi.getContracts']
                              = ['PluginWebservicesMethodInventaire','methodGetItemContracts'];

   //Inventory : computer
   $WEBSERVICES_METHOD['glpi.getNetworkports']
                              = ['PluginWebservicesMethodInventaire','methodGetNetworkports'];
   $WEBSERVICES_METHOD['glpi.getPhones']
                              = ['PluginWebservicesMethodInventaire','methodGetPhones'];


   //Helpdesk
   $WEBSERVICES_METHOD['glpi.getTicket']
                              = ['PluginWebservicesMethodHelpdesk','methodGetTicket'];
   $WEBSERVICES_METHOD['glpi.getHelpdeskConfiguration']
                              = ['PluginWebservicesMethodHelpdesk','methodGetHelpdeskConfiguration'];
   $WEBSERVICES_METHOD['glpi.createTicket']
                              = ['PluginWebservicesMethodHelpdesk','methodCreateTicket'];
   $WEBSERVICES_METHOD['glpi.addTicketFollowup']
                              = ['PluginWebservicesMethodHelpdesk','methodAddTicketFollowup'];
   $WEBSERVICES_METHOD['glpi.addTicketDocument']
                              = ['PluginWebservicesMethodHelpdesk','methodAddTicketDocument'];
   $WEBSERVICES_METHOD['glpi.addTicketObserver']
                              = ['PluginWebservicesMethodHelpdesk','methodAddTicketObserver'];
   $WEBSERVICES_METHOD['glpi.setTicketSatisfaction']
                              = ['PluginWebservicesMethodHelpdesk','methodsetTicketSatisfaction'];
   $WEBSERVICES_METHOD['glpi.setTicketValidation']
                              = ['PluginWebservicesMethodHelpdesk','methodsetTicketValidation'];
   $WEBSERVICES_METHOD['glpi.setTicketSolution']
                              = ['PluginWebservicesMethodHelpdesk','methodsetTicketSolution'];
   $WEBSERVICES_METHOD['glpi.setTicketAssign']
                              = ['PluginWebservicesMethodHelpdesk','methodsetTicketAssign'];
   $WEBSERVICES_METHOD['glpi.addTicketTask']
                              = ['PluginWebservicesMethodHelpdesk','methodAddTicketTask'];

}


// Install process for plugin : need to return true if succeeded
function plugin_webservices_install() {

   $migration = new Migration(130);

   // No autoload when plugin is not activated
   require_once 'inc/client.class.php';
   PluginWebservicesClient::install($migration);

   $migration->executeMigration();

   return true;
}


// Uninstall process for plugin : need to return true if succeeded
function plugin_webservices_uninstall() {

   // No autoload when plugin is not activated
   require_once 'inc/client.class.php';
   PluginWebservicesClient::uninstall();

   return true;
}


function plugin_webservices_giveItem($type,$ID,$data,$num) {

   $searchopt  = &Search::getOptions($type);
   $table      = $searchopt[$ID]["table"];
   $field      = $searchopt[$ID]["field"];

   switch ($table.'.'.$field) {
      case "glpi_plugin_webservices_clients.do_log" :
            switch ($data["ITEM_$num"]) {
               case 2 :
                  return _n('Log', 'Logs', 2);

               case 1:
                  return __('Historical');

               default:
                  return __('No');
            }
         break;
   }

   return '';
}


function plugin_webservices_addSelect($type,$ID,$num) {

   $searchopt = &Search::getOptions($type);

   $table=$searchopt[$ID]["table"];
   $field=$searchopt[$ID]["field"];

   switch ($table.'.'.$field) {
      case 'glpi_plugin_webservices_clients.ip' :
         return " CONCAT(INET_NTOA(ip_start), ' - ', INET_NTOA(ip_end)) AS ITEM_$num, ";
   }
   return '';
}


function plugin_webservices_addWhere($link,$nott,$type,$ID,$val) {

   $NOT        = ($nott ? " NOT" : "");
   $searchopt  = &Search::getOptions($type);
   $table      = $searchopt[$ID]["table"];
   $field      = $searchopt[$ID]["field"];

   switch ($table.'.'.$field) {
      case 'glpi_plugin_webservices_clients.ip' :
         return " $link $NOT (INET_ATON('$val') >= ip_start
                              AND INET_ATON('$val') <= ip_end) ";

      case 'pattern' :
         return " $link '$val' $NOT REGEXP pattern ";
   }
   return '';
}


function plugin_webservices_addOrderBy($type, $ID, $order) {

   $searchopt  = &Search::getOptions($type);
   $table      = $searchopt[$ID]["table"];
   $field      = $searchopt[$ID]["field"];

   switch ($table.'.'.$field) {
      case 'glpi_plugin_webservices_clients.ip' :
         return " ORDER BY INET_NTOA(`ip_start`) $order,
                           INET_NTOA(`ip_end`) $order ";
   }
   return '';
}


function cron_plugin_webservices() {

   Toolbox::logInFile('webservices', "cron called\n");
   // todo: where does it exist?
   plugin_webservices_soap_create_wdsl();
   return 1;

}
