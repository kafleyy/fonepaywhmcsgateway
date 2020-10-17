<?php

/**
 * WHMCS Sample Payment Callback File
 *
 * This sample file demonstrates how a payment gateway callback should be
 * handled within WHMCS.
 *
 * It demonstrates verifying that the payment gateway module is active,
 * validating an Invoice ID, checking for the existence of a Transaction ID,
 * Logging the Transaction for debugging and Adding Payment to an Invoice.
 *
 * For more information, please refer to the online documentation.
 *
 * @see https://developers.whmcs.com/payment-gateways/callbacks/
 *
 * @copyright Copyright (c) WHMCS Limited 2017
 * @license http://www.whmcs.com/license/ WHMCS Eula
*/

// Require libraries needed for gateway module functions.
require_once __DIR__ . '/../../../../init.php';
require_once __DIR__ . '/../../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../../includes/invoicefunctions.php';

// Detect module name from filename.
$gatewayModuleName = basename(__FILE__, '.php');

// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables($gatewayModuleName);
//echo "<pre>";print_r($_GET);
//echo "<pre>";print_r($_SESSION['redirecturl']);
//echo "<pre>";print_r($gatewayParams); 
//exit;
// Die if module is not active.
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}
if (isset($_GET['PRN']) && isset($_GET['PID']) && isset($_GET['BID']) && isset($_GET['UID']) && isset($_GET['RU'])
            && isset($_GET['BC']) && isset($_GET['INI']) && isset($_GET['AMT'])) {
        $sharedSecretKey = $gatewayParams['Secretkey'];
        $requestData = [
        'PRN' => $_GET['PRN'],
        'PID' => $gatewayParams['MerchantID'],
        'BID' => $_GET['BID'],
        'AMT' => $_GET['AMT'], // original payment amount
        'UID' => $_GET['UID'],
        'DV' => hash_hmac('sha512', $_GET['PID'].','. $_GET['AMT'].','.$_GET['PRN'].','.$_GET['BID'].','.$_GET['UID'],
        $sharedSecretKey),
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $_GET['RU'].'?'.http_build_query($requestData));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $responseXML = curl_exec($ch);
        if($response = simplexml_load_string($responseXML)){
        if($response->success == 'true'){
            $redirectUrl = $_SESSION['redirecturl'];
            //echo "<pre>";print_r($_SESSION['invoiceId']);
            // Retrieve data returned in payment gateway callback
            // Varies per payment gateway
            $invoiceId = $_SESSION['invoiceId'];
            $transactionId = $_GET['PRN'];
            $paymentAmount = $_SESSION['amount'];
            $paymentFee = 0;
            
            $transactionStatus = 'SUCCESS';
            
            $invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);
            
            checkCbTransID($transactionId);
            
            logTransaction($gatewayParams['name'], $_REQUEST, $transactionStatus);
            
            addInvoicePayment(
                $invoiceId,
                $transactionId,
                $paymentAmount,
                $paymentFee,
                $gatewayModuleName
        );
        header("Location: $redirectUrl");
        
        
        }else{
        echo "Payment Verifcation Failed: ".$response->message;
        }
        }                
}

// if (isset($_REQUEST['TXNID'])){

//        $PID = 'NBQM';
//     $sharedSecretKey = 'a7e3512f5032480a83137793cb2021dc';
//     $requestData = [
//     'PRN' => $_GET['PRN'],
//     'PID' => $PID,
//     'BID' => $_GET['BID'],
//     'AMT' => 10, // original payment amount
//     'UID' => $_GET['UID'],
//     'DV' => hash_hmac('sha512', $PID.',10'.','.$_GET['PRN'].','.$_GET['BID'].','.$_GET['UID'],
//     $sharedSecretKey),
//     ];
//     $ch = curl_init();
//     curl_setopt($ch, CURLOPT_URL, $_GET['RU'].'?'.http_build_query($requestData));
//     curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//     $responseXML = curl_exec($ch);
//     if($response = simplexml_load_string($responseXML)){
//     if($response->success == 'true'){
//     echo "Payment Verifcation Completed: ".$response->message;
//     }else{
//     echo "Payment Verifcation Failed: ".$response->message;
//     }
// }

// function verifyTransaction($gatewayParams)
// {
//     $merchant['merchantId']  = $gatewayParams['MerchantID'];
//     $merchant['appId']       = $gatewayParams['AppID'];
//     $merchant['referenceId'] = $_REQUEST['TXNID'];
//     $merchant['txnAmt']      = $_SESSION['amount'] * 100;

//     $string = 'MERCHANTID=' . $merchant['merchantId'] . ',APPID=' . $merchant['appId'] . ',REFERENCEID=' . $merchant['referenceId'] . ',TXNAMT=' . $merchant['txnAmt'];

//     $pfx          = file_get_contents($gatewayParams['Certificate']);
//     $certPassword = $gatewayParams['CertificatePass'];
    
//     $status       = openssl_pkcs12_read($pfx, $certs, $certPassword);
 
//     if (!$status) {
//         return 'Invalid pasword';
//     }

//     $public_key  = $certs['cert'];
//     $private_key = $certs['pkey'];
//     $pkeyid      = openssl_get_privatekey($private_key);
//     if (!$pkeyid) {
//         return 'Invalid private key';
//     }

//     $status = openssl_sign($string, $signature, $pkeyid, "sha256WithRSAEncryption");
//     openssl_free_key($pkeyid);
//     if (!$status) {
//           return 'Computing of the signature failed';
//     }

//     $signature_value   = base64_encode($signature);
//     $merchant["token"] = $signature_value;

//     $jsonData = json_encode($merchant);

//     $auth = base64_encode($merchant['appId'] . ':'. $gatewayParams['Pass']);

//     try {

//         $curl = curl_init();
//         curl_setopt_array($curl, array(
//             CURLOPT_URL            => "https://login.connectips.com:7443/connectipswebws/api/creditor/validatetxn",
//             CURLOPT_RETURNTRANSFER => true,
//             CURLOPT_ENCODING       => "",
//             CURLOPT_MAXREDIRS      => 10,
//             CURLOPT_TIMEOUT        => 30,
//             CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
//             CURLOPT_CUSTOMREQUEST  => "POST",
//             CURLOPT_POSTFIELDS     => $jsonData,
//             CURLOPT_HTTPHEADER     => array(
//                 "authorization: Basic $auth",
//                 "cache-control: no-cache",
//                 "content-type: application/json",
//             ),
//         ));

//     $response = curl_exec($curl);

//     if ($response === false) {
//         return "It's has some error";
//     }

//     $response = json_decode($response, true);
   
//     return $response;

//     } catch (Exception $e) {

//         return sprintf('Curl failed with error #%d: %s', $e->getCode(), $e->getMessage());

//     }
// }

?>
