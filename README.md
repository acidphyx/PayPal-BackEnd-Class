# PayPal Encrypted Button Generator
Paypal Backend Functions such as Encrypted Button blob generation.

		PayPal Backend Class
		Created: 03/01/2017 by Zachary Williams of OrbitSystems.ca
		
		This class facilitates the creation of the Encrypted Blob needed when dynamically generating an Encrypted Paypal Payment Button.
		
# Usage: 
			1. Review 'Class Configuration' Section. Ensure Accuracy
			2. Externally Instantiate Class via  $NewObject = new PayPal;
			2(a). Possibly Set Class into SandBox Mode via  $NewObject->useSandBox();
			3. Build $Button_Properties Array as shown in the examples below depending on what type of button you want to create. (Subscription, or BuyNow)
			4. Generate the Encrypted Blob for your custom button by passing the aforementioned array while calling the GenerateEncryptedButton method.
				( Example: $EncryptedBlob = $NewObject->GenerateEncryptedButton((string){subscription/buynow}, (array)$Button_Properties)); )
				That call will return something encrypted or false.
										-----BEGIN PKCS7-----
					MIIIZQYJKoZIhvcNAQcDoIIIVjCCCFICAQAxggE6MIIBNgIBADCBnjCBmDELMAkG
					A1UEBhMCVVMxEzARBgNVBAgTCkNhbGlmb3JuaWExETAPBgNVBAcTCFNhbiBKb3Nl
					MRUwEwY     -=-= A Bunch of Encrypted Data =-=-     ijGNR77rvBZv
					Y+uVGiQj/NE6le1e61RpfVaDCCKkzfcgYY4IwxwZExOgT+0dLKQnrd66OREuOI8g
					VKFL7xwyOo/RuihyXjhlv4W4tSy/vJHHsKl9S8e1vQhINzJuNnwRqHI=
											-----END PKCS7-----
			5. Include Output of previous function call to <input type='hidden' name='encrypted' value='$EncryptedBlob'> as part of the html of a paypal button.
			
		Error Handling:
			All methods return either their intended data or 
			They make an internal call to the flagError() method. See Other Public Functions -> flagError()
			and then they return (boolean) false.
			See below regarding checking error state and messages.
			
		
		Other Public Functions:
			getErrorState(): This method returns the current state of the private $ErrorState Property. (Returns Boolean (TRUE/FALSE)
			getLastErrorMessage(): This method returns the private $LastErrorMessage Property. (Returns String)
	
# Example of Button Property Array used for generation of Subscription Buttons
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

# Example of Button Property Array used for generation of Subscription Buttons
		    $ButtonData = array('lc' => 'CA', // Locality
					'item_name' => '100 Credits',
					'amount' => '10.00', // Amount for Single Item Purchase
					'currency_code' => 'USD', // Currency of Purchase
					'button_subtype' => 'services',
					'no_note' => '0',
					'cn' => 'Add special instructions to the seller:',
					'no_shipping' => '2',
					'rm' => '1',
					'return' => 'http://cannixnorthern.ca/success/1ms', // Address users are returned to after successfull purchase
					'cancel_return' => 'http://cannixnorthern.ca/cancel/1ms', // Address users are returned to after canceled purchase
					'tax_rate' => '7.000', // Tax Rate Charged(float) in % (ie: 7.000%)
				);
