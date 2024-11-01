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

$object = new AdvancePaymentLinks($db);

$sql_type = "SELECT b.rowid as rowid, b.datev as datev, b.amount as amount, b.label as label,";
$sql_type .= " c.rowid as command_rowid, c.ref as command_label,";
$sql_type .= " p.rowid as propal_rowid, p.ref as propal_label,";
$sql_type .= " f.rowid as facture_rowid, f.ref as facture_label";
$sql_type .= " FROM " . MAIN_DB_PREFIX . "bank AS b";
$sql_type .= " INNER JOIN ".MAIN_DB_PREFIX."advancepayment_advancepaymentlink AS a ON b.rowid = a.payment_rowid";
$sql_type .= " LEFT JOIN ".MAIN_DB_PREFIX."commande AS c ON c.rowid = a.element_rowid AND a.type_link = 'commande'";
$sql_type .= " LEFT JOIN ".MAIN_DB_PREFIX."propal AS p ON p.rowid = a.element_rowid AND a.type_link = 'propal'";
$sql_type .= " LEFT JOIN ".MAIN_DB_PREFIX."paiement AS pai ON pai.fk_bank = a.payment_rowid";
$sql_type .= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture AS pf ON pf.fk_paiement = pai.rowid";
$sql_type .= " LEFT JOIN ".MAIN_DB_PREFIX."facture AS f ON f.rowid = pf.fk_facture";
$sql_type .= " WHERE (p.fk_projet = " . $rowid . " AND a.used = 0) OR (c.fk_projet = " . $rowid . " AND a.used = 0) OR (f.fk_projet = " . $rowid . " AND a.used = 1)";

$results_type = $db->query($sql_type);
if ($results_type === false) {
	dol_print_error($db);
	exit;
}

print '<table class="centpercent notopnoleftnoright table-fiche-title">';
print '<tr class="titre">';
print '<td class="nobordernopadding valignmiddle col-title">';
print '<div class="titre inline-block">';
print $langs->trans("AdvanceLinkTo");
print '</div>';
print '</td>';
print '</tr>';
print '</table>';

print '<div class="div-table-responsive-no-min">';

print '<table class="centpercent noborder">';
print '<tr class="liste_titre">';
print '<th class="wrapcolumntitle liste_titre" title="Label">Libellé</th>';
print '<th class="wrapcolumntitle liste_titre" title="Label">Lié à</th>';
print '<th class="wrapcolumntitle liste_titre_sel" title="Date">Date</th>';
print '<th class="wrapcolumntitle liste_titre" title="Amount">Montant</th>';
print '<th></th>';
print '</tr>';

$n = $db->num_rows($results_type);
if ($n == 0) {
	print '<tr class="oddeven"><td colspan="6"><span class="opacitymedium">Aucun</span></td></tr>';
} else {
	while ($n > 0) {
		$obj = $db->fetch_object($results_type);
		$n--;

		print '<tr class="oddeven">';
		print '<td>';
		print '<a href="'.dol_buildpath('/compta/bank/line.php', 1).'?rowid='.$obj->rowid.'">'.$obj->label.'</a>';
		print '</td>';
		print '<td>';
		print '<ul>';
		if (!empty($obj->command_label)) {
			print '<li><a href="'.dol_buildpath('/commande/card.php', 1).'?id='.$obj->command_rowid.'">'.$obj->command_label.'</a></li>';
		}
		if (!empty($obj->propal_label)) {
			print '<li><a href="'.dol_buildpath('/comm/propal/card.php', 1).'?id='.$obj->propal_rowid.'">'.$obj->propal_label.'</a></li>';
		}
		$sql = "SELECT f.ref as label, f.rowid as facture_rowid FROM ".MAIN_DB_PREFIX."facture AS f";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."paiement_facture AS pf ON pf.fk_facture = f.rowid";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."paiement AS pai ON pai.rowid = pf.fk_paiement";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."bank AS b ON b.rowid = pai.fk_bank";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."advancepayment_advancepaymentlink AS a ON b.rowid = a.payment_rowid";
		$sql .= " WHERE b.rowid = " . $obj->rowid . " AND a.used = 1";

		$results = $db->query($sql);
		if ($results === false) {
			dol_print_error($db);
			exit;
		}

		$n_facture = $db->num_rows($results);
		if ($n_facture > 0) {
			while ($n_facture > 0) {
				$obj_facture = $db->fetch_object($results);
				$n_facture--;
				print '<li><a href="'.dol_buildpath('/compta/facture/card.php', 1).'?id='.$obj_facture->facture_rowid.'">'.$obj_facture->label.'</a></li>';
			}
		}

		print '</ul>';
		print '</td>';
		print '<td class="nowrap">'.dol_print_date($db->jdate($obj->datev), 'day').'</td>';
		print '<td class="nowrap">'.price($obj->amount).'</td>';
		print '<td>';
		print '</td>';
	}
}

print '</table>';

print '</div>';
$db->close();
