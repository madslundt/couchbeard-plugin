<?php
/* ==========================================================================
   General
   ========================================================================== */
function connectionStatusFunction()
{
    ////check_ajax_referer( CouchBeardPlugin::KEY, 'security' );
    echo json_encode(couchbeard::isAnyAlive());
    exit();
}
add_action('wp_ajax_connectionStatus', 'connectionStatusFunction'); // Only logged in users
add_action('wp_ajax_nopriv_connectionStatus', 'connectionStatusFunction');


function getReleaseDate($releases) {
	foreach ($releases as $val) {
		if ($val) {
			return date(get_option('date_format') . ' ' . get_option('time_format'), $val);
		}
	}
	return false;
}



/* ==========================================================================
   IMDB
   ========================================================================== */
function imdb_movieInfoFunction()
{
    //check_ajax_referer( CouchBeardPlugin::KEY, 'security' );
    $movie = new imdbAPI($_POST['imdb']);
    echo json_encode($movie->getData());
    exit();
}
add_action('wp_ajax_imdb_movieInfo', 'imdb_movieInfoFunction');  // Only logged in users

function imdbGetReleaseFunction()
{
	//check_ajax_referer( CouchBeardPlugin::KEY, 'security' );
    try {
    	$imdb = new imdbAPI($_POST['id']);
    } catch (Exception $e) {
    	exit();
    }
    
    echo $imdb->released();
    exit();
}
add_action('wp_ajax_imdbGetRelease', 'imdbGetReleaseFunction'); // Only logged in users
?>