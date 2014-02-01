<?php
/** 
 * Handles authentication requests
 *
 * @package     Organic API
 * @version     1.1 - 2013-06-24 | 1.0 - 2013-04-21
 * 
 * @author      David Baños Expósito
 *
 */
namespace Ieru\Ieruapis\Organic; 

use \Ieru\Restengine\Engine\Exception\APIException;
use Ieru\Ieruapis\Organic\Models\User;
use Ieru\Ieruapis\Organic\Models\Token;

use Illuminate\Database\Capsule\Manager as Capsule;

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
    public function __construct ( &$params, &$config = null, $databases = null )
    {
        // Set params of the request
        $this->_params = $params;

        // Set configuration file and variables
        if ( $config )
        {
            $this->_config   = $config;
            $this->_lang     = $config->get_iso_lang();
            $this->_autolang = $config->get_autolang();
        }

        // Set databases
        if ( $databases )
        {
            $this->_db = $databases;
        }
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
            return array( 'success'=>false, 'message'=>923 );

        // Try to connect to the database
        $this->_connect_oauth();

        // Query the database with the username and password given by the user
        $user = User::where('user_username', '=', $this->_params['username'])->first();
        
        // Wrong username
        if ( !is_object( $user ) )
            return array( 'success'=>false, 'message'=>922 );

        // Separate user password
        if ( !$this->_check_password( $this->_params['password'], $user->user_password ) )
            return array( 'success'=>false, 'message'=>922 );

        // Try to retrieve token for IP and active session, creating a new token if none
        // User can only be logged from one place at a time
        $token = $this->_get_token( $user );
        if ( !is_object( $token ) )
        {
            Token::where('user_id', '=', $user['user_id'])->update(array('token_active'=>0));
            $token_chars = $this->_generate_token( $user );
            $token = new Token();
            $token->token_chars = $token_chars;
            $token->user_id = $user['user_id'];
            $token->token_active = 1;
            $token->token_ip = $_SERVER['REMOTE_ADDR'];
            $token->save();
        }

        // Return the token
        return array( 'success'=>true, 'message'=>921, 'data'=>array( 'usertoken'=>$token->token_chars ) );
    }

    /**
     * Sets the selected token as inactive in the database
     */
    public function logout ()
    {
        // Check that both parameters required for login are set
        if ( !isset( $this->_params['usertoken'] ) )
            return array( 'success'=>false, 'message'=>920 );

        // Try to connect to the database
        $this->_connect_oauth();

        // Update the usertoken to inactive
        $token = Token::where('token_chars', '=', @$this->_params['usertoken'])->first();
        if ( is_object( $token ) )
        {
            $token->token_active = 0;
            $token->save();
            $return = array( 'success'=>true, 'message'=>'Session logged out.' );
        }
        else
        {
            // Return true anyway for deleting cookies on the browser
            $return = array( 'message'=>true, 'message'=>'Invalid token.' );
        }

        // Destroy session and related data even if there was a problem with the token
        session_start();

        session_regenerate_id( true );

        setcookie( 'usertoken', '', 0, '/' );
        setcookie( 'PHPSESSID', '', 0, '/' );

        session_unset();
        session_destroy();
        return $return;
    }

    /**
     * Register a new user
     *
     * @return  array   the result of the user registration
     */
    public function register ()
    {
        // Check that both parameters required for login are set
        if ( !isset( $this->_params['form-register-accept'] ) )
            return array( 'success'=>false, 'message'=>900 );

        // Check that both parameters required for login are set
        if ( !isset( $this->_params['form-register-username'] ) OR !isset( $this->_params['form-register-password'] ) OR !isset( $this->_params['form-register-email'] ) )
            return array( 'success'=>false, 'message'=>901 );

        // Check that the username length is correct
        if ( strlen( $this->_params['form-register-username'] ) < 3 )
            return array( 'success'=>false, 'message'=>902 );

        // Check that the password length is correct
        if ( strlen( $this->_params['form-register-password'] ) < 6 OR preg_match( '/[^a-z0-9]/si', $this->_params['form-register-password'] ) )
            return array( 'success'=>false, 'message'=>912 );

        // Check that the email is correct
        if ( !filter_var( $this->_params['form-register-email'], FILTER_VALIDATE_EMAIL ) )
            return array( 'success'=>false, 'message'=>914 );

        // Check that both parameters required for login are set
        if ( preg_match( '/[^a-z0-9]/si', $this->_params['form-register-username'] ) )
            return array( 'success'=>false, 'message'=>903 );

        // Check that both passwords are the same
        if ( $this->_params['form-register-password'] != $this->_params['form-register-repeat-password'] )
            return array( 'success'=>false, 'message'=>904 );

        // Try to connect database = if an error occurs, it will return an array, nothing otherwise
        if ( $connect = $this->_connect_oauth() )
            return $connect;

        // Query the database with the username and password given by the user
        $user = new User();
        $user->user_username = $_POST['form-register-username'];
        $user->user_password = $this->_hash_password( $_POST['form-register-password'] );
        $user->user_email    = $_POST['form-register-email'];
        $user->user_name     = $_POST['form-register-name'];
        $user->user_active   = 0;
        $user->user_activation_hash = md5( $user['user_password'] );
        
        try
        {
            if ( $user->save() )
            {
                $data = $user->toarray();
                $this->_send_activation_email( $data );
                return array( 'success'=>true, 'message'=>905 );
            }
            else
            {
                return array( 'success'=>false, 'message'=>906 );
            }
        }
        catch ( \Exception $e )
        {
            return array( 'success'=>false, 'message'=>906 );
        }
    }

    /**
     * Activates an user through the link sent to his email
     */
    public function activate ()
    {
        // Check if the parameters are correct
        if ( !isset( $this->_params['user'] ) OR !isset( $this->_params['hash'] ) )
            return array( 'success'=>false, 'message'=>907 );

        // Check if the parameters are correct
        if ( !$this->_params['user'] OR !$this->_params['hash'] )
            return array( 'success'=>false, 'message'=>908 );

        $data['user_active'] = 1;

        // Try to connect database = if an error occurs, it will return an array, nothing otherwise
        if ( $connect = $this->_connect_oauth() )
            return $connect;

        try
        {
            // Query the database with the username and password given by the user
            $user = User::where('user_activation_hash', '=', $this->_params['hash'])
                        ->where('user_username', '=', $this->_params['user'])
                        ->where('user_active', '=', 0)
                        ->first();

            // If the query affects at least one row, the user has been activated
            if ( $user->count() )
            {
                $user->user_active = 1;
                $user->user_activation_hash = '';
                $user->save();
                return array( 'success'=>true, 'message'=>909 );
            }
            else
            {

                return array( 'success'=>false, 'message'=>910 );
            }
        }
        catch ( \Exception $e )
        {
            return array( 'success'=>false, 'message'=>911 );
        }
    }

    /**
     * Sends an activation email to the user
     *
     * @param   array   &$data  The user information registering
     * @return  void
     */
    private function _send_activation_email ( &$data )
    {
        $mail = new \Ieru\Ieruapis\Organic\PHPMailer();

        $mail->From = 'no-reply@organic-edunet.eu';
        $mail->FromName = 'Organic.Edunet';
        $mail->AddAddress( $data['user_email'] );  // Add a recipient
        $mail->AddReplyTo('no-reply@organic-edunet.eu', 'Information');
        $mail->AddBCC('david.banos@uah.es');

        $mail->WordWrap = 50;                                 // Set word wrap to 50 characters
        $mail->IsHTML(true);                                  // Set email format to HTML

        $mail->Subject = '[Organic.Edunet] New user registration';
        $mail->Body    = '<p>Thank you for registering. You can activate your account following this link</p><p><a href="'.API_SERVER.'/#/user/register/'.$data['user_username'].'/'.$data['user_activation_hash'].'">'.API_SERVER.'/#/user/register/activation/'.$data['user_activation_hash'].'</a></p><p>Organic.Edunet website</p>';
        $mail->AltBody = "Thank you for registering. You can activate your account following this link\n".API_SERVER."/#/user/register/".$data['user_username']."/".$data['user_activation_hash']."\nOrganic.Edunet website";

        if(!$mail->Send()) {
           //echo 'Message could not be sent.';
           //echo 'Mailer Error: ' . $mail->ErrorInfo;
           //exit;
        }
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
        return Token::where('user_id', '=', $user['user_id'])
                    ->where('token_active', '=', 1)
                    ->where('token_ip', '=', $_SERVER['REMOTE_ADDR'])
                    ->first();
    }

    /**
     * Connects with the OAuth database
     *
     * @return array is NOK | nothing if OK
     */
    private function _connect_oauth ()
    {
        $capsule = new Capsule();
        $capsule->addConnection( $this->_db['oauth'] );
        $capsule->addConnection( $this->_db['analytics'], 'analytics' );
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
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

    /**
     * Gets the user_id of an usertoken
     *
     * @return int
     */
    public function check ( &$usertoken )
    {
        // Connect with the oauth database
        $this->_connect_oauth();

        // Get user id of the usertoken
        return 1;
    }

    /**
     * Requests to retrieve the password for the account
     *
     * @return array
     */
    public function retrieve ()
    {
        $email = $this->_params['email'];

        if ( !filter_var( filter_var( $email, FILTER_SANITIZE_EMAIL ), FILTER_VALIDATE_EMAIL ) )
            return array( 'success'=>false, 'message'=>'Email address is not valid.' );

        // Try to connect database = if an error occurs, it will return an array, nothing otherwise
        if ( $connect = $this->_connect_oauth() )
            return $connect;

        // Check if an user has been created for the email
        $user = User::where('user_email', '=', $this->_params['email'])->first();
        if ( is_object( $user ) )
        {
            // Create change password token and store in db
            if ( $user->user_password_change_token )
            {
                $token = $user->user_password_change_token;
            }
            else
            {
                $token = sha1( $this->_params['email'].time() );
                $user->user_password_change_token = $token;
                $user->save();
            }

            // Create email
            $mail = new \Ieru\Ieruapis\Organic\PHPMailer();
            $mail->WordWrap = 50;
            $mail->IsHTML( true );

            $mail->From = 'no-reply@organic-edunet.eu';
            $mail->FromName = 'Organic.Edunet';
            $mail->AddAddress( $email );  // Add a recipient
            $mail->AddReplyTo('no-reply@organic-edunet.eu', 'Information');
            $mail->AddBCC('david.banos@uah.es');

            $mail->Subject = '[Organic.Edunet] Retrieve account password';
            $mail->Body    = '<p>You have requested to retrieve your account\'s password. If you click the following link, a new password will be generated and sent back to you.</p><p>If you have not requested to change your password, please ignore this email.</p><a href="http://organic-edunet.eu/en/#/user/password/change/'.$token.'">http://organic-edunet.eu/en/#/user/password/change/'.$token.'</a>';
            $mail->AltBody = "You have requested to retrieve your account\'s password. If you click the following link, a new password will be generated and sent back to you.\n\nIf you have not requested to change your password, please ignore this email.\n\nhttp://organic-edunet.eu/en/#/user/password/change/".$token;

            $mail->Send();
        }

        return array( 'success'=>true, 'message'=>'If an user is linked to the specified email, an email has been sent to your account with instructions for retrieving your password. Otherwise, no email will be sent.' );
    }

    /**
     * Accept the password change, send new password to user
     *
     * @return array
     */
    public function retrieve_accepted ()
    {
        if ( !$this->_params['token'] )
            return array( 'success'=>false, 'message'=>'Invalid token for password change.' );

        // Try to connect database = if an error occurs, it will return an array, nothing otherwise
        if ( $connect = $this->_connect_oauth() )
            return $connect;

        $user = User::where('user_password_change_token', '=', $this->_params['token'])->first();
        if ( is_object( $user ) )
        {
            $new_password = $this->_random_password();
            $user->user_password = $this->_hash_password( $new_password );
            $user->user_password_change_token = '';
            $user->save();

            // Create email
            $mail = new \Ieru\Ieruapis\Organic\PHPMailer();
            $mail->WordWrap = 50;
            $mail->IsHTML( true );

            $mail->From = 'no-reply@organic-edunet.eu';
            $mail->FromName = 'Organic.Edunet';
            $mail->AddAddress( $email );  // Add a recipient
            $mail->AddReplyTo('no-reply@organic-edunet.eu', 'Information');
            $mail->AddBCC('david.banos@uah.es');

            $mail->Subject = '[Organic.Edunet] Account password changed';
            $mail->Body    = '<p>Your account password has been changed to username "'.$user->user_username.'": '.$new_password.'</p>';
            $mail->AltBody = "Your account password has been changed to '".$user->user_username."': ".$new_password;

            $mail->Send();
        }
        else
        {
            return array( 'success'=>false, 'message'=>'Incorrect token for password change.' );
        }

        return array( 'success'=>true, 'message'=>'Password changed and mail sent.' );
    }

    private function _random_password () 
    {
        $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
        $pass = array();
        $alphaLength = strlen( $alphabet ) - 1;
        for ( $i = 0; $i < 16; $i++ ) 
        {
            $n = rand( 0, $alphaLength );
            $pass[] = $alphabet[$n];
        }
        return implode( $pass ); //turn the array into a string
    }
}