<?php
/* Copyright (C) 2024 SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * Library javascript to enable Browser notifications
 */

if (!defined('NOREQUIREUSER')) {
	define('NOREQUIREUSER', '1');
}
if (!defined('NOREQUIREDB')) {
	define('NOREQUIREDB', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', 1);
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOLOGIN')) {
	define('NOLOGIN', 1);
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}


/**
 * \file    advancepayment/js/advancepayment.js.php
 * \ingroup advancepayment
 * \brief   JavaScript file for module Advancepayment.
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
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/../main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/../main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

// Define js type
header('Content-Type: application/javascript');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) {
	header('Cache-Control: max-age=3600, public, must-revalidate');
} else {
	header('Cache-Control: no-cache');
}

global $langs;

$langs->load("advancepayment@advancepayment");

$payment_link_trad = $langs->trans("AdvanceLink");
$payment_link_trad = $langs->trans("AdvanceLink");
$invoice_link_payment = $langs->trans("InvoiceLinkAdvance");
$create_payment = $langs->trans("ReceiveAdvance");
$advance_name = $langs->trans("AdvancepaymentDescription");
?>

/* Javascript library of module Advancepayment */

$(document).ready(function () {
	// If we aren't on the payment page, don't do anything
	if (window.location.href.indexOf("/compta/bank/line.php") === -1) {
		return;
	}

	// If we are on the payment page, add a button to the page
	$("form[name='update'] > div.center").append(
		"<input type='button' value='<?= $payment_link_trad ?>' id='payment_link' class='button'>"
	);

	// When the button is clicked, go to page
	$("#payment_link").click(function () {
		// Get the rowid from the URL
		var rowid = window.location.href.split("rowid=")[1].split("&")[0];
		// Redirect to the payment link page
		window.location.href = "/custom/advancepayment/paymentlinkto.php?rowid=" + rowid;
	});
});

$(document).ready(function () {
	// If we aren't on the order page or proposal page, don't do anything
	if (window.location.href.indexOf("/compta/bank/various_payment/card.php?action=create") === -1) {
		return;
	}

	$('input.advancepayment').appendTo('form');
});

$(document).ready(function () {
	// If we aren't on the order page or proposal page, don't do anything
	if (window.location.href.indexOf("/commande/card.php") === -1 && window.location.href.indexOf("/propal/card.php") === -1 || window.location.href.indexOf("/fourn") > -1) {
		return;
	}
	const id = window.location.href.split("id=")[1].split("&")[0];

	let type = 'commande';
	if (window.location.href.indexOf("propal/card.php") !== -1) {
		type = 'propal';
	}

	const refid = $('.refid').clone().children().remove().end().text();
	const soc = $('.refurl').clone().children().remove().end().text();

	$('.tabsAction').prepend(
		`<form style="display: inline-block" action="/compta/bank/various_payment/card.php?action=create" method="post">
			<input type="hidden" name="token" value="<?= newToken() ?>">
			<input type="hidden" name="label" value="<?= $advance_name ?> - ${refid} ${soc}">
			<input type="hidden" name="sens" value="1">
			<input type="hidden" name="type_advancelink" value="${type}">
			<input type="hidden" name="rowid_advancelink" value="${id}">
			<input class="butAction" type="submit" value="<?= $create_payment ?>" id="payment_create" class="butAction">
		</form>`
	);

	$.get(`/custom/advancepayment/paymentlinkto_list.php?type=${type}&rowid=${id}`, function (data) {
		const last = $(".fichecenter .fichehalfright").last();
		last.append(data);
	});
});

$(document).ready(function () {
	// if we aren't on the Tier page, don't do anything
	if (window.location.href.indexOf("/comm/card.php") === -1) {
		return;
	}
	const id = window.location.href.split("socid=")[1].split("&")[0];

	$.get(`/custom/advancepayment/paymentlinkto_list.php?type=soc&rowid=${id}`, function (data) {
		const last = $(".fichecenter .fichehalfright").last();
		last.append(data);
	});
});

$(document).ready(function () {
	// if we aren't on the Tier page, don't do anything
	if (window.location.href.indexOf("/compta/facture/card.php") === -1) {
		return;
	}
	const id = window.location.href.split("id=")[1].split("&")[0];

	const last = $(".fichecenter .fichehalfright").first();
	last.append(
		"<input type='button' value='<?= $invoice_link_payment ?>' id='payment_link' class='button'>"
	);

	$("#payment_link").click(function () {
		window.location.href = "/custom/advancepayment/invoicelinkpayment.php?id=" + id;
	});
});
