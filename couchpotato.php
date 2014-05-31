<?php
class couchpotato extends couchbeard 
{

	const APP = 'couchpotato';

	protected function setApp() 
	{
		$this->app = self::APP;
		$this->api = parent::getAPI();
	}

	public function __construct() {
		parent::__construct();
		add_action('wp_ajax_cp_addMovie', array(&$this, 'addMovieFunction'));  // Only logged in users
		add_action('wp_ajax_cp_getMovies', array(&$this, 'getMoviesFunction'));  // Only logged in users
		add_action('wp_ajax_nopriv_cp_getMovies', array(&$this, 'getMoviesFunction'));
		add_action('wp_ajax_cp_getMovie_template', array(&$this, 'getMovie_templateFunction'));  // Only logged in users
		add_action('wp_ajax_nopriv_cp_getMovie_template', array(&$this, 'getMovie_templateFunction'));
		add_action('wp_ajax_cp_getMovies_template', array(&$this, 'getMovies_templateFunction'));  // Only logged in users
		add_action('wp_ajax_nopriv_cp_getMovies_template', array(&$this, 'getMovies_templateFunction'));
	}

	/**
	 * Get version of Couchpotato
	 * @return string Version
	 */
	public function version()
	{
        $url = $this->getURL() . '/app.version';
        $json = parent::curl_download($url);
        if (!$json)
            return false;

        $data = json_decode($json);
        return $data->version;
	}

	/**
	 * Get connection status to Couchpotato
	 * @return bool Connection status
	 */
	public function available()
	{		        
		$url = $this->getURL() . '/app.available';
        $json = parent::curl_download($url);
        if (!$json)
            return false;

        $data = json_decode($json);
        return $data->success;
	}

	/**
	 * Add movie to Couchpotato
	 * @param  string $id IMDB movie id
	 * @return bool     Adding status
	 */
	public function addMovie($id)
	{
        $url = $this->getURL() . '/movie.add/?identifier=' . $id;
        $json = parent::curl_download($url);
        if (!$json)
            return false;

        $data = json_decode($json);
        return 1;
        return $data->added;
	}

	/**
	 * Remove movie from wanted list in Couchpotato
	 * @param  int $id Couchpotato id
	 * @return bool     Success
	 */
	public function removeMovie($id)
	{
        $url = $this->getURL() . '/movie.delete/?id=' . $id . '&delete_from=wanted';
        $json = parent::curl_download($url);
        if (!$json)
            return false;

        $data = json_decode($json);
        return $data->success;
	}

	/**
	 * Get all wanted movies in Couchpotato
	 * @return array Movies
	 */
	public function getMovies()
	{
        $url = $this->getURL() . '/movie.list/?status=active';
        $json = parent::curl_download($url);
        if (!$json)
            return false;

        $data = json_decode($json);
        return $data->movies;
	}

	/**
	 * Get a specific movie by id
	 * @return object Movie
	 */
	public function getMovie($id)
	{
        $url = $this->getURL() . '/movie.get/?id=' . $id;
        $json = parent::curl_download($url);
        if (!$json)
            return false;

        $data = json_decode($json);
        return $data->movie;
	}

	/**
	 * Refresh a movie in Couchpotato
	 * @param  int $id Couchpotato id
	 * @return bool     Success
	 */
	public function refreshMovie($id)
	{
        $url = $this->getURL() . '/movie.list/?id=' . $id;
        $json = parent::curl_download($url);
        if (!$json)
            return false;

        $data = json_decode($json);
        return $data->success;
	}

	/**
	 * Looking for updates to Couchpotato
	 * @return bool update available
	 */
	public function update()
	{
        $url = $this->getURL() . '/updater.check';
        $json = parent::curl_download($url);
        if (!$json)
            return false;

        $data = json_decode($json);
        return $data->update_available;
	}

	public function releases($id) {
		$url = $this->getURL() . '/release.for_movie?id=' . $id;
		$json = parent::curl_download($url);
        if (!$json)
            return false;

        $data = json_decode($json);
        return $data->releases;
	}

	/**
	 * Looking for a specific movie in CouchPotato
	 * @param  int $imdb_id IMDb movie ID
	 * @return bool movie found in CouchPotato
	 */
	public function movieWanted($imdb_id)
	{
        $url = $this->getURL() . '/movie.get/?id=' . $imdb_id;
        $json = parent::curl_download($url);
        if (!$json)
            return false;

        $res = json_decode($json);
        if ($res->success)
        {
            if (count($res->movie->releases))
            {
                return false;
            }
            return true;
        }
        return false;
	}

	public function getQuality($quality_id) {
		$url = $this->getURL() . '/quality.list';
        $json = parent::curl_download($url);
        if (!$json)
            return false;
        $res = json_decode($json);
        foreach ($res->list as $i) {
        	if ($i->id == $quality_id) {
        		return $i;
        	}
        }
    	return false;
	}

	private function getReleaseDate($releases) {
		foreach ($releases as $val) {
			if ($val) {
				return date(get_option('date_format') . ' ' . get_option('time_format'), $val);
			}
		}
		return false;
	}


	/* ==========================================================================
	   Couchpotato AJAX calls
	   ========================================================================== */
	public function addMovieFunction()
	{		
		try {    
	    	echo (bool) $this->addMovie($_POST['id']);
    	} catch(Exception $e) {
			_e('Could not find CouchPotato', 'couchbeard');
		}
	    die();
	}


	public function getMoviesFunction()
	{
		try {
	    	echo json_encode($this->getMovies());
	    } catch(Exception $e) {
			_e('Could not find CouchPotato', 'couchbeard');
		}
	    die();
	}


	public function getMovie_templateFunction() {
		//check_ajax_referer( CouchBeardPlugin::KEY, 'security' );
	    try {
	    	$movie = $this->getMovie($_POST['id']);
	    	$releases = $this->releases($_POST['id']);
	    	?>
	    	<div class="modal-body couchpotato">
				<h1><?php echo $movie->library->info->titles[0]; ?></h1>
				<div class="row">
					<div class="col-xs-12">
						<div class="pull-left">
							<div class="cover" style="background-image: url('<?php echo $movie->library->info->images->poster_original[1]; ?>');"></div>
								<div class="row">
										<center>
											<p class="lead">
											<?php if ($movie->library->info->rating->imdb[0]): ?>
												<span class="quality"><?php echo $movie->library->info->rating->imdb[0]; ?></span>
											<?php endif; ?>
											</p>
										</center>
								</div>
							<?php 
								$types = $movie->profile->types;
								if (isset($types)):
								foreach ($types as $type):
							?>
								<span class="quality"><?php echo $type->quality->label; ?></span>
							<?php endforeach; endif; ?>
							<?php //var_dump($movie); ?>
						</div>
						<div class="row">
							<div class="col-xs-12 col-sm-9">
								<p class="lead"><?php printf( _n( '1 release', '%s releases', count($releases), 'couchbeard' ), count($releases) ); ?></p>
								<?php if (count($releases) > 0): ?>
									<table class="table">
										<thead>
											<tr>
												<th>Release name</th>
												<th>Quality</th>
												<th class="hidden-xs">Size</th>
												<th class="hidden-xs">Score</th>
												<th class="hidden-xs">Provider</th>
												<th>Extra</th>
											</tr>
										</thead>
										<tbody>
											<?php foreach ($releases as $k => $r): ?>
												<tr>
													<td title="<?php echo $r->info->name; ?>"><?php echo $r->info->name; ?></td>
													<td><?php echo $this->getQuality($r->quality_id)->identifier; ?></td>
													<td class="hidden-xs"><?php echo intval($r->info->size); ?></td>
													<td class="hidden-xs"><?php echo intval($r->info->score); ?></td>
													<td class="hidden-xs"><?php echo $r->info->provider; ?></td>
													<td><a href="#" class="js-cp-download"><i class="glyphicon glyphicon-download"></i></a> <a href="<?php echo $r->info->detail_url; ?>" target="_blank"><i class="glyphicon glyphicon-info-sign"></i></a></td>
												</tr>
											<?php endforeach; ?>
										</tbody>
									</table>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<a class="pull-left js-cp-refresh" href="#" title="<?php _e('Refresh movie', 'couchbeard'); ?>"><i class="glyphicon glyphicon-refresh"></i></a>
				<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
			</div>
    	<?php
	    } catch(Exception $e) {
			_e('Could not find CouchPotato', 'couchbeard');
		}
	    die();
	}


	public function getMovies_templateFunction()
	{
	    try {
			$movies = $this->getMovies();
			if ($movies):
			?>
			<div class="list-group couchpotato">
				<?php foreach ($movies as $k => $m): ?>
					<?php 
						$date = $this->getReleaseDate($m->library->info->release_date);
						$time = ''; 
						if ($date) {
							$time = '<span class="pull-right"><i class="glyphicon glyphicon-time"></i> <time timestamp="' . $date . '" title="' . $date . '">' . human_time_diff( strtotime($date), current_time('timestamp') ) . (strtotime($date) < current_time('timestamp') ? ' ago' : '') . '</time></span>';
						}
					?>
				  	<a data-toggle="modal" data-target="#movieModal" href="#<?php echo $m->library->info->imdb; ?>" class="list-group-item<?php echo $k > 2 ? ' hidden' : ''; ?>" data-imdb="<?php echo $m->library->info->imdb; ?>" data-id="<?php echo $m->library_id; ?>">
						<h4 class="list-group-item-heading" title="<?php echo $m->library->info->original_title; ?>"><?php echo $m->library->info->original_title; ?></h4>
						<p class="list-group-item-text"><time timestamp="<?php echo $m->library->info->year; ?>"><?php echo $m->library->info->year; ?></time><?php echo $time; ?></p>
				  	</a>
				<?php endforeach; ?>
				<a href="#more" class="list-group-item loadmore"><center><p class="lead nomargin"><?php _e('Load more...', 'madslundt'); ?></p></center></a>
			</div>
			<div class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" id="movieModal" aria-labelledby="movieModal" aria-hidden="true">
				<div class="modal-dialog modal-lg">
					<div class="modal-content">
						<div class="modal-body">
							<div class="modal-body">
							</div>
							<div class="modal-footer">
							</div>
						</div>
						<div class="modal-footer">
							<a class="pull-left js-refresh" href="#" title="<?php _e('Refresh movie', 'couchbeard'); ?>"><i class="glyphicon glyphicon-refresh"></i></a>
							<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
							<button type="button" class="btn btn-primary">Save changes</button>
						</div>
					</div>
				</div>
			</div>
			<?php 
				endif;
		} catch (Exception $e) {
			_e('CouchPotato is not online.', 'couchbeard');
		}
	    die();
	}
}
try {
	new couchpotato();
} catch(Exception $e) {}
?>

