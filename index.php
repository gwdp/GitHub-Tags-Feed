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
    <a href="https://github.com/gwdp/GitHub-Tags-Feed"><img style="position: absolute; top: 0; left: 0; border: 0;" src="https://s3.amazonaws.com/github/ribbons/forkme_left_darkblue_121621.png" alt="Fork me on GitHub"></a>
	<style>
		* {
			margin: 0;
			padding: 0;
		}

		body, input {
			font: 16px/1.2 "Helvetica Neue", helvetica, arial, sans-serif;
		}

		body {
			text-align: center;
		}

		h1 {
			font-size: 1.6em;
			line-height: 6;
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

<h1>GitHub Tags RSS Feed Generator</h1>

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
		<input type="submit" value="Get Tag Feed" />
	</div>
</form>
</body>
</html>

<?php else: ?><?php
$repo_url = "https://api.github.com/repos/$username/$repo_name";
$list_url = $repo_url."/tags";

$curl = curl_init();
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

curl_setopt($curl, CURLOPT_URL, $repo_url);
$response = curl_exec($curl);

if(!status_ok($curl)) {
	header("HTTP/1.1 404 Not Found");
	exit("Repository doesn't exist or is private. ".curl_getinfo($curl, CURLINFO_HTTP_CODE)." - ".response);
}

$repo = json_decode($response, true);

curl_setopt($curl, CURLOPT_URL, $list_url);
$response = curl_exec($curl);

if(!status_ok($curl)) {
	header("HTTP/1.1 404 Not Found");
	exit("No tags for this repository yet. ".curl_getinfo($curl, CURLINFO_HTTP_CODE)." - ".response);
}
$tag_refs = json_decode($response, true);
curl_close($curl);

function escape(&$var) {
	$var = htmlspecialchars($var, ENT_NOQUOTES | 16); //ENT_XML1 = 16
}

escape($repo["name"]);
escape($repo["description"]);
escape($username);
//got and modified from http://stackoverflow.com/questions/2699086/sort-multidimensional-array-by-value-2
function feed_sort (&$array, $key) {
    $sorter=array();
    $ret=array();
    reset($array);
    foreach ($array as $ii => $va) {
        $sorter[$ii]=str_replace("v","",$va[$key]);
    }
    arsort($sorter);
    foreach ($sorter as $ii => $va) {
        $ret[$ii]=$array[$ii];
    }
    $array=$ret;
}
//Sort tags array
$orderedTags = $tag_refs ;
feed_sort($orderedTags,'name');

header("Content-Type: application/xml;"); ?>
 <rss version="2.0"
    xmlns:git="http://github-tags.herokuapp.com/gitModule">
	<channel>
		<title>Changelog for <?php echo $repo["name"] ?></title>
		<link><?php echo $repo["html_url"] ?></link>
		<description><?php echo $repo["description"] ?></description>
		<language>en</language>
		<copyright>Copyright <?php echo date("Y") ?>, <?php echo $username ?></copyright>
		<docs>http://blogs.law.harvard.edu/tech/rss</docs>
		<pubDate><?php echo date("r", strtotime($repo["pushed_at"])) ?></pubDate>
		<lastBuildDate><?php echo date("r", strtotime($repo["updated_at"])) ?></lastBuildDate>
		<?php foreach($orderedTags as $tag): ?>
        <item>
            <guid isPermaLink="false"><?php echo $tag["commit"]["sha"] ?></guid>
			<title><?php echo $tag["name"] ?></title>
            <description><?php echo "Tag: ".$tag["name"] ?></description>
			<git:sha><?php echo $tag["commit"]["sha"] ?></git:sha>
			<link><?php echo $tag["zipball_url"] ?></link>
			<git:linkZip><?php echo $tag["zipball_url"] ?></git:linkZip>
			<git:linkTar><?php echo $tag["tarball_url"] ?></git:linkTar>
			<git:linkCommit><?php echo $tag["commit"]["url"] ?></git:linkCommit>
		</item>
       <?php endforeach ?>
	</channel>
</rss>
<?php endif ?>