<?php

$host = "ftp.conradtweb.com";
$username = "installer@conradtweb.com";
$password = "installer";

$connection = ftp_connect($host);
$result = ftp_login($connection, $username, $password);

//file_put_contents("Tmpfile.zip", fopen('https://github.com/joeconradt/inkstand/archive/master.zip', 'r'));

if($_SERVER['REQUEST_METHOD'] === 'POST') {
	$package = $_POST['version'];

	set_time_limit(500);

	if (ftp_get($connection	, 'package.zip', $package, FTP_BINARY)) {
    	echo "Successfully written to $local_file\n";
	} else {
	    echo "There was a problem\n";
	}
	die();
}

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

	</style>

	<script type="text/javascript">
	$(document).ready(function() {
		$("#showothers").click(function() {
			$("#otherversions").slideToggle();
		});
	});
	</script>
</head>
<body>
	<div class="container">
		<h1>Inkstand Installer</h1>
		<p>Get ready for the most amazing CMS ever!</p><br><br>
		<h4>Step 1: Choose version</h4>
		<form method="post">
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
			<h4>Step 2: Install</h4>
			<input type="submit" value="Install" class="btn btn-primary btn-lg btn-block">
		</form>
	</div>
</body>
</html>
