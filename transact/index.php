<?php

	ini_set('display_errors','On');  
	error_reporting(E_ALL);
	
	require 'vendor/autoload.php';
	
	use net\authorize\api\contract\v1 as AnetAPI;
	use net\authorize\api\controller as AnetController;
	
	$data = $_POST;

	if(count($data) > 0) {
		
		$eventid = intval($data["eventid"]);
		$isstore = intval($data["isstore"]) == 1 ? true : false;
		
		include("auth.php");	
			
		//SANDBOX - also change 'PRODUCTION' to 'SANDBOX' below
		//$authid = "4yWp34RY";
		//$authkey = "4zS567m658nkWHxn";
		
		/* Create a merchantAuthenticationType object with authentication details retrieved from the constants file */
		$merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
		$merchantAuthentication->setName("$authid");
		$merchantAuthentication->setTransactionKey("$authkey");
		
		// Set the transaction's refId
		$refId = 'ref' . time();
		
		// Create the payment data for a credit card
		$creditCard = new AnetAPI\CreditCardType();
		$creditCard->setCardNumber($data["card_num"]);
		$creditCard->setExpirationDate($data["exp_y"]."-".$data["exp_m"]);
		
		// Add the payment data to a paymentType object
		$paymentOne = new AnetAPI\PaymentType();
		$paymentOne->setCreditCard($creditCard);
		
		// Create order information
		$order = new AnetAPI\OrderType();
		$order->setInvoiceNumber(substr($data["company"],0,20));
		$order->setDescription($data["description"]." - ".$data["company"]);
		
		// Set the customer's Bill To address
		$customerAddress = new AnetAPI\CustomerAddressType();
		$customerAddress->setFirstName($data["first_name"]);
		$customerAddress->setLastName($data["last_name"]);
		$customerAddress->setAddress($data["address"]);
		$customerAddress->setZip($data["zip"]);
		
		// Set the customer's identifying information
	    $customerData = new AnetAPI\CustomerDataType();
	    $customerData->setType("individual");
	    //$customerData->setId("");
	    $customerData->setEmail($data["email"]);
	
		// Create a TransactionRequestType object and add the previous objects to it
		$transactionRequestType = new AnetAPI\TransactionRequestType();
		$transactionRequestType->setTransactionType("authCaptureTransaction");
		$transactionRequestType->setAmount($data["amount"]);
		$transactionRequestType->setOrder($order);
		$transactionRequestType->setPayment($paymentOne);
		$transactionRequestType->setBillTo($customerAddress);
		$transactionRequestType->setCustomer($customerData);
		
		// Assemble the complete transaction request
		$request = new AnetAPI\CreateTransactionRequest();
		$request->setMerchantAuthentication($merchantAuthentication);
		$request->setRefId($refId);
		$request->setTransactionRequest($transactionRequestType);
		
		// Create the controller and get the response
		$controller = new AnetController\CreateTransactionController($request);
		$response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::PRODUCTION);
	
		$return = array("status"=>"error");
		
		if ($response != null) {
	   	
			$tresponse = $response->getTransactionResponse();
	
			if($response->getMessages()->getResultCode() == "Ok") {
				if($tresponse != null && $tresponse->getMessages() != null) {
					/* SUCCESSFUL CHARGE */
					$return["status"] = "OK";
				} else {
					/* NOT SUCCESSFUL */
					if($tresponse->getErrors() != null) {
						$return["status"] = "error";
						$return["code"] = $tresponse->getErrors()[0]->getErrorCode();
						$return["message"] = $tresponse->getErrors()[0]->getErrorText();
					}
				}
			} else {
				$return["status"] = "error";
				$return["code"] = $tresponse->getErrors()[0]->getErrorCode();
				$return["message"] = $tresponse->getErrors()[0]->getErrorText();
			}// /if ok
		} else {
			/* NOT SUCCESSFUL */
			$tresponse = $response->getTransactionResponse();
	
			if($tresponse != null && $tresponse->getErrors() != null) {
				$return["status"] = "error";
				$return["code"] = $tresponse->getErrors()[0]->getErrorCode();
				$return["message"] = $tresponse->getErrors()[0]->getErrorText();
			} else {
				$return["status"] = "error";
				$return["code"] = $tresponse->$response->getMessages()->getMessage()[0]->getCode();
				$return["message"] = $response->getMessages()->getMessage()[0]->getText();
			}
		}
	}

	print(json_encode($return));

	exit();
	
?>
