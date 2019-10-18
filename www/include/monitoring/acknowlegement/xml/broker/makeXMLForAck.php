<?php
/*
 * Copyright 2005-2015 Centreon
 * Centreon is developped by : Julien Mathis and Romain Le Merlus under
 * GPL Licence 2.0.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation ; either version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, see <http://www.gnu.org/licenses>.
 *
 * Linking this program statically or dynamically with other modules is making a
 * combined work based on this program. Thus, the terms and conditions of the GNU
 * General Public License cover the whole combination.
 *
 * As a special exception, the copyright holders of this program give Centreon
 * permission to link this program with independent modules to produce an executable,
 * regardless of the license terms of these independent modules, and to copy and
 * distribute the resulting executable under terms of Centreon choice, provided that
 * Centreon also meet, for each linked independent module, the terms  and conditions
 * of the license of that module. An independent module is a module which is not
 * derived from this program. If you modify this program, you may extend this
 * exception to your version of the program, but you are not obliged to do so. If you
 * do not wish to do so, delete this exception statement from your version.
 *
 * For more information : contact@centreon.com
 *
 */

require_once realpath(dirname(__FILE__) . "/../../../../../../config/centreon.config.php");
include_once _CENTREON_PATH_ . "www/class/centreonDuration.class.php";
include_once _CENTREON_PATH_ . "www/class/centreonGMT.class.php";
include_once _CENTREON_PATH_ . "www/class/centreonXML.class.php";
include_once _CENTREON_PATH_ . "www/class/centreonDB.class.php";
include_once _CENTREON_PATH_ . "www/class/centreonSession.class.php";
include_once _CENTREON_PATH_ . "www/class/centreon.class.php";
include_once _CENTREON_PATH_ . "www/class/centreonLang.class.php";
include_once _CENTREON_PATH_ . "www/include/common/common-Func.php";

session_start();
$oreon = $_SESSION['centreon'];

$db = new CentreonDB();
$pearDB = $db;
$dbb = new CentreonDB("centstorage");

$centreonlang = new CentreonLang(_CENTREON_PATH_, $oreon);
$centreonlang->bindLang();
$sid = session_id();
if (isset($sid)) {
    //$sid = $_GET["sid"];
    $res = $db->query("SELECT * FROM session WHERE session_id = '" . CentreonDB::escape($sid) . "'");
    if (!$session = $res->fetchRow()) {
        get_error('bad session id');
    }
} else {
    get_error('need session id !');
}

(isset($_GET["hid"])) ? $host_id = CentreonDB::escape($_GET["hid"]) : $host_id = 0;
(isset($_GET["svc_id"])) ? $service_id = CentreonDB::escape($_GET["svc_id"]) : $service_id = 0;

/*
 * Init GMT class
 */
$centreonGMT = new CentreonGMT($pearDB);
$centreonGMT->getMyGMTFromSession($sid, $pearDB);

/**
 * Start Buffer
 */
$xml = new CentreonXML();
$xml->startElement("response");

$xml->startElement("label");
$xml->writeElement('author', _('Author'));
$xml->writeElement('entrytime', _('Entry Time'));
$xml->writeElement('persistent', _('Persistent'));
$xml->writeElement('sticky', _('Sticky'));
$xml->writeElement('comment', _('Comment'));
$xml->endElement();

/**
 * Retrieve info
 */
if (!$service_id) {
    $query = "SELECT author, entry_time, comment_data, persistent_comment, sticky
    		  FROM acknowledgements
    		  WHERE host_id = " . CentreonDB::escape($host_id) . "
    		  AND service_id IS NULL
    		  ORDER BY entry_time DESC
    		  LIMIT 1";
} else {
    $query = "SELECT author, entry_time, comment_data, persistent_comment, sticky
    		  FROM acknowledgements
    		  WHERE host_id = " . CentreonDB::escape($host_id) . "
    		  AND service_id = " . CentreonDB::escape($service_id) . "
    		  ORDER BY entry_time DESC
    		  LIMIT 1";
}
$res = $dbb->query($query);
$rowClass = "list_one";
if (isset($res)) {
    while ($row = $res->fetchRow()) {
        $row['comment_data'] = strip_tags($row['comment_data']);
        $xml->startElement('ack');
        $xml->writeAttribute('class', $rowClass);
        $xml->writeElement('author', $row['author']);
        $xml->writeElement('entrytime', $row['entry_time']);
        $xml->writeElement('comment', $row['comment_data']);
        $xml->writeElement('persistent', $row['persistent_comment'] ? _('Yes') : _('No'));
        $xml->writeElement('sticky', $row['sticky'] ? _('Yes') : _('No'));
        $xml->endElement();
        $rowClass == "list_one" ? $rowClass = "list_two" : $rowClass = "list_one";
    }
}

/*
 * End buffer
 */
$xml->endElement();
header('Content-type: text/xml; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

/*
 * Print Buffer
 */
$xml->output();
