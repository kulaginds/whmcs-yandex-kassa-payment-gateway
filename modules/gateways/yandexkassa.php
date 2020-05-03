<?php
/**
 * Yandex.Kassa платежный шлюз.
 *
 * @see https://github.com/kulaginds/whmcs-yandex-kassa-payment-gateway
 *
 * @license GNU General Public License v3.0
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once __DIR__ . '/yandexkassa/lib.php';

/**
 * Устанавливает метаданные модуля.
 *
 * Значения, определенные здесь, используются для определения соответствующих
 * возможностей и настроек модуля.
 *
 * @see https://developers.whmcs.com/payment-gateways/meta-data-params/
 *
 * @return array
 */
function yandexkassa_MetaData()
{
    return array(
        'DisplayName' => 'Yandex.Kassa',
        'APIVersion' => '1.1', // Use API Version 1.1
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage' => false,
    );
}

/**
 * Yandex.Kassa параметры конфигурации модуля.
 *
 * @return array
 */
function yandexkassa_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Yandex.Kassa',
        ),
        'act' => array(
            'FriendlyName' => 'path merchant',
            'Type' => 'text',
            'Size' => '20',
            'Default' => 'https://money.yandex.ru/eshop.xml',
            'Description' => 'Path to Yandex.Kassa merchant',
        ),
        'ShopId' => array(
            'FriendlyName' => 'Shop ID',
            'Type' => 'text',
            'Size' => '20',
            'Default' => '',
            'Description' => 'Identificator of shop in Yandex.Kassa',
        ),
        'scid' => array(
            'FriendlyName' => 'scid',
            'Type' => 'text',
            'Size' => '20',
            'Default' => '',
            'Description' => 'Identificator of payment form in Yandex.Kassa',
        ),
        'shopPassword' => array(
            'FriendlyName' => 'Shop password',
            'Type' => 'password',
            'Size' => '40',
            'Default' => '',
            'Description' => 'Password of shop in Yandex.Kassa',
        ),
    );
}

/**
 * Ссылка на форму оплаты.
 *
 * Готовит HTML-код вместе с ссылкой на форму оплаты.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see https://developers.whmcs.com/payment-gateways/third-party-gateway/
 *
 * @return string
 */
function yandexkassa_link($params)
{
    // Gateway Specific Variables
    $act = $params['act'];
    $ShopId = $params['ShopId'];
    $scid = $params['scid'];

    // Invoice Variables
    $invoiceid = $params['invoiceid'];
    $amount = $params['amount'];
    $currency = $params['currency'];
    $clientemail = $params['clientdetails']['email'];

    $ym_merchant_receipt = yandexkassa_getMerchantReceipt($clientemail, $invoiceid);
    
    return '<form name=pay method="POST" action="' . $act . '" onSubmit="return check_form();">
    сумма:&nbsp;<select name=Sum><OPTION>'.sprintf("%1.2f",$amount).'</OPTION></SELECT> '.$currency.'
        <input type="hidden" name="CustomerNumber" value="' . $invoiceid . '">
    <input type="hidden" name="scid" value="' . $scid . '">
    <input type="hidden" name="ShopId" value="' . $ShopId . '">
    <input type="hidden" name="ym_merchant_receipt" value=\'' . $ym_merchant_receipt . '\'>
    <br><input type="submit" value="оплатить сейчас">
    </form>';
}
