<?php

class sickbeard extends couchbeard 
{

	const APP = 'sickbeard';

	protected function setApp() 
	{
		$this->app = self::APP;
		$this->api = parent::getAPI();
	}

	public function __construct() 
	{
		parent::__construct();
		add_action('wp_ajax_sb_addTV', array(&$this, 'addTVFunction'));  // Only logged in users
		add_action('wp_ajax_sb_getTV_template', array(&$this, 'getTV_templateFunction'));  // Only logged in users
		add_action('wp_ajax_nopriv_sb_getTV_template', array(&$this, 'getTV_templateFunction'));
	}

	/**
	 * Get version of Sick Beard
	 * @return string Version
	 */
	public function version()
	{
        $url = $this->getURL() . '/?cmd=sb';
        $json = parent::curl_download($url);
        if (!$json)
            return false;

        $data = json_decode($json);
        return $data->data->version;
	}

	/**
	 * Add TV show to sickbeard
	 * @param  string $id TVDB id
	 * @return bool     Success
	 */
	public function addShow($id)
	{
        $url = $this->getURL() . '/?cmd=show.addnew&tvdbid=' . imdb_to_tvdb($id);
        $json = parent::curl_download($url);
        if (!$json)
            return false;

        $data = json_decode($json);
        return ($data->result != 'failure');
	}

	/**
	 * Get all TV shows in Sickbeard
	 * @return array TV shows
	 */
	public function getShows()
	{
        $url = $this->getURL() . '/?cmd=shows';
        $json = parent::curl_download($url);
        if (!$json)
            return false;

        $data = json_decode($json);
        return $data->data;
	}

	/**
	 * Get a specific show info
	 * @param  string $id TVDB id
	 * @return array     TV show data
	 */
	public function getShow($id)
	{
        $url = $this->getURL() . '/?cmd=show&tvdbid=' . $id;
        $json = parent::curl_download($url);
        if (!$json)
            return false;

        $data = json_decode($json);
        return $data->data;
	}

	/**
	 * Check if series is in Sick Beard
	 * @param  string $id TVDB id
	 * @return bool     Success
	 */
	public function showAdded($id)
	{
	    $res = (array) $this->getShows();
	    return (in_array(imdb_to_tvdb($id), array_keys($res)) ? $this->getShow(imdb_to_tvdb($id)) : false);
	}

	/**
	 * Returns future starting shows
	 * @return array future shows
	 */
	public function getFuture()
	{
        $url = $this->getURL() . '/?cmd=future&sort=date';
        $json = parent::curl_download($url);
        if (!$json)
            return false;

        $data = json_decode($json);
        return $data->data;
	}

	/**
	 * Returns SickBeard's downloaded/snatched history.
	 * @return array history shows
	 */
	public function getHistory($type = '')
	{
        $url = $this->getURL() . '/?cmd=history&sort=date';
        if (!empty($type))
        	$url .= '&type=' . $type;
        $json = parent::curl_download($url);
        if (!$json)
            return false;

        $data = json_decode($json);
        return $data->data;
	}

	private function getReleaseDate($release) {
		return date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($release));
	}



	/* ==========================================================================
	   Sickbeard ajax calls
	   ========================================================================== */
	public function addTVFunction()
	{	
		try {    
		    echo (bool) $this->addShow($_POST['id']);
		} catch(Exception $e) {
			_e('Could not find sickbeard add TV function', 'couchbeard');
		}
	    die();
	}


	public function getTV_templateFunction()
	{
		try {
			$history = $this->getHistory('downloaded');
			$shows = $this->getFuture();
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
											<time datetime="<?php echo $s->date; ?>" title="<?php echo $this->getReleaseDate($s->date); ?>">
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
										<div class="list-group-item-text"><p class="pull-left title" title="<?php echo '(s' . sprintf('%02s', $e->season) . 'e' . sprintf('%02s', $e->episode) . '): ' . $e->ep_name; ?>"><?php echo '(s' . sprintf('%02s', $e->season) . 'e' . sprintf('%02s', $e->episode) . '): ' . $e->ep_name; ?></p>
											<span class="pull-right"><i class="glyphicon glyphicon-time"></i>  
												<time datetime="<?php echo $e->airdate; ?>" title="<?php echo $this->getReleaseDate($e->airdate); ?>">
													<?php echo human_time_diff( strtotime($e->airdate . ' +1 day'), current_time('timestamp') ); ?>
												</time>
											</span>
										</div>
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
		} catch(Exception $e) {
			_e('Could not find sickbeard add TV function', 'couchbeard');
		}
	    die();
	}
}
try {
	new sickbeard();
} catch(Exception $e) {}
?>