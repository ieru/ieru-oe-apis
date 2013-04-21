<?php
/** 
 * Connects with a IEEE LOM database
 *
 * @package     Organic API
 * @version     1.1 - 2013-04-04 | 1.0 - 2012-10-15
 * 
 * @author      David Baños Expósito
 */

namespace Ieru\Ieruapis\Organic;

class LOMDatabase
{
    /**
     * Constructor
     *
     * @param   array   $con    [ hostname, database, user, password ] <-- specific order for it to work
     * @return  object | null
     */
    public static function & get_db ( $con )
    {
        try 
        {
            // Esto debe ir a una clase propia dentro del paquete API (directorio /organicapi/api)
            $db = new \PDO( 'mysql:host='.$con['host'].';dbname='.$con['database'], $con['username'], $con['password'] );
            $db->exec( 'SET NAMES `utf8`' );
            $db->exec( 'SET SQL_BIG_SELECTS=1' );
        } 
        catch ( \Exception $e ) 
        {
            throw new \APIException( 'Could not connect to the database.' );
        }
        return $db;
    }
}