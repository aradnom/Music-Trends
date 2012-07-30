<?php
// Includes ////////////////////////////////////////////////////////////////
require_once( "inc/LastFM.class.php" );
require_once( "inc/MongoMe.class.php" );

// Constants ///////////////////////////////////////////////////////////////
define( "API_KEY", 			"32667bdca73f194edf066b43a44fa116");
define( "SECRET",			"7e136836bc7597366e753b75b4d07160" );
define( "FORMAT",			"json" );
define( "USER",				"aradnom" );	

// Opening variables ///////////////////////////////////////////////////////
$lastfm = new LastFM( API_KEY, FORMAT, USER );
$mongo = new MongoMe( "cs457", "topTracks" );
$mongo->setReturnAsArray( true );

// Fetch API info.  This stuff is slow so don't grab too much at once. You can process it
// a lot faster once it's in the database.

//setTopUserScore();

//getChartLovedTracks();
//getPopularTrackCount();
//rankTracks();
getUserCharts( 1000, 'loved', 'subUsers', 'lovedChart' );
//getTopTracks( 10 );
//getTopUsers(10);
//getUserNeighbors();
//setUsers();
//getNeighbors();

function setTopUserScore () {
	global $lastfm, $mongo;
	
	$mongo->setCollectionName( "topUsers" );
	
	$users = $mongo->getAll();
	
	foreach ( $users as $user ) {
		echo "User: " . $user['name'] . "<br />&nbsp;&nbsp;&nbsp;&nbsp;";
		
		// Rank of top track	
		
		echo "Top track: " . $user['topTrack']['artist'] . " - " . 
			$user['topTrack']['name'] . " - rank " . 
			getSongRank( "topTracks", $user['topTrack']['name'] ) . "<br />";		
		
		// Rank of user songs in top chart, normalized for n
		if ( $user['chart'] )
			foreach ( $user['chart'] as $chartTrack ) {
				$rank = getSongRank( "topTracks", $chartTrack['name'] );
				if ( $rank )
					echo $rank . "<br />";
			}
		
		// Rank of loved songs compared to top songs
		
		
		echo "<br />";	
	}		
}

function getSongRank ( $collection, $name ) {
	global $lastfm, $mongo;
	
	$mongo->setCollectionName( $collection );
	
	$track = $mongo->findOne( array( 'name' => $name ) );
	
	return $track['rank'];
}

function getChartLovedTracks () {
	global $lastfm, $mongo;
	
	$mongo->setCollectionName( "topLovedTracks" );
	
	$rank = 1;

	foreach ( $lovedtracks->tracks->track as $track ) {
		$track->rank = $rank;
		$mongo->insert( $track );
		$rank++;		
	}
}

function getPopularTrackCount () {
	$topTracks = $mongo->getAll();

	$mongo->setCollectionName( "topUsers" );
	
	$topUsers = $mongo->getAll();
	
	foreach ( $topUsers as $user ) {
		$popularCount =  0;
		
		echo $user['name'] . "<br />";
		
		foreach ( $user['chart'] as $chartTrack ) {
			//echo $chartTrack['name'] . "<br />";
			foreach ( $topTracks as $topTrack ) {
				if ( $chartTrack['name'] == $topTrack['name'] )
					$popularCount++;	
			}
		}
		
		$mongo->update( $user['_id'], null, array( "topTrackCount" => $popularCount ) );
		
		echo "count: " . $popularCount . "<br />";
	}
}

function getTopTracks ( $page = 1 ) {
	global $lastfm, $mongo;
	
	$topTracks = $lastfm->getChartTopTracks( 10, $page ); 
	foreach ( $topTracks->tracks->track as $track ) {
		echo $track->artist->name . " - " . $track->name . "<br />";
		$mongo->insert( $track );		
	}	
}

function getTopUsers ( $page = 1 ) {
	global $lastfm, $mongo;
	
	$tracks = $mongo->getAll();
	$tracksWithFans = array();
	
	//print_r( $tracks );
	
	//foreach ( $tracks as $track ) {
		//echo $track['_id'];
	//}
	
	for ( $i = ($page - 1) * 10; $i < ($page * 10); $i++ ) {
		//echo $tracks[$i]['artist']['name'];
		$topUsers = $lastfm->getTrackFans( $tracks[$i]['artist']['name'], $tracks[$i]['name'] );		
		
		foreach ( $topUsers as $row ) {
			if ( isset( $row->user ) )
				$mongo->update( $tracks[$i]['_id'], null, array( "topUsers" => $row->user ) );
				//$tracksWithFans[] = $row->user;
		}		
	}
		
	//print_r( $tracksWithFans );
	
	//foreach ( $topUsers->topfans->user as $user ) {
		//echo "\t" . $user->name . "<br />";	
	//}
}

function getUserNeighbors () {
	global $lastfm, $mongo;
	
	print_r( $lastfm->getUserNeighbours( "pamerkat" ) );
}

function setUsers () {
	global $lastfm, $mongo;
	
	$tracks = $mongo->getAll();
	
	$mongo->setCollectionName( "users" );
	
	foreach ( $tracks as $track ) {
		if ( isset( $track['topUsers'] ) ) {
			/*foreach ( $track['topUsers'] as $user ) {
				if ( isset( $user['name'] ) ) {
					//echo $user['name'];
					$user['topTrack']['name'] = $track['name'];
					$user['topTrack']['artist'] = $track['artist']['name'];
					$user['topTrack']['weight'] = $user['weight'];
					$mongo->insert( $user );		
					print_r( $user );
				}
			}*/
			
			/*if ( isset( $track['topUsers']['name'] ) ) {
				//echo $track['topUsers']['name'];
				$track['topUsers']['topTrack']['name'] = $track['name'];
				$track['topUsers']['topTrack']['artist'] = $track['artist']['name'];
				$track['topUsers']['topTrack']['weight'] = $track['topUsers']['weight'];
				$mongo->insert( $track['topUsers'] );		
				print_r( $track );				
			}*/
		}
		
		//print_r( $track );
	}
}

function getNeighbors () {
	global $lastfm, $mongo;
	
	//$mongo->delete( null, array( 'level' => 2 ) ); // Useful if you fuck up while populating

	$users = $mongo->getAll();
	
	$mongo->setCollectionName( "subUsers" );
	
	foreach ( array_slice( $users, 175, 5 ) as $user ) {
		//echo $user['name'] . "<br />";
		
		$neighbors = $lastfm->getUserNeighbours( $user['name'], 50 );	
		
		foreach ( $neighbors->neighbours->user as $subuser ) {
			$subuser->level = 2;
			$subuser->parent = $user['name'];		
			//$mongo->insert( $subuser );
			//print_r( $subuser );
		}
	}	
}

function getUserCharts ( $userNum = 1, $type = 'top', $collection = 'topUsers', $chartName = 'chart' ) {
	global $lastfm, $mongo;
	
	$mongo->setCollectionName( $collection );

	$total = 0;
	
	// For reference, 1000 records == ~1200 seconds
	for ( $i = 0; $i < $userNum; $i++ ) {
		$startTime = time();
		
		$user = $mongo->findOne( array( $chartName => array( '$exists' => false ) ) );
		
		$chart = ( $type == 'top' ) ? 
			$lastfm->getUserTopTracks( $user['name'], "6month", 50 ) : 
			$lastfm->getUserLovedTracks( $user['name'] );
		
		$type == 'top' ? 
			$mongo->update( $user['_id'], null, array( $chartName => $chart->toptracks->track ) ) :
			$mongo->update( $user['_id'], null, array( $chartName => $chart->lovedtracks->track ) );
		
		echo $user['name'] . " processed" . "<br />";
		
		$total += ( time() - $startTime );	
		
		set_time_limit( 30 );
	}
	
	echo ( $total . " seconds" );	
}

function rankTracks () {
	global $lastfm, $mongo;
	
	$mongo->setCollectionName( "topTracks" );
	
	$tracks = $mongo->getAll();
	
	// Not an error, dreamweaver just thinks it is
	usort( $tracks, function ( $first, $second ) {
		return $first['playcount'] < $second['playcount'] ? 2 : -1;
	});
	ksort( $tracks );
	
	foreach ( $tracks as $index => $track ) {
		echo $index . ". " . $track['artist']['name'] . 
			" - " . $track['name'] . " : " . $track['playcount'] . 
			"<br />";
			
		//$mongo->update( $track['_id'], null, array( 'rank' => $index + 1 ) );	
	}	
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>PANTS</title>
</head>

<body>



</body>
</html>