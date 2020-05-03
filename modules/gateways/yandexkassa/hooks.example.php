<?php

function yandexkassa_hook_sendSuccessEmail($invoiceId, $amount, $payerCode) {
	// do something
}

function yandexkassa_hook_sendError($invoiceId, $amount, $payerCode, $customData) {
	// do something
}
