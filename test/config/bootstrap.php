<?php
use ActivityPub\ActivityPub;

$dbPath = dirname( __FILE__ ) . '/../db.sqlite';
if ( file_exists( $dbPath ) ) {
    unlink( $dbPath );
}
$activityPub = new ActivityPub( array(
    'dbOptions' => array(
        'driver' => 'pdo_sqlite',
        'path' => $dbPath,
    ),
) );
$activityPub->updateSchema();
?>
