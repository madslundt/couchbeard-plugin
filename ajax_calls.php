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
   Couchpotato
   ========================================================================== */
function cp_addMovieFunction()
{
    //check_ajax_referer( CouchBeardPlugin::KEY, 'security' );
    try {
    	$cp = new couchpotato();
    } catch (Exception $e) {
    	exit();
    }
    
    echo (bool) $cp->addMovie($_POST['id']);
    exit();
}
add_action('wp_ajax_cp_addMovie', 'cp_addMovieFunction');  // Only logged in users

function cp_getMoviesFunction()
{
    //check_ajax_referer( CouchBeardPlugin::KEY, 'security' );
    try {
    	$cp = new couchpotato();
    } catch (Exception $e) {
    	exit();
    }
    echo json_encode($cp->getMovies());
    exit();
}
add_action('wp_ajax_cp_getMovies', 'cp_getMoviesFunction');  // Only logged in users
add_action('wp_ajax_nopriv_cp_getMovies', 'cp_getMoviesFunction');

function cp_getMovieFunction() {
	//check_ajax_referer( CouchBeardPlugin::KEY, 'security' );
    try {
    	$cp = new couchpotato();
    } catch (Exception $e) {
    	exit();
    }
    echo json_encode($cp->getMovie($_POST['id']));
    exit();
}
add_action('wp_ajax_cp_getMovie', 'cp_getMovieFunction');  // Only logged in users
add_action('wp_ajax_nopriv_cp_getMovie', 'cp_getMovieFunction');

function cp_getMovies_templateFunction()
{
    try {
		$cp = new couchpotato();
		$movies = $cp->getMovies();
		if ($movies):
		?>
		<div class="list-group couchpotato">
			<?php foreach ($movies as $k => $m): ?>
				<?php 
					$date = getReleaseDate($m->library->info->release_date);
					$time = ''; 
					if ($date) {
						$time = '<span class="pull-right"><i class="glyphicon glyphicon-time"></i> <time timestamp="' . $date . '" title="' . $date . '">' . human_time_diff( strtotime($date), current_time('timestamp') ) . (strtotime($date) < current_time('timestamp') ? ' ago' : '') . '</time></span>';
					}
				?>
			  	<a data-toggle="modal" data-target="#movieModal" href="#<?php echo $m->library->info->imdb; ?>" class="list-group-item<?php echo $k > 2 ? ' hidden' : ''; ?>" data-imdb="<?php echo $m->library->info->imdb; ?>" data-id="<?php echo $m->library_id; ?>">
					<h4 class="list-group-item-heading"><?php echo $m->library->info->original_title; ?></h4>
					<p class="list-group-item-text"><time timestamp="<?php echo $m->library->info->year; ?>"><?php echo $m->library->info->year; ?></time><?php echo $time; ?></p>
			  	</a>
			<?php endforeach; ?>
			<a href="#more" class="list-group-item loadmore"><center><p class="lead nomargin"><?php _e('Load more...', 'madslundt'); ?></p></center></a>
		</div>
		<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" id="movieModal" aria-labelledby="movieModal" aria-hidden="true">
			<div class="modal-dialog modal-lg">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
						<h4 class="modal-title" id="myModalLabel"></h4>
					</div>
					<div class="modal-body">
						
					</div>
					<div class="modal-footer">
						<a class="pull-left js_refresh" href="#" title="<?php _e('Refresh movie', 'couchbeard'); ?>"><i class="glyphicon glyphicon-refresh"></i></a>
						<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
						<button type="button" class="btn btn-primary">Save changes</button>
					</div>
				</div>
			</div>
		</div>
		<?php 
			endif;
	} catch (Exception $e) {
		_e('CouchPotato is not online.', 'madslundt');
	}
    exit();
}
add_action('wp_ajax_cp_getMovies_template', 'cp_getMovies_templateFunction');  // Only logged in users
add_action('wp_ajax_nopriv_cp_getMovies_template', 'cp_getMovies_templateFunction');


/* ==========================================================================
   Sickbeard
   ========================================================================== */
function sb_addTVFunction()
{
    //check_ajax_referer( CouchBeardPlugin::KEY, 'security' );
    try {
    	$sb = new sickbeard();
    } catch (Exception $e) {
    	exit();
    }
    
    echo (bool) $sb->addShow($_POST['id']);
    exit();
}
add_action('wp_ajax_sb_addTV', 'sb_addTVFunction');  // Only logged in users


function sb_getTV_templateFunction()
{
    try {
		$sb = new sickbeard();
		$history = $sb->getHistory('downloaded');
		$shows = $sb->getFuture();
		if ($shows || $history): ?>
			<div class="panel-group" id="accordion">
			<?php if ($history): ?>
				<div class="panel panel-default">
				    <div class="panel-heading">
				    	<a class="nolink" data-toggle="collapse" data-parent="#accordion" href="#sb_downloaded">
					    	<h3 class="panel-title">
			          			<?php _e('Downloaded', 'madslundt'); ?>
					      	</h3>
				      	</a>
				    </div>
			    	<div id="sb_downloaded" class="panel-collapse collapse in">
						<div class="list-group sickbeard">
						<?php foreach ($history as $k => $s): ?>
						  	<a href="#" class="list-group-item<?php echo $k > 2 ? ' hidden' : ''; ?>">
								<h4 class="list-group-item-heading"><?php echo $s->show_name ?></h4>
								<p class="list-group-item-text"><?php echo 's' . sprintf('%02s', $s->season) . 'e' . sprintf('%02s', $s->episode); ?>
									<span class="pull-right"><i class="glyphicon glyphicon-time"></i>
										<time datetime="<?php echo $s->date; ?>" title="<?php echo $s->date; ?>">
											<?php echo human_time_diff( strtotime($s->date . ' +1 day'), current_time('timestamp') ) . ' ago'; ?>
										</time>
									</span>
								</p>
						  	</a>
						<?php endforeach; ?>
							<a href="#more" class="list-group-item loadmore"><center><p class="lead nomargin"><?php _e('Load more...', 'madslundt'); ?></p></center></a>
						</div>
					</div>
				</div>
			<?php
			endif;
			if ($shows):
			foreach ($shows as $k => $s): ?>
				<?php if ($s): ?>
				<?php
					if ($k == 'today') {
						$k = 'tomorrow';
					}
				?>
				<div class="panel panel-default">
				    <div class="panel-heading">
				    	<a class="nolink" data-toggle="collapse" data-parent="#accordion" href="#sb_<?php echo $k; ?>">
				      		<h3 class="panel-title">
			          			<?php echo ucfirst($k); ?>
					      	</h3>
				      	</a>
				    </div>
				    <div id="sb_<?php echo $k; ?>" class="panel-collapse collapse">
							<div class="list-group sickbeard">
							<?php foreach ($s as $e): ?>
								<a href="#" class="list-group-item">
									<h4 class="list-group-item-heading"><?php echo $e->show_name; ?></h4>
									<p class="list-group-item-text"><?php echo '(s' . sprintf('%02s', $e->season) . 'e' . sprintf('%02s', $e->episode) . '): ' . $e->ep_name; ?> 
										<span class="pull-right"><i class="glyphicon glyphicon-time"></i>  
											<time datetime="<?php echo $e->airdate; ?>" title="<?php echo $e->airdate; ?>">
												<?php echo human_time_diff( strtotime($e->airdate . ' +1 day'), current_time('timestamp') ); ?>
											</time>
										</span>
								</p>
								</a>
							<?php endforeach; ?>
							</div>
					</div>
				</div>
			<?php endif; ?>
			<?php endforeach; ?> 
			<?php endif; ?>
			</div>
		<?php 
		endif;
	} catch (Exception $e) {
		_e('SickBeard is not online.', 'madslundt');
	}
    exit();
}
add_action('wp_ajax_sb_getTV_template', 'sb_getTV_templateFunction');  // Only logged in users
add_action('wp_ajax_nopriv_sb_getTV_template', 'sb_getTV_templateFunction');



/* ==========================================================================
   SabNZBD
   ========================================================================== */
function currentDownloadingFunction() 
{
    //check_ajax_referer( CouchBeardPlugin::KEY, 'security' );
    try {
    	$sab = new sabnzbd();
    } catch (Exception $e) {
    	exit();
    }
    
    echo json_encode($sab->getQueue());
    exit();
}
add_action('wp_ajax_currentDownloading', 'currentDownloadingFunction');  // Only logged in users
add_action('wp_ajax_nopriv_currentDownloading', 'currentDownloadingFunction');



/* ==========================================================================
   XBMC
   ========================================================================== */
function xbmc_sendNotificationFunction() {
    //check_ajax_referer( CouchBeardPlugin::KEY, 'security' );
    try {
    	$xbmc = new xbmc();
    } catch (Exception $e) {
    	exit();
    }
    
    echo (bool) $xbmc->sendNotification('Couch Beard message', $_POST['message']);
    exit();
}
add_action('wp_ajax_xbmcSendNotification', 'xbmc_sendNotificationFunction');  // Only logged in users

function movieXbmcInfoFunction()
{
    //check_ajax_referer( CouchBeardPlugin::KEY, 'security' );
    try {
    	$xbmc = new xbmc();
    } catch (Exception $e) {
    	exit();
    }
    
    echo json_encode($xbmc->getMovieDetails($_POST['movieid']));
    exit();
}
add_action('wp_ajax_movieXbmcInfo', 'movieXbmcInfoFunction');  // Only logged in users

function xbmcPlayMovieFunction()
{
    //check_ajax_referer( CouchBeardPlugin::KEY, 'security' );
    try {
    	$xbmc = new xbmc();
    } catch (Exception $e) {
    	exit();
    }
    
    echo $xbmc->play($_POST['movieid']);
    exit();
}
add_action('wp_ajax_xbmcPlayMovie', 'xbmcPlayMovieFunction');  // Only logged in users

function xbmcPlayPauseFunction()
{
    //check_ajax_referer( CouchBeardPlugin::KEY, 'security' );
    try {
    	$xbmc = new xbmc();
    } catch (Exception $e) {
    	exit();
    }
    
    echo $xbmc->playPause($_POST['player']);
    exit();
}
add_action('wp_ajax_xbmcPlayPause', 'xbmcPlayPauseFunction');  // Only logged in users

function currentPlayingFunction() 
{
    //check_ajax_referer( CouchBeardPlugin::KEY, 'security' );
    try {
    	$xbmc = new xbmc();
    } catch (Exception $e) {
    	exit();
    }
    
    echo json_encode($xbmc->getCurrentPlaying());
    exit();
}
add_action('wp_ajax_currentPlaying', 'currentPlayingFunction');  // Only logged in users

function xbmcPlayerPropsFunction() 
{
    //check_ajax_referer( CouchBeardPlugin::KEY, 'security' );
    try {
    	$xbmc = new xbmc();
    } catch (Exception $e) {
    	exit();
    }
    
    echo $xbmc->getPlayerProperties();
    exit();
}
add_action('wp_ajax_xbmcPlayerProps', 'xbmcPlayerPropsFunction');  // Only logged in users

function xbmcInputActionFunction()
{
    //check_ajax_referer( CouchBeardPlugin::KEY, 'security' );
    try {
    	$xbmc = new xbmc();
    } catch (Exception $e) {
    	exit();
    }
    
    echo $xbmc->inputAction($_POST['input']);
    exit();
}
add_action('wp_ajax_xbmcInputAction', 'xbmcInputActionFunction'); // Only logged in users

function xbmcEjectDriveFunction()
{
    //check_ajax_referer( CouchBeardPlugin::KEY, 'security' );
    try {
    	$xbmc = new xbmc();
    } catch (Exception $e) {
    	exit();
    }
    
    echo $xbmc->ejectDrive();
    exit();
}
add_action('wp_ajax_xbmcEjectDrive', 'xbmcEjectDriveFunction'); // Only logged in users



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