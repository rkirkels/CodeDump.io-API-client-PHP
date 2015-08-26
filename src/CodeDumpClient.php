<?php
namespace rkirkels\codedump_io;
/*
 * Dit werk is gelicenseerd onder de licentie Creative Commons Naamsvermelding 4.0 Internationaal. 
 * Ga naar http://creativecommons.org/licenses/by/4.0/ om een kopie van de licentie te kunnen lezen.
 */

define('TOKEN_KEY', 'INSERT_YOUR_API_KEY_HERE');
define('TOKEN_SECRET', 'INSERT_YOUR_API_SECRET_HERE');
define('performPrechecks', false);

// DO NOT EDIT BELOW THIS LINE ! -----------------------------------------------

define('APIHOST','https://codedump.io/api');

/**
 * Client class for the CodeDump.io API
 *
 * @author Rutger Kirkels <rutger@expanet.nl>
 * @copyright (c) 2015, Rutger Kirkels
 * @license http://creativecommons.org/licenses/by/4.0/ Creative Commons -- Attribution 4.0 International
 * @version 1.0
 * 
 */
class CodeDumpClient {
    
    /**
     *
     * @var Object
     * @static
     * @access private
     */
    private static $instance;
    
    /**
     * Holds all possible HTTP response codes that can be received by the API
     * @var array Contains possible HTTP response codes 
     * @access private
     */
    private $erroneousResponseCodes = array(
        400 => "Bad request",
        401 => "Unauthorized",
        406 => "Missing parameter",
        407 => "Incorrect parameter value"
    );
    
    /**
     * The API key is defined in this class and can be overruled when initiating
     * this class.
     * @var string API key, supplied by CodeDUmp.io 
     * @see getInstance()
     * @see __construct()
     */
    private $apiKey = NULL;
    
    /**
     * The API secret is defined in this class and can be overruled when initiating
     * this class.
     * @var string API secret, supplied by CodeDUmp.io 
     * @see getInstance()
     * @see __construct()
     */
    private $apiSecret = NULL;
    
    /**
     * Time after connection to the API is considered failed.
     * @var integer Time in seconds (default: 30 seconds)
     */
    private $apiTimeOut = 30;
    
    /**
     * Contains the command to execute on the API
     * @var string 
     * @example code/add
     * @example languages/get
     * @example access/get
     */
    private $command = NULL;
    
    /**
     * Contains all variables the are sent to the API
     * @var array
     */
    private $parameters = array();
    
    /**
     * Contains the response from the API
     * @var array
     * @see execute()
     */
    private $response = NULL;
    
    /**
     * Contains detailed statistical information about API-call
     * @var array
     * @see execute()
     */
    private $responseDetails = array();
    
    /**
     * Initializes the API Client as a singleton
     * 
     * @access public
     * @static
     * @param string $apiKey API key (optional)
     * @param string $apiSecret API secret (optional)
     * @return object
     */
    public static function getInstance($apiKey = NULL, $apiSecret = NULL) 
    { 
        $class = get_class(self::$instance);
        if (!self::$instance) 
        { 
            self::$instance = new $class($apiKey, $apiSecret); 
        } 
        return self::$instance; 
    } 
    
    /**
     * @access public
     * @param string $apiKey The API key that you got from CodeDump.io
     * @param string $apiSecret The API secret that you got from CodeDump.io
     */
    public function __construct($apiKey = NULL, $apiSecret = NULL) {
        
        if (!empty($apiKey)) {
            $this->apiKey = $apiKey;
        }
        else {
            $this->apiKey = TOKEN_KEY;
        }
        
        if (!empty($apiSecret)) {
            $this->apiSecret = $apiSecret;
        }
        else {
            $this->apiSecret = TOKEN_SECRET;
        }
    }
    /**
     * @access public
     * @param string $name Name of class property
     * @param array $arguments Array of length 1, containing a variable
     * @return boolean
     */
    public function __call($name, $arguments) {
        if (property_exists(__CLASS__, $name)) {
            if (count($arguments) === 1) {
                $this->$name = $arguments[0];
                return true;
            }
            else {
                return $this->$name;                
            }
        }
    }
    
    /**
     * Adds a parameter to include in the API call
     * 
     * @access public
     * @param string $name Name of the field to send in the API call
     * @param string $value Value to send in the API call
     * @return boolean TRUE if succesfully added
     */
    public function setParameter($name, $value) {
            if (empty($value)) {
                $this->raiseError("You cannot set a parameter with an empty value", E_USER_WARNING);
            }
            $this->parameters[$name] = $value;
            return true;
    }
    
    /**
     * Executes the API call
     * 
     * @access public
     * @return boolean
     */
    public function execute() {
        if (empty($this->apiKey)) {
            $this->raiseError("No API key defined", E_USER_ERROR);
        }
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => APIHOST . "/" . $this->command,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => array_merge(array("token_key" => $this->apiKey, "token_secret" => $this->apiSecret),$this->parameters),
            CURLOPT_TIMEOUT => $this->apiTimeOut
        ));
        $response = curl_exec($curl);
        $responseDecoded = json_decode($response);
        $this->responseDetails = curl_getinfo($curl);
        if ($this->responseDetails['http_code'] !== 200) {
            
            return false;
        }
        if ($responseDecoded->success === true) {
            $this->response = reset($responseDecoded->data);
            return true;
        }
        return false;
    }
    
    public function getLanguages() {
        $this->command = "languages/get";
        if ($this->execute()) {
            return $this->response;
        }
        return false;
    }
    
    public function getAccess() {
       $this->command = "access/get";
        if ($this->execute()) {
            return $this->response;
        }
        return false; 
    }
    
    private function preCheckAddCode() {
        $possibleAccess = $this->getAccess();
        $possibleLanguage = $this->getLanguages();
        if (in_array($this->parameters['access'], $possibleAccess)) { 
            return false;
        }
        if (in_array($this->parameters['language'], $possibleLanguage)) {
            return false;
        }
        return true;
    }
    
    /**
     * Stores your code dump on CodeDump.io
     * @param string $title A descriptive title for your code dump
     * @param string $description Short description about what this code does
     * @param string $code Your code
     * @param string $access Access type: public|private
     * @param string $language The language your code is written in
     * @param boolean $preCheck TRUE if you want your access type and language parameter being checked against the API (Default: FALSE)
     * @return string|boolean string|boolean If succesfull, the direct URL to your code dump is returned, otherwise FALSE
     */
    public function addCode($title, $description, $code, $access, $language, $preCheck = false) {
        // If $file is not a string, raise an error and return FALSE;
        if (gettype($code) !== "string") {
            $this->raiseError(__FUNCTION__ . ": parameter 2 expects string, " . gettype($code) . " given", E_USER_WARNING);
            return false;
        }
        
        // Set the neccessary parameters
        $this->parameters = array(
                "title" => $title,
                "description" => $description,
                "code" => $code,
                "access" => $access,
                "language" => $language
                );
        
        // Perform a check on the given access type and language, if requested.
        if ($preCheck === true || performPrechecks === true) {
            if (!$this->preCheckAddCode()) {
                return false;
            }
        }
        
        // Perform the API call and return the response.
        $this->command = "code/add";
        $this->execute();
        return $this->response;
    }
    
    /**
     * Loads code from a file and stores your code dump on CodeDump.io
     * @param string $title A descriptive title for your code dump
     * @param string $file Path to file containing your code
     * @param string $description Short description about what this code does
     * @param string $access Access type: public|private
     * @param string $language The language your code is written in
     * @param boolean $preCheck TRUE if you want your access type and language parameter being checked against the API (Default: FALSE)
     * @return string|boolean If succesfull, the direct URL to your code dump is returned, otherwise FALSE
     */
    public function addCodeFromFile($title, $file, $description, $access, $language, $preCheck = false) {
        // If $file is not a string, raise an error and return FALSE;
        if (gettype($file) !== "string") {
            $this->raiseError(__FUNCTION__ . ": parameter 2 expects string, " . gettype($file) . " given", E_USER_WARNING);
            return false;
        }
        
        // Read the file contents
        $fileContents = $this->loadFileContents($file, false);
        
        // If the file could not be loaded, raise an error and return FALSE;
        if ($fileContents === false) {
            $this->raiseError("The file " . $file . " does not exist", E_USER_WARNING);
            return false;
        }
        
        // Set the neccessary parameters
        $this->parameters = array(
                "title" => $title,
                "description" => $description,
                "code" => $fileContents,
                "access" => $access,
                "language" => $language
                );
        
        // Perform a check on the given access type and language, if requested.
        if ($preCheck === true || performPrechecks === true) {
            if (!$this->preCheckAddCode()) {
                return false;
            }
        }
        
        // Perform the API call and return the response.
        $this->command = "code/add";
        $this->execute();
        return $this->response;
    }
    
    /**
     * Returns the content of a give file.
     * @access public
     * @param string $file Path to file.
     * @param boolean $reportError Enables error reporting when file does not exist. (Default: TRUE)
     * @return boolean
     */
    public function loadFileContents($file, $reportError = true) {
        if (!file_exists($file)) {
            if ($reportError === true) {
                $this->raiseError("The file " . $file . " does not exist", E_USER_WARNING);
            }
            return false;
        }
        $content = file_get_contents($file);
        return $content;
    }
    
    /**
     * Raises a PHP error
     * @access private
     * @param string $message
     * @param integer $level PHP User error level E_USER_NOTICE|E_USER_WARNING|E_USER_ERROR
     * @return boolean
     */
    private function raiseError($message, $level) {
        $caller = next(debug_backtrace());
        switch ($level) {
            case E_USER_NOTICE:
                $output = "PHP Notice: ";
                break;
            
            case E_USER_WARNING:
                $output = "PHP Warning: ";
                break;
            
            case E_USER_ERROR:
                $output = "PHP Fatal error: ";
                break;
        }
        $output .= $message.' in '.$caller['file'].' on line '.$caller['line'];
        error_log($output);
        if ($level === E_USER_ERROR) {
            die;
        }
        return true;
    }
    
    public function getMyDumps() {
        $this->command = "dumps/get";
        if ($this->execute()) {
            return $this->response;
        }
        return false;
    }
}
