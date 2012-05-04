<?php
$username = isset($_GET["user"]) ? $_GET["user"] : false;
$repo_name = isset($_GET["repo"]) ? $_GET["repo"] : false;

function status_ok($curl) {
	$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	return ($status >= 200 && $status < 300);
}

if(!$username || !$repo_name):
?>

<!doctype html>
<html lang="en">

<head>
	<meta charset="utf-8" />
	<meta name="author" content="Sam Rayner" />
	
	<title>GitHub Tags RSS Feed Generator</title>

	<style>
		* {
			margin: 0;
			padding: 0;
		}

		body, input {
			font: 16px/1.2 "Helvetica Neue", helvetica, arial, sans-serif;
		}

		body {
			padding-top: 8em;
			text-align: center;
		}

		div {
			margin-bottom: 1em;
		}

		input {
			padding: 0.5ex 1ex;
		}

		label {
			display: inline-block;
			width: 10ex;
			text-align: right;
			margin-right: 0.5ex;
		}

		p {
			margin-top: 1em;
		}

		footer {
			margin-top: 8em;
		}
	</style>
</head>
<body>	

<form method="get" action="<?php echo $_SERVER["PHP_SELF"] ?>">
	<div>
		<label for="user">Username</label>
		<input type="text" name="user" value="samrayner" autofocus />
	</div>

	<div>
		<label for="repo">Repository</label>
		<input type="text" name="repo" value="GitHub-Tags-Feed" />
	</div>

	<div>
		<input type="submit" value="Get tag feed" />
	</div>
</form>

<footer>
	<p>Made by <a href="http://samrayner.com">Sam Rayner</a>. Questions? <a href="mailto:&#115&#97&#109&#64&#115&#97&#109&#114&#97&#121&#110&#101&#114&#46&#99&#111&#109">Get in touch</a>.</p>

	<p>
		<iframe src="http://markdotto.github.com/github-buttons/github-btn.html?user=samrayner&repo=GitHub-Tags-Feed&type=watch" allowtransparency="true" frameborder="0" scrolling="0" width="62px" height="20px"></iframe>
		<iframe src="http://markdotto.github.com/github-buttons/github-btn.html?user=samrayner&repo=GitHub-Tags-Feed&type=fork" allowtransparency="true" frameborder="0" scrolling="0" width="55px" height="20px"></iframe>
	</p>
</footer>

</body>
</html>

<?php else: ?><?php
$repo_url = "https://api.github.com/repos/$username/$repo_name";
$list_url = $repo_url."/git/refs/tags/";

$curl = curl_init();
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

curl_setopt($curl, CURLOPT_URL, $repo_url);
$response = curl_exec($curl);

if(!status_ok($curl))
	exit("Repository doesn't exist or is private.");

$repo = json_decode($response, true);

curl_setopt($curl, CURLOPT_URL, $list_url);
$response = curl_exec($curl);

if(!status_ok($curl))
	exit("Could not fetch tag list.");

$tag_refs = array_reverse(json_decode($response, true));

$tags = array();
foreach($tag_refs as $tag) {
	curl_setopt($curl, CURLOPT_URL, $tag["object"]["url"]);
	$tags[] = json_decode(curl_exec($curl), true);
}

curl_close($curl);

header("Content-Type: application/xml;");
echo '<?xml version="1.0" encoding="utf-8"?>'; ?>
<rss version="2.0">
	<channel>
	
		<title>Changelog for <?php echo $repo["name"] ?></title>
		<link><?php echo $repo["html_url"] ?></link>
		<description><?php echo $repo["description"] ?></description>
		<language>en</language>
		<copyright>Copyright <?php echo date("Y") ?>, <?php echo $username ?></copyright>
		<docs>http://blogs.law.harvard.edu/tech/rss</docs>
		<pubDate><?php echo date("r", strtotime($repo["pushed_at"])) ?></pubDate>
		<lastBuildDate><?php echo date("r", strtotime($repo["updated_at"])) ?></lastBuildDate>
		
		<?php foreach($tags as $tag): ?>
		<item>
		
			<title><?php echo $tag["tag"] ?></title>
			<link><?php echo "https://github.com/$username/$repo_name/commit/".$tag["sha"] ?></link>
			<pubDate><?php echo date("r", strtotime($tag["tagger"]["date"])) ?></pubDate>
			<guid><?php echo "https://github.com/$username/$repo_name/commit/".$tag["sha"] ?></guid>
			<author><?php echo $tag["tagger"]["email"] ?></author>
			<description><?php echo $tag["message"] ?></description>

		</item>
		<?php endforeach ?>
		
	</channel>
</rss>

<?php endif ?>