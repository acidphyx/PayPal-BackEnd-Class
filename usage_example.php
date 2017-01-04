<?php include("Paypal.class.php"); ?>
<!html>
<head>
	<title>Paypal Button Generator Example</title>
</head>
<body>
	<div id="subscribe-button">
		<?php 
			// Prepare Custom Value 
			$CustomValue = "userid=".$Acct_ID."&invoiceid=".$Invoice_ID;
			$CustomValue = base64_encode($CustomValue);
			$PayPalManager = new PayPal;
			$PayPalManager->useSandBox();
			$ButtonData = array('lc' => 'CA', // Locality
								'item_name' => 'Weekly Gold Subscription',
								'no_note' => '1',
								'no_shipping' => '2',
								'rm' => '1',
								'return' => 'http://cannixnorthern.ca/success/1ms', // Address users are returned to after succesful purchase
								'cancel_return' => 'http://cannixnorthern.ca/cancel/1ms', // Address users are returned to after canceled purchase.
								'a1' => '11.96', // Trial 1 Price
								'p1' => '1', // Trial Length (int)
								't1' => 'W', // Trial Length (W = Weeks, M = Months)
								'src' => '1',
								'a3' => '14.95', // Regular Price
								'p3' => '1', // Regular Subscription Interval (int)
								't3' => 'W', // Regular Subscription Length (W = Weeks, E.T.C)
								'currency_code' => 'USD' // Currency of Purchase
			);
			

			$EncryptedButtonData = "";
			if($PayPalManager->getErrorState()==TRUE){ // Check for any Errors during class instantiation.
				error_log($PayPalManager->getLastErrorMessage());
			}else{
				$EncryptedButtonData = $PayPalManager->GenerateEncryptedButton("subscription", $ButtonData);
			}
		?>
		<form action="<?php print($PayPalManager->getPaypalURI()); ?>" method="post" target="_top">
			<input type="hidden" name="cmd" value="_s-xclick">
			<input type="hidden" name="encrypted" value="<?php print($EncryptedButtonData); ?>">
			<input type="hidden" name="custom" value="<?php print($CustomValue); ?>"/>
			<input type="image" src="https://www.sandbox.paypal.com/en_US/i/btn/btn_subscribeCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
			<img alt="" border="0" src="https://www.sandbox.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
		</form>
	</div>
</body>
</html>
