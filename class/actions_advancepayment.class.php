<?php

require_once DOL_DOCUMENT_ROOT.'/custom/advancepayment/class/advancepaymentlink.class.php';

class ActionsAdvancePayment {
	function doActions($paramters, &$object, &$action, &$hookmanager)
	{
		global $langs, $db, $conf, $user;

		$langs->load("advancepayment@advancepayment");

		if ($action == 'create') {
			$type = GETPOST('type_advancelink', 'alpha');
			$rowid = GETPOST('rowid_advancelink', 'int');

			if (!empty($type) && !empty($rowid)) {
				print '<input class="advancepayment" type="hidden" name="type_advancelink" value="'.$type.'">';
				print '<input class="advancepayment" type="hidden" name="rowid_advancelink" value="'.$rowid.'">';
			}
			return 0;
		}

		if ($action == 'add') {
			if (isModEnabled('accounting') && getDolGlobalString('ACCOUNTANCY_COMBO_FOR_AUX')) {
				$subledger_account = GETPOST("subledger_account", "alpha") > 0 ? GETPOST("subledger_account", "alpha") : '';
			} else {
				$subledger_account = GETPOST("subledger_account", "alpha");
			}

			$error = 0;

			$datep = dol_mktime(12, 0, 0, GETPOSTINT("datepmonth"), GETPOSTINT("datepday"), GETPOSTINT("datepyear"));
			$datev = dol_mktime(12, 0, 0, GETPOSTINT("datevmonth"), GETPOSTINT("datevday"), GETPOSTINT("datevyear"));
			if (empty($datev)) {
				$datev = $datep;
			}

			$object->ref = ''; // TODO
			$object->accountid = GETPOSTINT("accountid") > 0 ? GETPOSTINT("accountid") : 0;
			$object->datev = $datev;
			$object->datep = $datep;
			$object->amount = GETPOSTFLOAT("amount");
			$object->label = GETPOST("label", 'restricthtml');
			$object->note = GETPOST("note", 'restricthtml');
			$object->type_payment = dol_getIdFromCode($db, GETPOST('paymenttype'), 'c_paiement', 'code', 'id', 1);
			$object->num_payment = GETPOST("num_payment", 'alpha');
			$object->chqemetteur = GETPOST("chqemetteur", 'alpha');
			$object->chqbank = GETPOST("chqbank", 'alpha');
			$object->fk_user_author = $user->id;
			$object->category_transaction = GETPOSTINT("category_transaction");

			$object->accountancy_code = GETPOST("accountancy_code") > 0 ? GETPOST("accountancy_code", "alpha") : "";
			$object->subledger_account = $subledger_account;

			$object->sens = GETPOSTINT('sens');
			$object->fk_project = GETPOSTINT('fk_project');

			if (empty($datep) || empty($datev)) {
				$langs->load('errors');
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
				$error++;
			}
			if (empty($object->amount)) {
				$langs->load('errors');
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Amount")), null, 'errors');
				$error++;
			}
			if (isModEnabled("bank") && !$object->accountid > 0) {
				$langs->load('errors');
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("BankAccount")), null, 'errors');
				$error++;
			}
			if (empty($object->type_payment) || $object->type_payment < 0) {
				$langs->load('errors');
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("PaymentMode")), null, 'errors');
				$error++;
			}
			if (isModEnabled('accounting') && !$object->accountancy_code) {
				$langs->load('errors');
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("AccountAccounting")), null, 'errors');
				$error++;
			}
			if ($object->sens < 0) {
				$langs->load('errors');
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Sens")), null, 'errors');
				$error++;
			}

			if (!$error) {
				$db->begin();

				$ret = $object->create($user);
				if ($ret > 0) {
					$db->commit();

					$backtopage = GETPOST('backtopage', 'alpha');
					$type = GETPOST('type_advancelink', 'alpha');
					$rowid = GETPOST('rowid_advancelink', 'int');

					if (!empty($type) && !empty($rowid)) {
						$object->fetch($ret);
						$id = $object->fk_bank;

						$o = new Advancepaymentlink($db);

						$o->type_link = $type;
						$o->element_rowid = $rowid;
						$o->payment_rowid = $id;

						$res = $o->create($user);
						if (!$res) {
							setEventMessages($o->errors, $o->errors_detail, 'errors');
							return -1;
						}

						if ($type == 'commande') $backtopage = DOL_URL_ROOT.'/commande/card.php?id='.$rowid;
						if ($type == 'propal') $backtopage = DOL_URL_ROOT.'/comm/propal/card.php?id='.$rowid;
					}

					$urltogo = ($backtopage ? $backtopage : DOL_URL_ROOT.'/compta/bank/various_payment/list.php');
					header("Location: ".$urltogo);
					exit;
				} else {
					$db->rollback();
					setEventMessages($object->error, $object->errors, 'errors');
					$action = "create";
				}
			}

			$action = 'create';
		}

		return 0;
	}
}
