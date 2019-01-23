<?php
namespace ActivityPub\Test;

use ActivityPub\ActivityPub;
use ActivityPub\Config\ActivityPubConfig;

$dbPath = dirname( __FILE__ ) . '/../db.sqlite';
if ( file_exists( $dbPath ) ) {
    unlink( $dbPath );
}
$config = ActivityPubConfig::createBuilder()
    ->setDbConnectionParams( array(
        'driver' => 'pdo_sqlite',
        'path' => $dbPath,
    ) )
    ->build();
$activityPub = new ActivityPub( $config );
$activityPub->updateSchema();
?>
