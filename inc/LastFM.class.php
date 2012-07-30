<?php
class LastFM {
	// Constants ///////////////////////////////////////////
	const API_ROOT = "http://ws.audioscrobbler.com/2.0/?method="; // Should be the same for all requests
	
	private $api_key;
	private $format; // Can be either json or xml, json is preferred
	private $urls = array(
		'ChartTopTracks' => 'chart.gettoptracks',
		'ChartTopArtists' => 'chart.gettopartists',
		'ChartGetLovedTracks' => 'chart.getlovedtracks',
		'TrackTopFans' => 'track.gettopfans',
		'TrackInfo' => 'track.getinfo',
		'UserTopTracks' => 'user.gettoptracks',
		'UserGetLovedTracks' => 'user.getlovedtracks',
		'UserWeeklyChart' => 'user.getweeklytrackchart',
		'AlbumGetInfo' => 'album.getinfo',
		'ArtistTopTracks' => 'artist.gettoptracks',
		'UserNeighbours' => 'user.getneighbours',
		'UserGetNewReleases' => 'user.getnewreleases',
		'UserGetRecentTracks' => 'user.getrecenttracks'
	);	
	
	public function __construct ( $api_key, $format = "json", $user = null ) {
		$this->api_key = $api_key;
		$this->format = $format;
	}
	
	public function getChartTopTracks ( $limit = 10, $page = 1 ) {
		if ( $this->api_key ) {
			$url = 
				self::API_ROOT . $this->urls['ChartTopTracks'] . 
				"&api_key=" . $this->api_key . 
				"&limit=" . $limit .
				"&page=" . $page;
				
			return $this->getResults( $url );
		}
	}
	
	public function getChartTopArtists ( $limit = 50 ) {
		if ( $this->api_key ) {
			$url = 
				self::API_ROOT . $this->urls['ChartTopArtists'] . 
				"&api_key=" . $this->api_key . 
				"&limit=" . $limit;
				
			return $this->getResults( $url );
		}
	}
	
	public function getChartLovedTracks ( $limit = 100 ) {
		if ( $this->api_key ) {
			$url = 
				self::API_ROOT . $this->urls['ChartGetLovedTracks'] . 
				"&api_key=" . $this->api_key . 
				"&limit=" . $limit;
				
			return $this->getResults( $url );
		}
	}
	
	// Use mbid if available (more precise) but will use name/artist if supplied.  All 3 required because
	// it won't accept just the mbid like it says it will
	public function getTrackFans ( $name, $artist, $mbid = null ) {
		if ( $this->api_key ) {
			$url = 
				self::API_ROOT . $this->urls['TrackTopFans'] . 
				"&api_key=" . $this->api_key . 
				"&track=" . urlencode( $name ) . 
				"&artist=" . urlencode( $artist ) . 
				"&mbid=" . $mbid;
				
			return $this->getResults( $url );
		}
	}
	
	// Same rules as above
	public function getTrackInfo ( $name, $artist, $mbid = null ) {
		if ( $this->api_key ) {
			$url = 
				self::API_ROOT . $this->urls['TrackInfo'] . 
				"&api_key=" . $this->api_key . 
				"&track=" . urlencode( $name ) . 
				"&artist=" . urlencode( $artist ) . 
				"&mbid=" . $mbid .
				"&format=" . $this->format;
				
			return $this->getResults( $url );
		}
	}
	
	// Valid periods: overall | 7day | 3month | 6month | 12month
	public function getUserTopTracks ( $username, $period = 'overall', $limit = 50 ) {
		if ( $this->api_key ) {
			$url = 
				self::API_ROOT . $this->urls['UserTopTracks'] . 
				"&api_key=" . $this->api_key . 
				"&user=" . $username .
				"&period=" . $period .
				"&limit=" . $limit	;
				
			return $this->getResults( $url );
		}
	}	
	
	// No periods because it's always global
	public function getUserLovedTracks ( $username, $limit = 50 ) {
		if ( $this->api_key ) {
			$url = 
				self::API_ROOT . $this->urls['UserGetLovedTracks'] . 
				"&api_key=" . $this->api_key . 
				"&user=" . $username .
				"&limit=" . $limit	;
				
			return $this->getResults( $url );
		}
	}
	
	public function getAlbumInfo ( $album, $artist, $mbid = null ) {
		if ( $this->api_key ) {	
			$url = 
				self::API_ROOT . $this->urls['AlbumGetInfo'] . 
				"&api_key=" . $this->api_key . 
				"&mbid=" . $mbid .
				"&album=" . urlencode( $album ) .
				"&artist=" . urlencode( $artist );
				
			return $this->getResults( $url );
		}
	}
	
	// Artist name is preferred, mbid is more precise
	public function getArtistTopTracks ( $artist, $mbid = null ) {
		if ( $this->api_key ) {	
			$url = 
				self::API_ROOT . $this->urls['AlbumGetInfo'] . 
				"&api_key=" . $this->api_key . 
				"&mbid=" . $mbid .
				"&artist=" . urlencode( $artist );
				
			return $this->getResults( $url );
		}
	}
	
	// Artist name is preferred, mbid is more precise
	public function getUserNeighbours ( $user, $limit = 50 ) {
		if ( $this->api_key ) {	
			$url = 
				self::API_ROOT . $this->urls['UserNeighbours'] . 
				"&api_key=" . $this->api_key . 
				"&user=" . $user .
				"&limit=" . $limit;
				
			return $this->getResults( $url );
		}
	}
	
	// Pulls down weekly user listen chart
	public function getUserWeeklyTrackChart ( $user ) {
		if ( $this->api_key ) {	
			$url = 
				self::API_ROOT . $this->urls['UserWeeklyChart'] . 
				"&api_key=" . $this->api_key . 
				"&user=" . $user;
				
			return $this->getResults( $url );
		}
	}
	
	// Pulls down user new releases
	public function getUserNewReleases ( $user ) {
		if ( $this->api_key ) {	
			$url = 
				self::API_ROOT . $this->urls['UserGetNewReleases'] . 
				"&api_key=" . $this->api_key .
				"&user=" . $user;
				
			return $this->getResults( $url );
		}
	}
	
	// Pull down user recent tracks
	public function getUserRecentTracks ( $user, $limit = 50 ) {
		if ( $this->api_key ) {	
			$url = 
				self::API_ROOT . $this->urls['UserGetRecentTracks'] . 
				"&api_key=" . $this->api_key .
				"&user=" . $user .
				"&limit=" . $limit;
				
			return $this->getResults( $url );
		}
	}
	
	// Private functions ///////////////////////////////////////////////////////////
	
	private function getResults ( $url ) {
		if ( $url ) {
			if ( $this->format == "json" )
				return json_decode( file_get_contents( $url . "&format=json" ) );
			else
				return simplexml_load_file(	$url );
		}
	}
}
?>