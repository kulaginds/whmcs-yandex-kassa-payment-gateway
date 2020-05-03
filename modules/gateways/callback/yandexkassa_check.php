<?php

define('WHMCS', 1);

require_once __DIR__ . '/../yandexkassa/lib.php';

$action = $_POST['action'];
$invoiceId = $_POST['invoiceId'];

$code = '1'; // Несовпадение значения параметра md5 с результатом расчета хэш-функции. Окончательная ошибка.
if (yandexkassa_check($_POST)) {
    $code = '0'; // Магазин дал согласие и готов принять перевод.
}

$now = date("c");

print "<?xml version=\"1.0\" encoding=\"utf-8\"?>
<response performedDatetime=\"$now\">
<result code=\"$code\" action=\"$action\" shopId=\"$shopId\" invoiceId=\"$invoiceId\"/>
</response>";
