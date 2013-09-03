<?php
/**
 * @copyright Copyright (C) 1999-2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 * @package kernel
 */

/**
 * DFS/MySQLi cluster gateway
 */
class ezpDfsMySQLiClusterGateway extends ezpClusterGateway
{
    protected $port = 3306;

    public function connect()
    {
        if ( !$this->db = mysqli_connect( $this->host, $this->user, $this->password, $this->name, $this->port ) )
            throw new RuntimeException( "Failed connecting to the MySQL database " .
                "(error #". mysqli_errno( $this->db ).": " . mysqli_error( $this->db ) );

        if ( !mysqli_set_charset( $this->db, $this->charset ) )
            throw new RuntimeException( "Failed to set database charset to '$this->charset' " .
                "(error #". mysqli_errno( $this->db ).": " . mysqli_error( $this->db ) );
    }

    /**
     * Returns the database table name to use for the specified file.
     *
     * For files detected as cache files the cache table is returned, if not
     * the generic table is returned.
     *
     *
     * @param string $filePath
     * @return string The database table name
     */
     protected function dbTable( $filePath )
     {
         // If no custom table is defined, we can just return the default table
         if ( !defined( 'CLUSTER_METADATA_TABLE_CACHE' ) )
             return 'ezdfsfile';

         $cacheDir = defined( 'CLUSTER_METADATA_CACHE_PATH' ) ? CLUSTER_METADATA_CACHE_PATH : "/cache/";
         $storageDir = defined( 'CLUSTER_METADATA_STORAGE_PATH' ) ? CLUSTER_METADATA_STORAGE_PATH : "/storage/";

         if ( strpos( $filePath, $cacheDir ) !== false && strpos( $filePath, $storageDir ) === false )
         {
             return CLUSTER_METADATA_TABLE_CACHE;
         }

         return 'ezdfsfile';
     }

    public function fetchFileMetadata( $filepath )
    {
        $filePathHash = md5( $filepath );
        $sql = "SELECT `datatype`, `size`, `mtime` FROM " . $this->dbTable( $filepath ) . " WHERE name_hash='{$filePathHash}'" ;
        if ( !$res = mysqli_query( $this->db, $sql ) )
            throw new RuntimeException( "Failed to fetch file metadata for '$filepath' " .
                "(error #". mysqli_errno( $this->db ).": " . mysqli_error( $this->db ) );

        if ( mysqli_num_rows( $res ) == 0 )
        {
            return false;
        }

        $metadata = mysqli_fetch_assoc( $res );
        mysqli_free_result( $res );
        return $metadata;
    }

    public function passthrough( $filepath, $filesize, $offset = false, $length = false )
    {
        $dfsFilePath = CLUSTER_MOUNT_POINT_PATH . '/' . $filepath;

        if ( !file_exists( $dfsFilePath ) )
            throw new RuntimeException( "Unable to open DFS file '$dfsFilePath'" );

        $fp = fopen( $dfsFilePath, 'rb' );
        if ( $offset !== false && @fseek( $fp, $offset ) === -1 )
            throw new RuntimeException( "Failed to seek offset $offset on file '$filepath'" );
        if ( $offset === false && $length === false )
            fpassthru( $fp );
        else
            echo fread( $fp, $length );

        fclose( $fp );
    }

    public function close()
    {
        mysqli_close( $this->db );
        unset( $this->db );
    }
}

ezpClusterGateway::setGatewayClass( 'ezpDfsMySQLiClusterGateway' );