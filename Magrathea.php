<?php

class Magrathea_Client {

	 /* Instance variables. */

	private $magratheaSettings = array('server', 'port', 'username', 'password');
	private $magratheaConnection;
	private $magratheaErrorMessage;
	
	 /* Magic methods. */

	 /**
	 * Constructor - sets up instance and connects to the Magrathea API.
	 *
	 * Creates an instance of the Magrathea_Client class with the
	 * specified details (if any), and connects this instance to the Magrathea
	 * API for further activity.
	 *
	 * @praram array $instanceMagratheaSettings Settings to use to connect to
	 *   Magrathea, if different from the default.
	 */
	function __construct($instanceMagratheaSettings = null) {
	
		if ($instanceMagratheaSettings !== null) {
			foreach ($instanceMagratheaSettings AS $magSettingKey => $magSettingValue) {
				//if (array_key_exists($magSettingKey, $this->magratheaSettings)) {
					$this->magratheaSettings[$magSettingKey] = $magSettingValue;
				//}
			}
		}
		$magratheaConnection = $this->_connect();
	
	}

	 /**
	 * Destructor - disconnects from the Magrathea API.
	 */
	function __destruct() {
	
		if (is_resource($this->magratheaConnection)) {
			$this->disconnect();
		}
	
	}

	 /**
	 * Get overloader - gets a variable value from $magratheaSettings array.
	 *
	 * @param string $varName The name of the $magratheaSettings key to get.
	 * @return mixed Either the value of the variable, or false if the key
	 *   doesn't exist in the array.
	 */
	function __get($varName) {
	
		if (array_key_exists($varName, $this->magratheaSettings)) {
			return $this->magratheaSettings[$varName];
		} else {
			return false;
		}
	
	}

	 /**
	 * Set overloader - sets a variable value in $magratheaSettings array.
	 *
	 * @param string $varName The name of the $magratheaSettings key to set.
	 * @param string $value The value to set the variable to.
	 * @return boolean True on success, false on failure.
	 */
	function __set($varName, $value) {
	
		if (array_key_exists($varName, $this->magratheaSettings)) {
			$this->magratheaSettings[$varName] = $value;
			return true;
		} else {
			return false;
		}
	
	}
	
	 /* Public methods. */
	 
	 /**
	 * Returns the last error message generated.
	 *
	 * @return string The error message.
	 */
	public function last_error() {
	
		return $this->magratheaErrorMessage;
	
	}
	
	 /**
	 * Disconnects from the Magrathea API.
	 *
	 * @return boolean True on success, false on failure.
	 */
	public function disconnect() {
	
		if (is_resource($this->magratheaConnection)) {
			fwrite($this->magratheaConnection, 'QUIT');
			return fclose($this->magratheaConnection);
		} else {
			$this->magratheaErrorMessage = 'Invalid connection to Magrathea API.';
			return false;
		}
	
	}
	
	 /**
	 * Redirect a non-geographic number to a real phone destination.
	 *
	 * Redirects the given non-geographic number managed by Magrathea to the
	 * given location.  Allows for the setting of destination type as well as
	 * assigning indexes to redirections for use in scheduling.
	 *
	 * @param string $nonGeoNumber Number to redirect.
	 * @param string $targetDestination Destination for this number.
	 * @param int $destinationIndex Index, 1, 2 or 3, for this redirect. Defaults
	 *   to 1 if not speficied.
	 * @param string $destinationType Destination type: L, F, V, S, s.  Defaults
	 *   to L if not specified.  I and H are currently unsupported.
	 * @return boolean True on success, false on failure.
	 */
	public function redirect($nonGeoNumber, $targetDestination, $destinationIndex = 1, $destinationType = 'L') {
	
		$validTarget = false;
		switch ($destinationType) {
			case 'L':
				$targetDestination = preg_replace('/\D/', '', $targetDestination);
				if (preg_match('/(01|02)\d{6,}/', $targetDestination)) {
					$validTarget = true;
					$targetDestination = '44'.substr($targetDestination, 1);
					echo $targetDestination;
				}
			break;
			case 'F': case 'V':
				if (preg_match('/^[a-zA-Z]([.]?([[:alnum:]_-]+)*)?@([[:alnum:]\-_]+\.)+[a-zA-Z]{2,6}$/', $targetDestination)) {
					$validTarget = true;
					$targetDestination = $destinationType.':'.$targetDestination;
				}
			break;
			case 'S': case 's':
				if (preg_match('/[[:alnum:]]+@[[:alnum:]]+/', $targetDestination)) {
					$validTarget = true;
					$targetDestination = $destinationType.':'.$targetDestination;
				}
			break;
		}
		if ($validTarget === true) {
			if (is_resource($this->magratheaConnection)) {
				$nonGeoNumber = preg_replace('/\D/', '', $nonGeoNumber);
				$destinationIndex = preg_replace('/\D/', '', $destinationIndex);
				fwrite($this->magratheaConnection, 'SET '.$nonGeoNumber.' '.$destinationIndex.' '.$targetDestination);
				$apiResponse = fgets($this->magratheaConnection);
				if (substr($apiResponse, 0, 1) == '0') {
					return true;
				} else {
					$this->magratheaErrorMessage = 'Error during SET command. Magrathea API returned: '.$apiResponse;
					return false;
				}
			} else {
				$this->magratheaErrorMessage = 'Invalid connection to Magrathea API.';
				return false;
			}
		} else {
			$this->magratheaErrorMessage = 'Invalid destination type \''.$destinationType.'\'.';
			return false;
		}
	
	}
	
	 /**
	 * Deactivate a non-geographic number.
	 *
	 * Deactivates the given non-geographic number managed by Magrathea.
	 *
	 * @param string $nonGeoNumber Number to deactivate.
	 * @param boolean True on success, false on failure.
	 */
	public function deactivate($nonGeoNumber) {
	
		if (is_resource($this->magratheaConnection)) {
			fwrite($this->magratheaConnection, 'DEAC '.$nonGeoNumber);
			$apiResponse = fgets($this->magratheaConnection);
			if (substr($apiResponse, 0, 1) == '0') {
				return true;
			} else {
				$this->magratheaErrorMessage = 'Error during deactivation. Magrathea API returned: '.$apiResponse;
				return false;
			}
		} else {
			$this->magratheaErrorMessage = 'Invalid connection to Magrathea API.';
			return false;
		}
	
	}
	 
	 /* Private methods. */
	
	 /**
	 * Connects to the Magrathea API and authenticates with instance credentials.
	 *
	 * @return boolean True on successful connection, false on failure.
	 */
	private function _connect() {
	
		if ($this->magratheaConnection = fsockopen($this->magratheaSettings['server'], $this->magratheaSettings['port'], $errorNo, $errorString)) {
			$apiResponse = fgets($this->magratheaConnection);
			if (substr($apiResponse, 0, 1) == '0') {
				fwrite($this->magratheaConnection, 'AUTH '.$this->magratheaSettings['username'].' '.$this->magratheaSettings['password']);
				$apiResponse = fgets($this->magratheaConnection);
				if (substr($apiResponse, 0, 1) == '0') {
					return true;
				} else {
					$this->magratheaErrorMessage = 'Error during authentication. Magrathea API returned: '.$apiResponse;
					return false;
				}
			} else {
				$this->magratheaErrorMessage = 'Error during connection. Magrathea API returned: '.$apiResponse;
				return false;
			}
		} else {
			$this->magratheaErrorMessage = $errorString;
			return false;
		}

	}

}