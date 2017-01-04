<?php

class PayPal {
	/*
		PayPal Backend Class
		Created: 03/01/2017 by Zachary Williams of OrbitSystems.ca http://www.orbitsystems.ca
		
		This class facilitates the creation of the Encrypted Blob needed when dynamically generating an Encrypted Paypal Payment Button.
		
		Usage: 
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
			an then they return (boolean) false.
			See below regarding checking error state and messages.
			
		
		Other Public Functions:
			getErrorState(): This method returns the current state of the private $ErrorState Property. (Returns Boolean (TRUE/FALSE)
			getLastErrorMessage(): This method returns the private $LastErrorMessage Property. (Returns String)
			
		
	*/
	
/* // Example of the data arrays needed during a call to $this->GenerateEncryptedButon() from outside the class.
$ButtonData = array('lc' => 'CA', // Locality
					'item_name' => 'Weekly Gold Subscription',
					'no_note' => '1',
					'no_shipping' => '2',
					'rm' => '1',
					'return' => 'http://yoursite.ca/success/1ms', // Address users are returned to after succesful purchase
					'cancel_return' => 'http://yoursite.ca/cancel/1ms', // Address users are returned to after canceled purchase.
					'a1' => '11.96', // Trial 1 Price
					'p1' => '1', // Trial Length (int)
					't1' => 'W', // Trial Length (W = Weeks, M = Months)
					'src' => '1',
					'a3' => '14.95', // Regular Price
					'p3' => '1', // Regular Subscription Interval (int)
					't3' => 'W', // Regular Subscription Length (W = Weeks, E.T.C)
					'currency_code' => 'USD' // Currency of Purchase
);
// Build BuyNow Properties Array
$ButtonData = array('lc' => 'CA', // Locality
					'item_name' => '100 Credits',
					'amount' => '10.00', // Amount for Single Item Purchase
					'currency_code' => 'USD', // Currency of Purchase
					'button_subtype' => 'services',
					'no_note' => '0',
					'cn' => 'Add special instructions to the seller:',
					'no_shipping' => '2',
					'rm' => '1',
					'return' => 'http://yoursite.ca/success/1ms', // Address users are returned to after successfull purchase
					'cancel_return' => 'http://yoursite.ca/cancel/1ms', // Address users are returned to after canceled purchase
					'tax_rate' => '7.000', // Tax Rate Charged(float) in % (ie: 7.000%)
				);
*/
	
		
	// Class Configuration
	const MY_KEY_FILE = "/var/www/yoursite.ca/private/paypal/my-private-paypal-key.pem"; // My OpenSSL Key File
	const MY_CERT_FILE = "/var/www/yoursite.ca/private/paypal/my-public-paypal-cert.pem"; // My OpenSSL Certificate
	const Sandbox_PAYPAL_CERT_FILE = "/var/www/yoursite.ca/private/paypal/sandbox_paypal_cert.pem"; // Paypals Public Certificate for SandBox Calls (Downloaded from inside Paypal Backend)
	const Live_PAYPAL_CERT_FILE = "/var/www/yoursite.ca/private/paypal/live_paypal_cert.pem"; // Paypals Public Certificate for Live Calls (Downloaded from inside Paypal Backend)
	const OPENSSL_BIN = "/usr/bin/openssl"; // Location of openssl binary on system.
	const Sandbox_CERTID = "ULD4AJU436W7A"; // Sandbox ID of My OpenSSL Certificate once uploaded to Paypal.
	const Live_CERTID = "LNUAUJDHEVUP2"; // Live ID of My OpenSSL Certificate once uploaded to Paypal.
	const Paypal_Sandbox_URI = "https://www.sandbox.paypal.com/cgi-bin/webscr"; // Sandbox Paypal URI
	const Paypal_Live_URI = "https://www.paypal.com/cgi-bin/webscr"; // Live Paypal URI
	const LiveBusinessID = "admin@yourbusiness.ca";
	const SandBoxBusinessID = "admin@yourbusiness.ca";
	const IPN_LISTENER_OVERRIDE_URI = "http://www.yoursite.ca/ipnlisten.php";
	
	private $KEY_FILE = ""; // Contains realpath(self::MY_KEY_FILE)
	private $CERT_FILE = ""; // Contains realpath(self::MY_CERT_FILE)
	private $SANDBOX_PAYPAL_CERT = ""; // Contains realpath(self::Sandbox_PAYPAL_CERT_FILE)
	private $LIVE_PAYPAL_CERT = ""; // Contains realpath(self::Live_PAYPAL_CERT_FILE)
	
	private $ErrorState = FALSE; // Hold Boolean Error State Flag
	private $LastErrorMessage = NULL; // Error Message Bugger, Obtained via $this->getLastErrorMessage()
	private $IPN_LISTEN_OVERRIDE = FALSE; // Do we Implement the IPN Override Property when creating a button.
	private $ButtonTypes = array("subscription" => "_xclick-subscriptions", "buynow" => "_xclick"); // Contains array of (string)ButtonTypes to 'cmd'={Unique String} for PP Button
	
	private $Active_CERTID = "";
	private $Active_URI = "";
	private $Active_Paypal_Cert = "";
	private $Active_BusinessID = "";
	
	public function __construct(){
		// Valid Cert File Existence and Make Paths Real, placing them into their own private properties.
		if (!file_exists(self::MY_KEY_FILE)) {
			$ErrMsg = "ERROR: MY_KEY_FILE ".self::MY_KEY_FILE." not found.";
			$this->flagError($ErrMsg);
		}else{
			$this->KEY_FILE = realpath(self::MY_KEY_FILE);
		}
		if (!file_exists(self::MY_CERT_FILE)) {
			$ErrMsg = "ERROR: MY_CERT_FILE ".self::MY_CERT_FILE." not found.";
			$this->flagError($ErrMsg);
		}else{
			$this->CERT_FILE = realpath(self::MY_CERT_FILE);
		}
		if (!file_exists(self::Sandbox_PAYPAL_CERT_FILE)) {
			$ErrMsg = "ERROR: PAYPAL_CERT_FILE ".self::Sandbox_PAYPAL_CERT_FILE." not found.";
			$this->flagError($ErrMsg);
		}else{
			$this->SANDBOX_PAYPAL_CERT = realpath(self::Sandbox_PAYPAL_CERT_FILE);
		}
		if (!file_exists(self::Live_PAYPAL_CERT_FILE)) {
			$ErrMsg = "ERROR: PAYPAL_CERT_FILE ".self::Live_PAYPAL_CERT_FILE." not found.";
			$this->flagError($ErrMsg);
		}else{
			$this->LIVE_PAYPAL_CERT = realpath(self::Live_PAYPAL_CERT_FILE);
		}
		$this->Active_CERTID = self::Live_CERTID;
		$this->Active_Paypal_Cert = $this->LIVE_PAYPAL_CERT;
		$this->Active_URI = self::Paypal_Live_URI;
		$this->Active_BusinessID = self::LiveBusinessID;
	}
	
	public function GenerateEncryptedButton($ButtonType, $ButtonProperties){
		if(!isset($ButtonType) || empty($ButtonType)){
			$ErrMsg = "Button Types need to be specified to ".__METHOD__." of ".__CLASS__." on ".__LINE__;
			$this->flagError($ErrMsg);
			return false;
		}else if(!array_key_exists(strtolower($ButtonType), $this->ButtonTypes)){
			$ErrMsg = "Button Type not found in button types property array of ".__METHOD__." of ".__CLASS__." on ".__LINE__;
			$this->flagError($ErrMsg);
			return false;
		}
		if($this->IPN_LISTEN_OVERRIDE){
			$ButtonProperties['address_override'] = "1";
			$ButtonProperties['notify_url'] = self::IPN_LISTENER_OVERRIDE_URI;
		}
		$ButtonProperties['cert_id'] = $this->Active_CERTID; // Add Active CERTID onto the end of Incoming $ButtonProperties
		$ButtonProperties['cmd'] = $this->ButtonTypes[strtolower($ButtonType)]; // Add cmd property into ButtonProperties Array Specific to Button Type Specified.
		$ButtonProperties['business'] = $this->Active_BusinessID; // Add Currently Active BusinessID to Property Array
		$ButtonProperties['bn'] = "OrbitSystems.ca.PHP_EWP2"; // Append OrbitSystems.ca Tag as Coders of Class
		$MY_CERT_FILE = $this->CERT_FILE; // Copy My Cert File to Local Scope
		$MY_KEY_FILE = $this->KEY_FILE; // Copy My Key File to Local Scope
		$PAYPAL_CERT_FILE = $this->Active_Paypal_Cert; // Copy Active Paypal Cert File to local scope
		$OPENSSL = self::OPENSSL_BIN; // Copy OpenSSL_BIN to Local Scope
		$DataToEncrypt = ""; // Create Blank Data Buffer
		// Iterate over all incoming ButtonProperties and add them into Data Buffer
		foreach ($ButtonProperties as $key => $value) {
			if ($value != "") {
				error_log("Storing Key: ".$key." Value: ".$value);
				$DataToEncrypt .= "$key=$value\n";
			}
		}
		error_log("Data to Encrypt: ".$DataToEncrypt);
		$openssl_cmd = "($OPENSSL smime -sign -signer $MY_CERT_FILE -inkey $MY_KEY_FILE " .
							"-outform der -nodetach -binary <<_EOF_\n$DataToEncrypt\n_EOF_\n) | " .
							"$OPENSSL smime -encrypt -des3 -binary -outform pem $PAYPAL_CERT_FILE";
	
		exec($openssl_cmd, $output, $error);
	
		if (!$error) {
			$this->clearErrorflag();
			return implode("\n",$output);
		} else {
			$ErrMsg = "ERROR: encryption failed";
			$this->flagError($ErrMsg);
			return false;
		}
	}
	
	// Returns Boolean TRUE/FALSE
	public function getErrorState(){
		return $this->ErrorState;
	}
	
	// Returns String composed of any Error Messages generated internally or NONE if there has not been any errors.
	public function getLastErrorMessage(){
		if($this->ErrorState || empty($this->LastErrorMessage)){
			return $this->LastErrorMessage;
		}else{
			return "NONE";
		}
	}
	
	// Sets Internal Active Properties based to SandBox Configuration Constants, and on __construct processed Properties. Returns Nothing.
	public function useSandBox(){
		$this->Active_CERTID = self::Sandbox_CERTID;
		$this->Active_Paypal_Cert = $this->SANDBOX_PAYPAL_CERT;
		$this->Active_URI = self::Paypal_Sandbox_URI;
		$this->Active_BusinessID = self::SandBoxBusinessID;
	}
	
	// Return (string) Currently Active PayPal URL (the one to be posted to for the form)
	// This function is useful when called as such: <form action="<?php print($PayPalObject->getPaypalURI()); ?\>"...</form>
	public function getPaypalURI(){
		return $this->Active_URI;
	}
	
	// Toggle Class Property Flag so that each generated button uses the custom ipn notificaton url specified in self::IPN_LISTENER_OVERRIDE_URI
	public function EnableIPNOverride(){
		$this->IPN_LISTEN_OVERRIDE = TRUE;
	}
	
	// Flag the Class as having encountered and error. if this is not the first error, append it to the buffer.
	private function flagError($ErrorMessage){
		if($this->ErrorState){ // If there is a pre existing error in the buffer.
			$this->LastErrorMessage .= $this->LastErrorMessage . "\n" . $ErrorMessage;
		}else{
			$this->ErrorState = TRUE;
			$this->LastErrorMessage = $ErrorMessage;
		}
		error_log("Error: ".$this->LastErrorMessage);
	}
	
	// Used Internally to clear the ErroState and LastErrorMessage
	private function clearErrorflag(){
		$this->ErrorState = FALSE;
		unset($this->LastErrorMessage);
	}
}
?>