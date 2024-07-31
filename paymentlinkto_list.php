<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       advancepayment/paymentlinkto_list.php
 *	\ingroup    advancepayment
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/advancepayment/class/advancepaymentlink.class.php';

// Load translation files required by the page
$langs->loadLangs(array("advancepayment@advancepayment"));

$rowid = GETPOSTINT('rowid');
$type = GETPOST('type', 'alpha');

$object = new AdvancePaymentLinks($db);

$links = [0];

if ($type != 'commande' && $type != 'propal' && $type != 'soc') {
	$type = 'commande';
}
$other_links = $object->getPaymentLinks($type, $rowid);
$links = array_merge($links, $other_links);

$sql = "SELECT p.rowid, p.datev, p.amount, p.label FROM ".MAIN_DB_PREFIX."bank AS p WHERE p.rowid IN (".implode(',', $links).")";
$results = $db->query($sql);
if ($results === false) {
	dol_print_error($db);
	exit;
}

print '<table class="centpercent notopnoleftnoright table-fiche-title">';
print '<tr class="titre">';
print '<td class="nobordernopadding valignmiddle col-title">';
print '<div class="titre inline-block">';
print $langs->trans("PaymentLinkTo");
print '</div>';
print '</td>';
print '</tr>';
print '</table>';

print '<div class="div-table-responsive-no-min">';

print '<table class="centpercent noborder">';
print '<tr class="liste_titre">';
print '<th class="wrapcolumntitle liste_titre" title="Label">Libellé</th>';
print '<th class="wrapcolumntitle center liste_titre_sel" title="Date">Date</th>';
print '<th class="wrapcolumntitle liste_titre" title="Amount">Montant</th>';
print '<th></th>';
print '</tr>';

$n = $db->num_rows($results);
if ($n == 0) {
	print '<tr class="oddeven"><td colspan="6"><span class="opacitymedium">Aucun</span></td></tr>';
} else {
	while ($n > 0) {
		$obj = $db->fetch_object($results);
		$n--;

		print '<tr class="oddeven">';
		print '<td>';
		print '<a href="'.dol_buildpath('/compta/bank/line.php', 1).'?rowid='.$obj->rowid.'">'.$obj->label.'</a>';
		print '</td>';
		print '<td class="nowrap">'.dol_print_date($db->jdate($obj->datev), 'day').'</td>';
		print '<td class="nowrap">'.price($obj->amount).'</td>';
		print '<td>';
		print '<form action="/custom/advancepayment/paymentlinkto.php?link='.$rowid.'" method="post">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="unlink">';
		print '<input type="hidden" name="type" value="'.$type.'">';
		print '<input type="hidden" name="rowid" value="'.$obj->rowid.'">';
		print '<button type="submit" class="butAction">'.$langs->trans("UnLinkPayment").'</button>';
		print '</form>';
		print '</td>';
	}
}

print '</table>';

print '</div>';
$db->close();
