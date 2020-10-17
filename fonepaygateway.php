<?php

/**
 * WHMCS Sample Merchant Gateway Module
 *
 * This sample file demonstrates how a merchant gateway module supporting
 * 3D Secure Authentication, Captures and Refunds can be structured.
 *
 * If your merchant gateway does not support 3D Secure Authentication, you can
 * simply omit that function and the callback file from your own module.
 *
 * Within the module itself, all functions must be prefixed with the module
 * filename, followed by an underscore, and then the function name. For this
 * example file, the filename is "fonepaygateway" and therefore all functions
 * begin "fonepaygateway_".
 *
 * For more information, please refer to the online documentation.
 *
 * @see https://developers.whmcs.com/payment-gateways/
 *
 * @copyright Copyright (c) WHMCS Limited 2017
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Define module related meta data.
 *
 * Values returned here are used to determine module related capabilities and
 * settings.
 *
 * @see https://developers.whmcs.com/payment-gateways/meta-data-params/
 *
 * @return array
 */
function fonepaygateway_MetaData()
{
    return array(
        'DisplayName' => 'Fonepay Payment Gateway',
        'APIVersion' => '1.1', // Use API Version 1.1
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage' => false,
    );
}

/**
 * Define gateway configuration options.
 *
 * The fields you define here determine the configuration options that are
 * presented to administrator users when activating and configuring your
 * payment gateway module for use.
 *
 * Supported field types include:
 * * text
 * * password
 * * yesno
 * * dropdown
 * * radio
 * * textarea
 *
 * Examples of each field type and their possible configuration parameters are
 * provided in the sample function below.
 *
 * @see https://developers.whmcs.com/payment-gateways/configuration/
 *
 * @return array
 */
function fonepaygateway_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Fonepay Payment Gateway',
        ),

        'MerchantID' => array(
            'FriendlyName' => 'Merchant Code',
            'Type' => 'text',
            'Size' => '48',
            'Default' => 'NBQM'
        ),

        'ApiUrl' => array(
            'FriendlyName' => 'API URL',
            'Type' => 'text',
            'Size' => '48',
            'Default' => 'https://dev-clientapi.fonepay.com/api/merchantRequest'
        ),
        
        'RedirectUrl' => array(
            'FriendlyName' => 'Verify Url',
            'Type' => 'text',
            'Size' => '48',
            'Default' => 'https://business.thulo.com/account/modules/gateways/fonepay/callback/fonepaygateway.php'
        ),
        
        'Secretkey' => array(
            'FriendlyName' => 'Secret Key',
            'Type' => 'text',
            'Size' => '48',
            'Default' => 'db5b2a0806f3483fb84a8bb48490f0e9'
        ),

        'Remark1' => array(
            'FriendlyName' => 'Remark 1',
            'Type' => 'text',
            'Size' => '48',
            'Default' => 'Hosting Payment'
        ),

        'Remark2' => array(
            'FriendlyName' => 'Remark 2',
            'Type' => 'text',
            'Size' => '48',
            'Default' => 'Hosting Payment 2'
        ),
    );
}

function fonepaygateway_current_page(){
    $filename = basename($_SERVER['SCRIPT_FILENAME']);
    return str_replace(".PHP", "", strtoupper($filename));
}

function fonepaygateway_processing_code(){
    return <<<EOT
        <h3>Processing <i class='fa fa-spin fa-circle-notch'></i></h3>
EOT;
}

function fonepaygateway_noinvoicepage_code(){
    return <<<EOT
        <div class='row'>
            <div class='col-sm-6 col-sm-offset-3'>
                <h3>You are being redirected to the invoice page. </h3>
            <hr />
                <h4>You can choose to pay either with the balance on your <strong>Fonepay Digital Wallet</strong> 
            <br />
            or
            <br />the e-Banking options provided by your bank</h4>
            </div>
        </div>
EOT;
}


function fonepaygateway_invoicepage_code($params){
    // echo "<pre>";print_r($params);exit;
    $MD   = 'P';
    $AMT  = $params['amount'];
    $CRN  = $params['currency'];
    $DT   = date('m/d/Y');
    $R1   = $params['Remark1'];
    $R2   = $params['Remark2'];
    $RU   = $params['RedirectUrl'];
    $PRN  = uniqid();
    $PID  = $params['MerchantID'];
    $APIU = $params['ApiUrl'];
    $sharedSecretKey = $params['Secretkey'];
    $DV  = hash_hmac('sha512',$PID.','.$MD.','.$PRN.','.$AMT.','.$CRN.','.$DT.','.$R1.','.$R2.','.$RU, $sharedSecretKey);
    $_SESSION['redirecturl']=$params['returnurl'];
    $_SESSION['amount']=$AMT;
    $_SESSION['invoiceId'] = $params['invoiceid'];
    return <<<EOT
     <img src="https://dev.fonepay.com/assets/images/brand.svg" style="margin-bottom:10px;">
     <form method="GET" action="{$APIU}">
        <input type="hidden" name="PID" value="{$PID}" >
        <input type="hidden" name="MD" value="{$MD}">
        <input type="hidden" name="AMT" value="{$AMT}">
        <input type="hidden" name="CRN" value="{$CRN}">
        <input type="hidden" name="DT" value="{$DT}">
        <input type="hidden" name="R1" value="{$R1}">
        <input type="hidden" name="R2" value="{$R2}">
        <input type="hidden" name="DV" value="{$DV}">
        <input type="hidden" name="RU" value="{$RU}">
        <input type="hidden" name="PRN" value="{$PRN}">    
        <button type="submit" class="btn btn-success btn-sm" id="btnPayNow"><i class="fas fa-credit-card"></i>Pay Now</button>
     </form>
EOT;
}

function fonepaygateway_link($params) {
    $currentPage = fonepaygateway_current_page();
    if($currentPage !== "VIEWINVOICE"){
        // Wait for the page to be redirected to the invoice page.
        return fonepaygateway_noinvoicepage_code();
    }
    return  fonepaygateway_invoicepage_code($params);
}