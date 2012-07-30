<?php
// Includes ////////////////////////////////////////////////////////////////
require_once( "inc/LastFM.class.php" );
require_once( "inc/MongoMe.class.php" );

// Constants ///////////////////////////////////////////////////////////////
define( "API_KEY", 			"32667bdca73f194edf066b43a44fa116");
define( "SECRET",			"7e136836bc7597366e753b75b4d07160" );
define( "FORMAT",			"json" );
define( "USER",				"aradnom" );	
define( "SUMMATION", 		4.499205338 ); // The summation of 1/n from 1 to 50, used in weight calculation

// Opening variables ///////////////////////////////////////////////////////
$lastfm = new LastFM( API_KEY, FORMAT, USER );
$mongo = new MongoMe( "cs457", "topTracks" );
$mongo->setReturnAsArray( true );

$tracks = $mongo->getAll();

usort( $tracks, function ( $first, $second ) {
	return $first['calculatedScore'] < $second['calculatedScore'] ? 2 : -1;
});

function getSongScores () {
	$mongo->setCollectionName( "topTracks" );
	$topTracks = $mongo->find( array( 'calculatedScore' => null ) );
	$trackSlice = array_slice( $topTracks, 0, 5 );
	
	foreach ( $trackSlice as $track ) {
		$name = $track['name'];
		$artist = $track['artist']['name'];
		
		echo $artist . " - " . $name . ": <br />";
		
		$trackScore = getSongScore( $artist, $name );
			
		echo  "score: " . $trackScore . "<br />";
		
		$mongo->setCollectionName( "topTracks" );
		
		$mongo->update( $track['_id'], null, array( "calculatedScore" => $trackScore ) );
	}	
}

function getSongScore ( $artist, $name ) {
	global $mongo, $lastfm;
	$totalScore = 0;	
	
	$mongo->setReturnAsArray( false );
	
	// Make sure there's no mismatch because of case
	$name = trim( strtolower( $name ) );
	$artist = trim( strtolower( $artist ) );
	
	// Process top users first
	
	$mongo->setCollectionName( "topUsers" );
	
	$users = $mongo->find( array( 'recentTracks' => array( '$ne' => null ) ), array( "name" => 1, "recentTracks" => 1, "score" => 1 ) );
	
	foreach ( $users as $user ) {
		$totalScore += scoreUserTracks( $user, $name, $artist );			
			
		set_time_limit( 30 );		
	}
	
	// Then subusers
	
	$mongo->setCollectionName( "subUsers" );
	
	//$mongo->getAll( array( "name" => 1, "recentTracks" => 1, "score" => 1 ) );
	
	$users = $mongo->find( 
		array( 
			'recentTracks' => array( '$ne' => null ), 'score.total' => array( '$ne' => 0 ) 
			), 
		array( 
			"name" => 1, "recentTracks" => 1, "score" => 1 
			));
	
	foreach ( $users as $user ) {
		$totalScore += scoreUserTracks( $user, $name, $artist );			
			
		set_time_limit( 30 );		
	}

	return $totalScore;
}

function scoreUserTracks ( $user, $name, $artist ) {
	$found = false;
	$score = 0;

	foreach ( $user['recentTracks'] as $track ) {
		$userTrackName = trim( strtolower( $track['name'] ) );
		
		foreach ( $track['artist'] as $k => $v ) {
			if ( $k != 'mbid' )
				$userArtistName = trim( strtolower( $v ) );
		}
		
		//echo $user['name'] . ": " . $userTrackName . " - " . $userArtistName . "<br />";
		
		// Yes, flipped.  Thanks last.fm, stellar work there
		if ( $name == $userTrackName && $artist == $userArtistName && !$found ) {	
			echo $user['name'] . ": " . $user['score']['total'] . "<br />";
			$score = $user['score']['total'];	
			$found = true;
		} elseif ( $name == $userArtistName && $artist == $userTrackName && !$found ) {
			echo $user['name'] . ": " . $user['score']['total'] . "<br />";
			$score = $user['score']['total'];	
			$found = true;
		}				
	}
	
	return $score;
}

function getUserRecentTracks ( $limit ) {
	global $mongo, $lastfm;
	
	$mongo->setCollectionName( "subUsers" );
	
	for ( $i = 0; $i < $limit; $i++ ) {
		$user = $mongo->findOne( array( 'recentTracks' => null ) );
				
		$tracks = $lastfm->getUserRecentTracks( $user['name'] );
		
		$mongo->update( $user['_id'], null, array( 'recentTracks' => $tracks->recenttracks->track ) );		
			
		set_time_limit( 30 );		
	}
}

function getUserChart ( $user ) {
	global $lastfm, $mongo;
	 
	return $lastfm->getUserTopTracks( $user['name'], "6month", 50 );
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>PANTS</title>
<link href="fonts.css" rel="stylesheet" type="text/css" />
<style type="text/css">
body {
	font-family: YanoneKaffeesatzRegular;	
	font-size: 32px;
}

div {
	position: relative;
}

.track {
	width: 600px;	
	height: 50px;
	background-color: #ddd;
	padding: 5px 5px 5px 10px;
	margin: 5px;
}
.track div {
	display: inline-block;	
}

.name {
	font-family: YanoneKaffeesatzBold;	
}

.my-score, .their-score {
	position: absolute;	
}

.my-score {
	right: 15px;
}

.their-score {
	right: -40px;
}
</style>
</head>

<body>

<?php foreach ( $tracks as $track ) { ?>
	<div class="track">
    	<div class="name"><?php echo $track['artist']['name'] . " - " . $track['name']; ?></div>
        <div class="my-score"><?php echo number_format( $track['calculatedScore'], 4 ); ?></div>
        <div class="their-score"><?php echo $track['rank']; ?></div>
    </div>
<?php } ?>

</body>
</html>