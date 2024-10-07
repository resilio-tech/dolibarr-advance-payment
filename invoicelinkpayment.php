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

global $conf,$user,$langs,$db,$mysoc;

require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/advancepayment/class/advancepaymentlink.class.php';

// Load translation files required by the page
$langs->loadLangs(array("advancepayment@advancepayment"));


$invoice_id = GETPOSTINT('id');

$form = new Form($db);
$object = new AdvancePaymentLinks($db);
$invoice = new Facture($db);
$invoice->fetch($invoice_id);
$invoice->fetch_thirdparty();

$action = GETPOST('action', 'alpha');

if ($action == 'link') {
	$rowid = GETPOSTINT('rowid');

	// fetch Bank object
	$bank = new AccountLine($db);
	$bank->fetch($rowid);

	// Get payment Type
	$paymentTypeSql = "SELECT id FROM ".MAIN_DB_PREFIX."c_paiement WHERE code = '".$bank->fk_type."'";
	$paymentType = $db->query($paymentTypeSql);
	if ($paymentType === false) {
		dol_print_error($db);
		exit;
	}
	$paymentType = $db->fetch_object($paymentType);

	$payment = new Paiement($db);

	// Create payment
	$payment->date = $bank->datev;
	$payment->datepaye = $bank->datev;
	$payment->amount = $bank->amount;
	$payment->multicurrency_amounts = array($invoice_id => $bank->amount);
	$payment->multicurrency_codes = array($invoice_id => $bank->multicurrency_code);
	$payment->facid = $invoice_id;
	$payment->socid = $invoice->socid;
	$payment->fk_account = $bank->fk_account;
	$payment->paiementid = $paymentType->id;

	$result = $payment->create($user);
	if ($result < 0) {
		setEventMessages($payment->error, $payment->errors, 'errors');
		var_dump($payment->error);
	}
	$payment->update_fk_bank($bank->id);

	$object->removePaymentLinks($rowid);

	header('Location: /compta/facture/card.php?id='.$invoice_id);
	exit;
}

llxHeader('', $langs->trans("Factures"));

$head = facture_prepare_head($invoice);
print dol_get_fiche_head($head, 'compta', $langs->trans('InvoiceCustomer'), -1, 'bill');

$linkback = '<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';
$morehtmlref = '<div class="refidno">';
// Ref invoice
if ($invoice->status == $invoice::STATUS_DRAFT && !$mysoc->isInEEC() && getDolGlobalString('INVOICE_ALLOW_FREE_REF')) {
	$morehtmlref .= $form->editfieldkey("Ref", 'ref', $invoice->ref, $invoice, false, 'string', '', 0, 1);
	$morehtmlref .= $form->editfieldval("Ref", 'ref', $invoice->ref, $invoice, false, 'string', '', null, null, '', 1);
	$morehtmlref .= '<br>';
}
// Ref customer
$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_client', $invoice->ref_customer, $invoice, false, 'string', '', 0, 1);
$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_client', $invoice->ref_customer, $invoice, false, 'string'.(getDolGlobalString('THIRDPARTY_REF_INPUT_SIZE') ? ':' . getDolGlobalString('THIRDPARTY_REF_INPUT_SIZE') : ''), '', null, null, '', 1);
// Thirdparty
$morehtmlref .= '<br>'.$invoice->thirdparty->getNomUrl(1, 'customer');
if (!getDolGlobalString('MAIN_DISABLE_OTHER_LINK') && $invoice->thirdparty->id > 0) {
	$morehtmlref .= ' (<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?socid='.$invoice->thirdparty->id.'&search_societe='.urlencode($invoice->thirdparty->name).'">'.$langs->trans("OtherBills").'</a>)';
}
// Project
if (isModEnabled('project')) {
	$langs->load("projects");
	$morehtmlref .= '<br>';
	if (!empty($invoice->fk_project)) {
		$proj = new Project($db);
		$proj->fetch($invoice->fk_project);
		$morehtmlref .= $proj->getNomUrl(1);
		if ($proj->title) {
			$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
		}
	}
}
$morehtmlref .= '</div>';
dol_banner_tab($invoice, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '', 0, '', '');

$soc_id = $invoice->socid;

$links = [0];
$other_links = $object->getPaymentLinks("soc", $soc_id);
$links = array_merge($links, $other_links);

$sql = "SELECT p.rowid, p.datev, p.amount, p.label FROM ".MAIN_DB_PREFIX."bank AS p WHERE p.rowid IN (".implode(',', $links).")";
$results = $db->query($sql);
if ($results === false) {
	dol_print_error($db);
	exit;
}


print '<table class=" tagtable nobottomiftotal liste">';

print '<tr>';
print '<th class="left">'.$langs->trans("Label").'</th>';
print '<th class="left">'.$langs->trans("Date").'</th>';
print '<th class="left">'.$langs->trans("Amount").'</th>';
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
		print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$invoice_id.'" method="post">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="link">';
		print '<input type="hidden" name="rowid" value="'.$obj->rowid.'">';
		print '<button type="submit" class="butAction">'.$langs->trans("Use").'</button>';
		print '</form>';
		print '</td>';
		print '</tr>';
	}
}

print '</table>';

// End of page
llxFooter();
$db->close();
