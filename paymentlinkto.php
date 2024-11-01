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
 *	\file       advancepayment/paymentlinkto.php
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

global $user;

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
$id = GETPOSTINT('link');
$action = GETPOST('action', 'alpha');

if ($action == 'unlink' && !empty($type) && !empty($id) && ($type == 'commande' || $type == 'propal' || $type == 'soc')) {
	$paymentlinks = new AdvancePaymentLinks($db);
	$paymentlinks->usePaymentLinkFrom($type, $id, $rowid);

	// Redirect to the relative linked page
	if ($type == 'commande') {
		header('Location: '.DOL_URL_ROOT.'/commande/card.php?id='.$id);
		exit;
	} else if ($type == 'propal') {
		header('Location: '.DOL_URL_ROOT.'/comm/propal/card.php?id='.$id);
		exit;
	} else if ($type == 'soc') {
		header('Location: '.DOL_URL_ROOT.'/societe/card.php?id='.$id);
		exit;
	}
}

if ($action == 'link' && !empty($type) && !empty($id) && ($type == 'commande' || $type == 'propal')) {
	$paymentlink = new AdvancePaymentLink($db);
	$paymentlink->type_link = $type;
	$paymentlink->payment_rowid = $rowid;
	$paymentlink->element_rowid = $id;

	$result = $paymentlink->create($user);
	if ($result < 0) {
		setEventMessages($paymentlink->error, $paymentlink->errors, 'errors');
	}

	// Redirect to the relative linked page
	if ($type == 'commande') {
		header('Location: '.DOL_URL_ROOT.'/commande/card.php?id='.$id);
		exit;
	} else if ($type == 'propal') {
		header('Location: '.DOL_URL_ROOT.'/comm/propal/card.php?id='.$id);
		exit;
	}
}

$filter_soc = GETPOST('filtersoc');
$filter_type = GETPOST('filtertype');
$filter_ref = GETPOST('filterref');
$filter_date = GETPOST('filterdate');

$form = new Form($db);

llxHeader('', $langs->trans("BankTransaction"));

$head = bankline_prepare_head($rowid);

$sql = "SELECT b.rowid, b.dateo as do, b.datev as dv, b.amount, b.label, b.rappro,";
$sql .= " b.num_releve, b.fk_user_author, b.num_chq, b.fk_type, b.fk_account, b.fk_bordereau as receiptid,";
$sql .= " b.emetteur,b.banque";
$sql .= " FROM ".MAIN_DB_PREFIX."bank as b";
$sql .= " WHERE rowid=".((int) $rowid);
$sql .= " ORDER BY dateo ASC";

$result = $db->query($sql);

if ($result && $db->num_rows($result)) {
	$objp = $db->fetch_object($result);

	$acct = new Account($db);
	$acct->fetch($objp->fk_account);

	$bankline = new AccountLine($db);
	$bankline->fetch($objp->rowid);

	$links = $acct->get_url($rowid);
	$bankline->load_previous_next_ref('', 'rowid');

	print dol_get_fiche_head($head, 'bankline', $langs->trans('LineRecord'), 0, 'accountline', 0);

	$linkback = '<a href="'.DOL_URL_ROOT.'/compta/bank/bankentries_list.php?restore_lastsearch_values=1'.(GETPOSTINT('account', 1) ? '&id='.GETPOSTINT('account', 1) : '').'">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($bankline, 'rowid', $linkback);

	print '<table>';

	print '<tr><td>'.$langs->trans("BankAccount").'</td>';
	print '<td>';
	print $acct->getNomUrl(1, 'transactions', 'reflabel');
	print '</td>';
	print '</tr>';

	print '<tr><td>'.$langs->trans("Label").'</td>';
	print '<td>';
	print $objp->label;
	print '</td>';
	print '</tr>';

	print '<tr><td>'.$langs->trans("Amount").'</td>';
	print '<td>';
	print price($objp->amount);
	print ' ';
	print $langs->trans("Currency".$acct->currency_code);
	print '</td>';
	print '</tr>';

	print '</table>';
	print '<br>';

	print '<table class=" tagtable nobottomiftotal liste">';

	print '<tr>';
	print '<th class="left">'.$langs->trans("Company").'</th>';
	print '<th class="left">'.$langs->trans("Type").'</th>';
	print '<th class="left">'.$langs->trans("Ref").'</th>';
	print '<th class="left">'.$langs->trans("Date").'</th>';
	print '<th></th>';
	print '</tr>';

	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')){
		$filter_soc = '';
		$filter_type = '';
		$filter_ref = '';
		$filter_date = '';
	}

	print '<tr>';

	print '<form name="update" method="POST" action="'.$_SERVER['PHP_SELF'].'?rowid='.$rowid.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="linkto">';

	print '<td><input type="text" name="filtersoc" value="'.$filter_soc.'" placeholder="'.$langs->trans('Filter').' '.$langs->trans('Company').'"></td>';
	print '<td><input type="text" name="filtertype" value="'.$filter_type.'" placeholder="'.$langs->trans('Filter').' '.$langs->trans('Type').'"></td>';
	print '<td><input type="text" name="filterref" value="'.$filter_ref.'" placeholder="'.$langs->trans('Filter').' '.$langs->trans('Ref').'"></td>';
	print '<td><input type="text" name="filterdate" value="'.$filter_date.'" placeholder="'.$langs->trans('Filter').' '.$langs->trans('Date').'"></td>';
	print '<td>';
	print $form->showFilterButtons('left');
	print '</td>';

	print '</form>';

	print '</tr>';

	$list_elements = array();

	$list_propals_sql = "SELECT p.rowid, p.ref, p.datec, s.nom FROM ".MAIN_DB_PREFIX."propal as p INNER JOIN ".MAIN_DB_PREFIX."societe as s ON p.fk_soc = s.rowid WHERE p.fk_statut != 0 ";
	$list_orders_sql = "SELECT o.rowid, o.ref, o.date_creation as datec, s.nom FROM ".MAIN_DB_PREFIX."commande as o INNER JOIN ".MAIN_DB_PREFIX."societe as s ON o.fk_soc = s.rowid WHERE o.fk_statut != 0 ";
	if (!empty($filter_soc)) {
		$list_propals_sql .= natural_search('s.nom', $filter_soc);
		$list_orders_sql .= natural_search('s.nom', $filter_soc);
	}
	if (!empty($filter_ref)) {
		$list_propals_sql .= natural_search('p.ref', $filter_ref);
		$list_orders_sql .= natural_search('o.ref', $filter_ref);
	}
	if (!empty($filter_date)) {
		$list_propals_sql .= natural_search('p.datec', $filter_date);
		$list_orders_sql .= natural_search('o.date_creation', $filter_date);
	}
	$list_propals_sql .= " ORDER BY p.datec DESC LIMIT 10 ";
	$list_orders_sql .= " ORDER BY o.date_creation DESC LIMIT 10 ";

	if (empty($filter_type) || str_contains($langs->trans('Proposal'), $filter_type)) {
		$results_propals = $db->query($list_propals_sql);
		$num_propals = $db->num_rows($results_propals);
		$n = 0;
		while ($n < $num_propals) {
			$result_propal = $db->fetch_object($results_propals);
			$list_elements[] = array(
				'soc' => $result_propal->nom,
				'type_name' => $langs->trans('Proposal'),
				'type' => 'propal',
				'id' => $result_propal->rowid,
				'ref' => $result_propal->ref,
				'date' => $result_propal->datec
			);
			$n++;
		}
	}

	if (empty($filter_type) || str_contains($langs->trans('Order'), $filter_type)) {
		$results_orders = $db->query($list_orders_sql);
		$num_orders = $db->num_rows($results_orders);
		$n = 0;
		while ($n < $num_orders) {
			$result_order = $db->fetch_object($results_orders);
			$list_elements[] = array(
				'soc' => $result_order->nom,
				'type_name' => $langs->trans('Order'),
				'type' => 'commande',
				'id' => $result_order->rowid,
				'ref' => $result_order->ref,
				'date' => $result_order->datec
			);
			$n++;
		}
	}

	array_multisort(array_column($list_elements, 'date'), SORT_DESC, $list_elements);

	foreach ($list_elements as $element) {
		print '<tr class="element">';
		print '<td>'.$element['soc'].'</td>';
		print '<td>'.$element['type_name'].'</td>';
		print '<td><a href="'.$_SERVER['PHP_SELF'].'?action=linktoupdate&rowid='.$rowid.'&ref='.$element['id'].'" target="_blank">'.img_picto($element['ref'], 'object').$element['ref'].'</a></td>';
		print '<td>'.dol_print_date($element['date'], 'day').'</td>';
		print '<td>';
		print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?rowid='.$rowid.'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="link">';
		print '<input type="hidden" name="type" value="'.$element['type'].'">';
		print '<input type="hidden" name="link" value="'.$element['id'].'">';
		print '<button type="submit" class="butAction">'.$langs->trans("Use").'</button>';
		print '</form>';
		print '</td>';
		print '</tr>';
	}

	print '</table>';

	print '<br>';
}

// End of page
llxFooter();
$db->close();
