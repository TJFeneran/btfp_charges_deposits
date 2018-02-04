<?php

	if(!isset($_REQUEST["paychoice"])) {
		exit();
	}

	require 'vendor/autoload.php';
	
		use net\authorize\api\contract\v1 as AnetAPI;
		use net\authorize\api\controller as AnetController;

	$data = $_POST;
	if(count($data) > 0) {
	
		$data["contact"] = json_decode($data["contact"], true);
		$refId = $data["userid"]."-".$data["tourdateid"]."-".$data["regid"];

		$merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
		
		//PUT ALL DEPOSIT PROFILES INTO DTS AUTHNET CIM
		
		include("auth.php");
		
		// Set credit card information for payment profile
		$creditCard = new AnetAPI\CreditCardType();
		$creditCard->setCardNumber(str_replace(" ","",$data["cc_number"]));
		$creditCard->setExpirationDate($data["cc_exp_y"]."-".$data["cc_exp_m"]);
		//$creditCard->setCardCode("142");
		$paymentCreditCard = new AnetAPI\PaymentType();
		$paymentCreditCard->setCreditCard($creditCard);
		
		// Create the Bill To info for new payment type
		$billTo = new AnetAPI\CustomerAddressType();
		$billTo->setFirstName($data["contact"]["fname"]);
		$billTo->setLastName($data["contact"]["lname"]);
		$billTo->setCompany("");
		$billTo->setAddress($data["contact"]["address"]);
		$billTo->setCity($data["contact"]["city"]);
		$billTo->setState($data["contact"]["state"]);
		$billTo->setZip($data["contact"]["zip"]);
		$billTo->setCountry($data["contact"]["country"]);
		$billTo->setPhoneNumber($data["contact"]["phone"]);
		$billTo->setfaxNumber($data["contact"]["fax"]);
		
		// Create a new CustomerPaymentProfile object
		$paymentProfile = new AnetAPI\CustomerPaymentProfileType();
		$paymentProfile->setCustomerType('individual');
		$paymentProfile->setBillTo($billTo);
		$paymentProfile->setPayment($paymentCreditCard);
		$paymentProfile->setDefaultpaymentProfile(true);
		$paymentProfiles[] = $paymentProfile;
		
		
		// Create a new CustomerProfileType and add the payment profile object
		$customerProfile = new AnetAPI\CustomerProfileType();
		$customerProfile->setDescription($data["eventname"]." ".$data["city"]." | $".$data["amountdue"]." due");
		$customerProfile->setMerchantCustomerId($refId);
		$customerProfile->setEmail($data["contact"]["email"]);
		$customerProfile->setpaymentProfiles($paymentProfiles);
		//$customerProfile->setShipToList($shippingProfiles);
		
		
		// Assemble the complete transaction request
		$request = new AnetAPI\CreateCustomerProfileRequest();
		$request->setMerchantAuthentication($merchantAuthentication);
		$request->setRefId($refId);
		$request->setProfile($customerProfile);
		
		// Create the controller and get the response
		$controller = new AnetController\CreateCustomerProfileController($request);
		$response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::PRODUCTION);
		  
		$return = array("status"=>"error");
		
		if (($response != null) && ($response->getMessages()->getResultCode() == "Ok")) {
			
			$paymentProfiles = $response->getCustomerPaymentProfileIdList();
			
			$return["status"] = "OK";
			$return["customerprofileid"] = $response->getCustomerProfileId();
			$return["customerpaymentprofileid"] = $paymentProfiles[0];
			$return["customerid"] = $refId;
			
			//echo "Succesfully created customer profile : " . $response->getCustomerProfileId() . "\n";
			//$paymentProfiles = $response->getCustomerPaymentProfileIdList();
			//echo "SUCCESS: PAYMENT PROFILE ID : " . $paymentProfiles[0] . "\n";
		
		} else {
		
			$errorMessages = $response->getMessages()->getMessage();
			
			$return["status"] = "error";
			$return["code"] = $errorMessages[0]->getCode();
			$return["message"] = $errorMessages[0]->getText();
			
			//echo "ERROR :  Invalid response\n";
			//$errorMessages = $response->getMessages()->getMessage();
			//echo "Response : " . $errorMessages[0]->getCode() . "  " .$errorMessages[0]->getText() . "\n";
		
		}
		
	}
	
	print(json_encode($return));


	exit();

?>