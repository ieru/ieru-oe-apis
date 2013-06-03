<?php
/** 
 * Handles authentication requests
 *
 * @package     Organic API
 * @version     1.0 - 2013-04-21
 * 
 * @author      David Baños Expósito
 *
 */
namespace Ieru\Ieruapis\Organic; 

use \Ieru\Restengine\Engine\Exception\APIException;

class AuthAPI
{
    private $_params;
    private $_db;
    private $_lang;
    private $_autolang;

    /**
     * Constructor
     *
     * @param   array   The parameters sent through a POST request or parsed from the URL routing machine
     */
    public function __construct ( &$params, &$config = null )
    {
        $this->_params = $params;
        $this->_config = $config;

        // Connect with the IEEE LOM database that stores the Organic.Edunet resources
        try
        {
            $this->_db =& LOMDatabase::get_db( $config->get_db_info() );
        }
        catch ( APIException $e )
        {
            $e->to_json();
        }

        $this->_lang     = $config->get_iso_lang();
        $this->_autolang = $config->get_autolang();
    }

    /**
     * Register an user for using the API as an authenticated user
     * 
     * @return array Contains in the data payload the usertoken to make requests to the API
     */
    public function login ()
    {
        // Check that both parameters required for login are set
        if ( !isset( $this->_params['username'] ) OR !isset( $this->_params['password'] ) )
            return array( 'success'=>false, 'message'=>'Wrong parameters count for log in.' );

        // Try to connect to the database
        $this->_connect_oauth();

        // Query the database with the username and password given by the user
        $sql = 'SELECT user_id, user_username, user_password 
                FROM users 
                WHERE user_username = ?
                LIMIT 1';

        $stmt = $this->_oauthdb->prepare( $sql );
        $stmt->execute( array( $this->_params['username'] ) );
        
        // Wrong username
        if ( !$user = $stmt->fetch( \PDO::FETCH_ASSOC ) )
            return array( 'success'=>false, 'message'=>'Wrong username or password' );

        // Separate user password
        if ( !$this->_check_password( $this->_params['password'], $user['user_password'] ) )
            return array( 'success'=>false, 'message'=>'Wrong username or password.' );

        // Try to retrieve token for IP and active session, creating a new token if none
        // User can only be logged from one place at a time        
        if ( !$token = $this->_get_token( $user ) )
        {
            $token = $this->_generate_token( $user );
            $stmt = $this->_oauthdb->prepare( 'UPDATE tokens SET token_active = 0 WHERE user_id = ?' );
            $stmt->execute( array( $user['user_id'] ) );
            $stmt = $this->_oauthdb->prepare( 'INSERT INTO tokens SET token_chars = ?, user_id = ?, token_active = ?, token_ip = ?' );
            $stmt->execute( array( $token, $user['user_id'], 1, $_SERVER['REMOTE_ADDR'] ) );
        }

        // Return the token
        $data = array( 'success'=>true, 'message'=>'Correct credentials.', 'data'=>array( 'usertoken'=>$token ) );
        return $data;
    }

    /**
     * Sets the selected token as inactive in the database
     */
    public function logout ()
    {
        // Check that both parameters required for login are set
        if ( !isset( $this->_params['usertoken'] ) )
            return array( 'success'=>false, 'message'=>'User token for logging out not specified.' );

        // Try to connect to the database
        $this->_connect_oauth();

        // Update the usertoken to inactive
        $stmt = $this->_oauthdb->prepare( 'UPDATE tokens SET token_active = 0 WHERE token_chars = ?' );
        $stmt->execute( array( $_COOKIE['usertoken'] ) );

        // Destroy session and related data
        if ( !session_id() )
            session_start();

        session_regenerate_id( true );

        setcookie( 'usertoken', '', 0, '/' );
        setcookie( 'PHPSESSID', '', 0, '/' );

        session_unset();
        session_destroy();

        return array( 'success'=>true, 'message'=>'Session logged out.' );
    }

    /**
     * Register a new user
     *
     * @return  array   the result of the user registration
     */
    public function register ()
    {
        // Check that both parameters required for login are set
        if ( !isset( $this->_params['form-register-username'] ) OR !isset( $this->_params['form-register-password'] ) OR !isset( $this->_params['form-register-email'] ) )
            return array( 'success'=>false, 'message'=>'Wrong parameters count for registering user.' );

        // Check that both parameters required for login are set
        if ( !$this->_params['form-register-username'] OR !$this->_params['form-register-password'] OR !$this->_params['form-register-email'] )
            return array( 'success'=>false, 'message'=>'The parameters can not be empty.' );

        // Check that both passwords are the same
        if ( $this->_params['form-register-password'] != $this->_params['form-register-repeat-password'] )
            return array( 'success'=>false, 'message'=>'The passwords does not match.' );

        // Try to connect database = if an error occurs, it will return an array, nothing otherwise
        if ( $connect = $this->_connect_oauth() )
            return $connect;

        // Format data
        $data['user_username'] = $_POST['form-register-username'];
        $data['user_password'] = $this->_hash_password( $_POST['form-register-password'] );
        $data['user_email']    = $_POST['form-register-email'];

        $params = array();
        foreach ( $data as $key=>$val )
        {
            $params[] = $key.' = ?';
            $values[] = $val;
        }
        $p = implode( ',', $params );

        // Query the database with the username and password given by the user
        $stmt = $this->_oauthdb->prepare( 'INSERT INTO users SET '.$p );
        $stmt->execute( $values );
        
        if ( $stmt->rowCount() > 0 )
            return array( 'success'=>true, 'message'=>'User created.', 'data'=>$data );
        else
            return array( 'success'=>false, 'message'=>'There is already an user with that username.' );
    }

    /**
     * Creates the has of a password for checking with the value stored
     * in the database
     *
     * @param 	string 	$password 	The string to hash
     * @return 	string  The password hashed
     */
    private function _hash_password ( $pass )
    {
        $hash = md5( uniqid( rand(), true ).microtime() );
        $encrypted = md5( $pass.$hash );
        $password = $encrypted.':'.$hash;

    	return $password;
    }

    /**
     * Generates a session token for the given user
     *
     * @param   array   $user   The user data, retrieved from the database
     * @return string(40) The generated token for the given user
     */
    private function _generate_token ( &$user )
    {
        return hash( 'sha256', $user['user_username'].microtime() );
    }

	/** 
     * Retrieves an active access token for the given user and IP address
     *
     * @param   array   $user   The user data, retrieved from the database
     * @return string   The token chars, or empty if not found
     */
    private function _get_token ( &$user )
    {
        $sql = 'SELECT token_chars 
                FROM tokens 
                WHERE user_id = ? AND token_active = 1 AND token_ip = ? 
                ORDER BY token_id DESC 
                LIMIT 1';
        $stmt = $this->_oauthdb->prepare( $sql );
        $stmt->execute( array( $user['user_id'], $_SERVER['REMOTE_ADDR'] ) );
        $token = $stmt->fetch( \PDO::FETCH_ASSOC );

        return $token ? $token['token_chars'] : '';
    }

    /**
     * Connects with the OAuth database
     *
     * @return array is NOK | nothing if OK
     */
    private function _connect_oauth ()
    {
        try 
        {
            $db = $this->_config->get_db_oauth_info();
            $this->_oauthdb = new \PDO( 'mysql:host='.$db['host'].';dbname='.$db['database'], $db['username'], $db['password'] );
        } 
        catch ( \Exception $e ) 
        {
            $e = new APIException( 'An error ocurred while connecting with the database.' );
            $e->to_json();
        }
    }

    /**
     * Import users from a joomla database to the OAuth database
     *
     * @return void
     */
    private function _import_users ()
    {
        // Import users
        $sql = 'SELECT *FROM jos_users';
        $stmt = $this->_oauthdb->prepare( $sql );
        $stmt->execute();

        $users = $stmt->fetchAll( \PDO::FETCH_ASSOC );
        foreach ( $users as $user )
        {
            echo $user['username'], "\n";
            $sql = 'INSERT INTO users SET user_id=?,user_name=?,user_username=?,user_password=?,user_password_joomla=?,user_email=?,user_creation_date=?';
            $stmt = $this->_oauthdb->prepare( $sql );
            $stmt->execute( array( $user['id'], $user['name'], $user['username'], $user['password'],$user['password'],$user['email'],$user['registerDate'] ) );
        }
    }

    /**
     * Check that a given password is equal to a oauth password
     *
     * @param $password
     * @param $oauth_password
     * @return boolean  Wether they are equal or not
     */
    private function _check_password ( $password, $oauth_password )
    {
        $pass = explode( ':', $oauth_password );
        $hash = md5( $password.$pass[1] );

        return $hash == $pass[0] ? true : false;
    }
}