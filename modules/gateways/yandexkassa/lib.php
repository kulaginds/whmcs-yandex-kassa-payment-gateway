<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once __DIR__ . '/../../../init.php';

use Illuminate\Database\Capsule\Manager as Capsule;

define('YANDEXKASSA_MODULE_NAME', 'yandexkassa');
define('YANDEXKASSA_HOOKS_FILE', __DIR__ . '/hooks.php');

// возвращает элементы счета
function yandexkassa_getInvoiceItems($invoiceID) {
    $result = Capsule::table('tblinvoiceitems')
        ->select('amount', 'description')
        ->where('invoiceid', '=', $invoiceID)
        ->get();

    $result = json_decode(json_encode($result), true);
    $invoiceitems = array();

    foreach ($result as $data) {
        $invoiceitems[] = array(
            "quantity" => 1,
            "price" => array("amount" => $data['amount']),
            "tax" => 1,
            "text" => $data['description'],
            "paymentSubjectType" => "service",
            "paymentMethodType" => "full_payment",
        );
    }

    return $invoiceitems;
}

// возвращает строку рецепта для формы оплаты
function yandexkassa_getMerchantReceipt($clientEmail, $invoiceID) {
    $invoiceItems = yandexkassa_getInvoiceItems($invoiceID);

    $ym_merchant_receipt = array(
        "customerContact" => $clientEmail,
        "taxSystem"       => 3,
        "items"           => $invoiceItems,
    );

    $ym_merchant_receipt = preg_replace_callback('/\\\\u(\w{4})/', function ($matches) {
        return html_entity_decode('&#x' . $matches[1] . ';', ENT_COMPAT, 'UTF-8');
    }, json_encode($ym_merchant_receipt));

    return str_replace('\n', ' ', $ym_merchant_receipt);
}

// валидирует запрос от Яндекс.Кассы
function yandexkassa_check($input) {
    require_once __DIR__ . '/../../../init.php';
    require_once __DIR__ . '/../../../includes/gatewayfunctions.php';

    $params = getGatewayVariables(YANDEXKASSA_MODULE_NAME);
    $shopId = $params['ShopId'];
    $shopPassword = $params['shopPassword'];

    $hash = $input['orderIsPaid'] . ";" . $input['orderSumAmount'] . ";" . $input['orderSumCurrencyPaycash']
            . ";" . $input['orderSumBankPaycash'] . ";" . $input['shopId'] . ";" . $input['invoiceId']
            . ";" . $input['customerNumber'] . ";" . $shopPassword;
    $hash = strtoupper(md5($hash));

    return $hash == $input['md5'] && $shopId == $input['shopId'];
}

// переводит транзакцию в оплаченный статус
function yandexkassa_addInvoicePayment($invoiceId, $payerCode, $yandexKassaInvoiceId, $amount) {
    require_once __DIR__ . '/../../../init.php';
    require_once __DIR__ . '/../../../includes/invoicefunctions.php';

    $transactionId = "кошелек: " . $payerCode . " транз.ID: " . $yandexKassaInvoiceId;
    $fees = 0;

    return addInvoicePayment($invoiceId, $transactionId, $amount, $fees, YANDEXKASSA_MODULE_NAME);
}

// логирует данные транзакции
function yandexkassa_logTransaction($input, $transactionStatus) {
    require_once __DIR__ . '/../../../init.php';
    require_once __DIR__ . '/../../../includes/gatewayfunctions.php';

    logTransaction(YANDEXKASSA_MODULE_NAME, $input, $transactionStatus);
}

// хук при успешном переводе транзакции в статус "оплачено"
function yandexkassa_sendSuccessEmail($invoiceId, $amount, $payerCode) {
    if (!is_file(YANDEXKASSA_HOOKS_FILE)) {
        return;
    }

    require_once YANDEXKASSA_HOOKS_FILE;

    if (function_exists('yandexkassa_hook_sendSuccessEmail')) {
        yandexkassa_hook_sendSuccessEmail($invoiceId, $amount, $payerCode);
    }
}

// хук при неуспешном переводе транзакции в статус "оплачено"
function yandexkassa_sendError($invoiceId, $amount, $payerCode, $customData) {
    if (!is_file(YANDEXKASSA_HOOKS_FILE)) {
        return;
    }

    require_once YANDEXKASSA_HOOKS_FILE;

    if (function_exists('yandexkassa_hook_sendError')) {
        yandexkassa_hook_sendError($invoiceId, $amount, $payerCode, $customData);
    }
}
