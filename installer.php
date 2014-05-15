<?php

session_start();

$host = "ftp.conradtweb.com";
$username = "installer@conradtweb.com";
$password = "installer";

$temppath = "temp.zip";

/*
 *
 * Setup package_downloader for downloading inkstand packages
 *
 */

$package_downloader = "<?php

session_start();

\$connection = ftp_connect('$host');
\$result = ftp_login(\$connection, '$username', '$password');

\$package = \$_POST['version'];

\$_SESSION['size'] = ftp_size(\$connection, \$package);

session_write_close();

set_time_limit(500);

if (ftp_get(\$connection, '$temppath', \$package, FTP_BINARY)) {
	echo 'Successfully written to \$local_file\n';
} else {
    echo 'There was a problem\n';
}
die();

?>
";

if(!file_exists('package_downloader.php')) {
	file_put_contents('package_downloader.php', $package_downloader);
}

/*
 *
 * Setup package_downloader for downloading inkstand packages
 *
 */

$package_progresser = "<?php

 session_start();

\$package = '$temppath';

\$source = \$_SESSION['size'];
\$local = filesize(\$package);

echo (\$local / \$source);

?>
";

if(!file_exists('package_progresser.php')) {
	file_put_contents('package_progresser.php', $package_progresser);
}

/*
 *
 * Setup package_unpackager for unpacking
 *
 */

$package_unpackager = "<?php

\$zip = new ZipArchive;
\$zip->open('$temppath');
\$zip->extractTo('./');
\$zip->close();

unlink('package_downloader.php');
unlink('package_progresser.php');
unlink('$temppath');
unlink(__FILE__);

?>
";

if(!file_exists('package_unpackager.php')) {
	file_put_contents('package_unpackager.php', $package_unpackager);
}

/* setup connection to FTP server */

$connection = ftp_connect($host);
$result = ftp_login($connection, $username, $password);

$contents = ftp_nlist($connection, '.');

$contents = array_reverse($contents);

$versions = array();

foreach ($contents as $file) {
	if($file != '.' && $file != '..') {
		$start = strpos($file, '-');
		$end = strpos($file, '.zip');
		$version = substr($file, $start + 1, $end - $start - 1);
		array_push($versions, array('file' => $file, 'ver' => $version));
	}
}

$newest = $versions[0];

?>

<!DOCTYPE html>
<html>
<head>
	<title>Inkstand Installer</title>

	<script type="text/javascript" src="https://code.jquery.com/jquery-1.11.1.min.js"></script>

	<!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">

	<!-- Optional theme -->
	<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap-theme.min.css">

	<!-- Latest compiled and minified JavaScript -->
	<script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>

	<style type="text/css">

		#newest
		{
			font-size:20px;
		}
		.progress-bar
		{
			background-color:#ea7206;
		}
		#configure
		{
			display:none;
		}
		.spin
		{
			-webkit-animation-name: spin;
		    -webkit-animation-duration: 1000ms;
		    -webkit-animation-iteration-count: infinite;
		    -webkit-animation-timing-function: linear;
		    -moz-animation-name: spin;
		    -moz-animation-duration: 1000ms;
		    -moz-animation-iteration-count: infinite;
		    -moz-animation-timing-function: linear;
		    -ms-animation-name: spin;
		    -ms-animation-duration: 1000ms;
		    -ms-animation-iteration-count: infinite;
		    -ms-animation-timing-function: linear;
		    
		    animation-name: spin;
		    animation-duration: 1000ms;
		    animation-iteration-count: infinite;
		    animation-timing-function: linear;
		}
		@-ms-keyframes spin {
		    from { -ms-transform: rotate(0deg); }
		    to { -ms-transform: rotate(360deg); }
		}
		@-moz-keyframes spin {
		    from { -moz-transform: rotate(0deg); }
		    to { -moz-transform: rotate(360deg); }
		}
		@-webkit-keyframes spin {
		    from { -webkit-transform: rotate(0deg); }
		    to { -webkit-transform: rotate(360deg); }
		}
		@keyframes spin {
		    from {
		        transform:rotate(0deg);
		    }
		    to {
		        transform:rotate(360deg);
		    }
		}
	</style>

	<script type="text/javascript">

	var progressTimer;

	$(document).ready(function() {
		$("#showothers").click(function() {
			$("#otherversions").slideToggle();
		});

		function ajaxClosure(url, _data) {
			$.post(url, _data, function(data) {
				if(!isNaN(data)) {
					percent = (data * 100);
					$(".progress-bar").css("width", percent + "%");

					if(percent >= 100) {
						clearInterval(progressTimer);
						$(".progress-bar").css({"width" : "50%", "background-color" : "#1b4670"});

						$.post('package_unpackager.php', {}, function() {
							$(".progress-bar").css({"width" : "100%"});
							nextStatus();
							nextStatus();
							$("#configure").slideDown();
						});

						nextStatus();
					}
				}
			});
		}

		$('form').submit(function(e) {
			e.preventDefault();

			$("#installing").slideDown();
			$("#step1").css("opacity", 0.6);
			$("#step2").css("opacity", 0.6);
			$("form input[type=submit]").slideUp();
			$("#inner-form").slideUp();
			
			ajaxClosure('package_downloader.php', { 'version' :  $("input[name=version]").val() });

			progressTimer = setInterval(function() {
				ajaxClosure('package_progresser.php', {});
			}, 50);
		});
	});

	function nextStatus() {
		var _this = $("#status .spin");
		_this.removeClass("glyphicon-repeat");
		_this.addClass("glyphicon-ok");
		_this.removeClass("spin");

		var index = $("li").index(_this.parent());

		var next = $("#status li:nth-child(" + (index+2) + ") span");
		next.addClass("glyphicon-repeat");
		next.addClass("spin");
	}
	</script>
</head>
<body>
	<div class="container">
		<h1>Inkstand Installer</h1>
		<p>Get ready for the most amazing CMS ever!</p><br><br>
		<h4 id="step1">Step 1: Choose version</h4>
		<form>
			<div id="inner-form">
				<div class="form-group">
					<div id="newest" class="alert alert-success">
						<input type="radio" name="version" value="<?php echo $newest['file'] ?>" checked> Inkstand <?php echo $newest['ver'] ?> <b>Recommended</b>
					</div>
				</div>
				<a id="showothers" class="btn btn-default btn-sm">Show other versions</a>
				<div id="otherversions" class="form-group alert alert-warning" style="display:none">
					<?php foreach(array_slice($versions, 1) as $version) : ?>
						<input type="radio" name="version" value="<?php echo $version['file'] ?>"> Inkstand ver: <?php echo $version['ver'] ?><br>
					<?php endforeach; ?>
				</div><br><br><br>
			</div>
			<h4 id="step2">Step 2: Start download</h4>
			<input type="submit" value="Start" class="btn btn-primary btn-lg btn-block">
		</form>
		<div id="installing" style="display:none">
			<h4>Step 3: Downloading Inkstand</h4>
			<div class="progress progress-striped active">
			  <div class="progress-bar"  role="progressbar" aria-valuenow="45" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
			    <span class="sr-only"></span>
			  </div>
			</div>
			<ul id="status" style="list-style:none !important">
				<li><span class="glyphicon glyphicon-repeat spin"></span> Downloading...</li>
				<li><span class="glyphicon"></span> Unpacking...</li>
				<li><span class="glyphicon"></span> Finished</li>
			</ul>
		</div>
		<a id="configure" class="btn btn-primary" href="index.php">Configure now</a>
	</div>
</body>
</html>
