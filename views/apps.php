<div class="row">
	<?php 
	if (isset($apps_name)):
		$row_sm = 12 / $row_sm;
		$row_md = 12 / $row_md;
		$row_lg = 12 / $row_lg;
		foreach ($apps_name as $a):

		?>
		<div class="col-lg-<?php echo $row_lg; ?> col-md-<?php echo $row_md; ?> col-sm-<?php echo $row_sm; ?> col-xs-12">
			<div class="thumbnail<?php echo ' ' . strtolower($a); ?>">
				<a href="#" class="pull-right js-update update"><i class="glyphicon glyphicon-refresh greyout"></i></a>
				<!--<div class="loading"></div>-->
				<a href="#"><img src="<?php echo plugins_url('../img/' . strtolower($a) . '.png', __FILE__ ); ?>"/></a>
				<div class="caption" id="<?php echo strtolower($a); ?>">
					<h3><?php echo $a; ?></h3>
					<div class="data">
						<center><i class="glyphicon glyphicon-refresh loader"></i></center>
					</div>
				</div>
			</div>
		</div>

		<?php endforeach; ?>
	<?php endif; ?>
</div>