<?php

define('WHMCS', 1);

require_once __DIR__ . '/../yandexkassa/lib.php';

$shopId = $_POST['shopId'];
$yandexKassaInvoiceId = $_POST['invoiceId'];
$action = $_POST['action'];
$invoiceId = $_POST['customerNumber'];
$payerCode = $_POST['paymentPayerCode'];
$amount = $_POST['orderSumAmount'];
$transactionStatus = "Failure";

$code = '1'; // Несовпадение значения параметра md5 с результатом расчета хэш-функции. Окончательная ошибка.
if (yandexkassa_check($_POST)) {
    if (yandexkassa_addInvoicePayment($invoiceId, $payerCode, $yandexKassaInvoiceId, $amount)) {
        $code = '0'; // Магазин дал согласие и готов принять перевод.
        $transactionStatus = "Success";
        yandexkassa_sendSuccessEmail($invoiceId, $amount, $payerCode);
    } else {
        $code = '200'; // Магазин не в состоянии разобрать запрос. Окончательная ошибка.
        yandexkassa_sendError($invoiceId, $amount, $payerCode, json_encode($results));
    }
}

yandexkassa_logTransaction($_POST, $transactionStatus);

$now = date("c");

echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>
<response performedDatetime=\"$now\">
<result code=\"$code\" action=\"$action\" shopId=\"$shopId\" invoiceId=\"$yandexKassaInvoiceId\"/>
</response>";