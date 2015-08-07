<?php
/**
 * @version $Id$
 -------------------------------------------------------------------------
 LICENSE

 This file is part of PDF plugin for GLPI.

 PDF is free software: you can redistribute it and/or modify
 it under the terms of the GNU Affero General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 PDF is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU Affero General Public License for more details.

 You should have received a copy of the GNU Affero General Public License
 along with Reports. If not, see <http://www.gnu.org/licenses/>.

 @package   pdf
 @authors   Nelly Mahu-Lasson, Remi Collet
 @copyright Copyright (c) 2009-2015 PDF plugin team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://forge.indepnet.net/projects/pdf
 @link      http://www.glpi-project.org/
 @since     2009
 --------------------------------------------------------------------------
*/

$token = (isset($_POST['_glpi_csrf_token']) ? $_POST['_glpi_csrf_token'] : false); 

include ("../../../inc/includes.php");

/* Hack to allow multiple exports, yes this is an hack, yes an awful one */
if (isset($_SESSION['glpicsrftokens'])) {
   $_SESSION['glpicsrftokens'][$token] = time() + GLPI_CSRF_EXPIRES;
}

Plugin::load('pdf', true);


if (isset($_POST["plugin_pdf_inventory_type"])
    && ($item = getItemForItemtype($_POST["plugin_pdf_inventory_type"]))
    && isset($_POST["itemID"])) {

   $type = $_POST["plugin_pdf_inventory_type"];
   $item->check($_POST["itemID"], READ);

   if (isset($_SESSION["plugin_pdf"][$type])) {
      unset($_SESSION["plugin_pdf"][$type]);
   }

   $tab = array();
   if (isset($_POST['item'])) {
      foreach ($_POST['item'] as $key => $val) {
         $tab[] = $_SESSION["plugin_pdf"][$type][] = $key;
      }
   }

   if (isset($PLUGIN_HOOKS['plugin_pdf'][$type])
       && class_exists($PLUGIN_HOOKS['plugin_pdf'][$type])) {

      $itempdf = new $PLUGIN_HOOKS['plugin_pdf'][$type]($item);
      $itempdf->generatePDF(array($_POST["itemID"]), $tab, (isset($_POST["page"]) ? $_POST["page"] : 0));
   } else {
      die("Missing hook");
   }
} else {
   die("Missing context");
}