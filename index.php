<?php
/**
 * @author Steinsplitter / https://commons.wikimedia.org/wiki/User:Steinsplitter
 * @author Framawiki / https://commons.wikimedia.org/wiki/User:Framawiki
 * @copyright 2017 tool authors
 * @license http://unlicense.org/ Unlicense
 */

// Stats
$hi = ( "new.txt" );
$hii = file( $hi );
$hii[0] ++;
$fp = fopen( $hi , "w" );
fputs( $fp , "$hii[0]" );
fclose( $fp );

/**
 * Query the data from the database. Sensitive information (DB password) is stored
 * in local variables so that it’s cleared as soon as possible.
 */
function query(): mysqli_result {
	mysqli_report( MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );
	$tools_pw = posix_getpwuid ( posix_getuid () );
	$tools_mycnf = parse_ini_file( $tools_pw['dir'] . "/replica.my.cnf" );
	$db = new mysqli( 'commonswiki.web.db.svc.eqiad.wmflabs', $tools_mycnf['user'], $tools_mycnf['password'], 'commonswiki_p' );
	$sql = <<<SQL
		SELECT page_title, actor_name, DATE_FORMAT(rev_timestamp, '%H:%i:%s %b %d %Y')
		FROM page JOIN categorylinks ON page_id = cl_from JOIN revision ON page_latest = rev_id JOIN actor_revision ON actor_id = rev_actor
		WHERE page_is_redirect = 0 AND cl_to = 'Media_requiring_renaming' AND page_namespace = 6
	SQL;
	return $db->query( $sql );
}

if (isset($_GET['api'])) {
	$answer = array();
	$elements = array();
	$r = query();
	while ( $row = $r->fetch_row() ) {
		$elements[] = array('title' => str_replace('_', ' ', $row[0]), 'user' => $row[1], 'date' => $row[2]);
	}
	$answer['len'] = count($elements);
	$answer['elements'] = $elements;
	echo json_encode($answer);
	header('Content-Type: application/json;charset=utf-8');
	exit(0);
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
        <meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
        <title>Media requiring renaming</title>
        <link rel="stylesheet" href="//tools-static.wmflabs.org/cdnjs/ajax/libs/twitter-bootstrap/2.3.2/css/bootstrap.min.css">
    <style>
      body {
        padding-top: 60px;
      }
    </style>
<?php if (isset($_GET['re'])): ?>
<meta http-equiv="cache-control" content="no-cache" />
<script language="javascript">
window.onload = function(start) {
(function countdown(remaining) {
    if(remaining === 1)
        location.reload(true);
    document.getElementById('countdown').innerHTML = remaining;
    setTimeout(function(){ countdown(remaining - 1); }, 1000);
})(60)
};
</script>
<?php endif; ?>
</head>
<body>
    <div class="navbar navbar-fixed-top">
      <div class="navbar-inner">
        <div class="container">
          <a class="brand" href="index.php">Media requiring renaming</a>
          <div class="nav-collapse collapse">
                <ul id="toolbar-right" class="nav pull-right">
               <li><a href="//commons.wikimedia.org/wiki/Category:Media_requiring_renaming">Commonscat</a></li>
               <li><a href="//github.com/Steinsplitter/File-renamers-queue/tree/master">Source</a></li>
               </ul>
          </div>
        </div>
      </div>
    </div>
  <div class="container">
<p>This is a fast loading and easy to use interface for file renamers who are working on the queue. Only files from the maincat show up here.</p>
<span><a href="#" onclick="location.reload(true); return false;" class ="btn btn-primary btn-success">Refresh<div id="countdown"></div></a>
<?php if (isset($_GET['re'])): ?>
<a href="index.php?stop=yes" class ="btn btn-primary btn-inverse">Autorefresh off</a>
<?php else: ?>
<a href="index.php?re=ok" class ="btn btn-primary btn-info">Autorefresh on</a>
<?php endif; ?>
</span><br><br>
<?php
$r = query();
$num = $r->num_rows;
$data = date('H:i:s (m-d-Y)');

echo '<p>There are currently <b>'.$num.'</b> move requests in the queue.</p><p>Data as of '.$data.' (UTC).';
if ( $num == 0 )
     echo "<div class=\"alert alert-info\"><b>No requests:</b> There are zero filemove requests. No backlog. Cool :-).</div>";
//header( 'Cache-Control: no-store, no-cache, must-revalidate' );
?>

<table align="left" class="table table-hover" style ="text-align: left;">
<?php while ( $row = $r->fetch_row() ):?>
 <tr>
  <td width="84">
  <img class="dfile" onerror="this.style.display = 'none'" src="//commons.wikimedia.org/w/thumb.php?f=<?= htmlspecialchars( urlencode ( $row[0] ) ) ?>&amp;w=80&amp;p=40">
  </td>
  <td>
  <a href="//commons.wikimedia.org/w/index.php?title=File:<?= htmlspecialchars ( urlencode ( $row[0] ) ) ?>">File:<?= str_replace( "_", " ", htmlspecialchars( $row[0] ) ); ?></a><br>(<a href="//commons.wikimedia.org/w/index.php?title=File:<?= htmlspecialchars ( urlencode ( $row[0] ) ) ?>&action=history">History</a> | <a href="//commons.wikimedia.org/w/index.php?title=File:<?= htmlspecialchars ( urlencode ( $row[0] ) ) ?>&action=edit">Edit</a> | <a href="//commons.wikimedia.org/w/index.php?title=Special:Log&page=File:<?= htmlspecialchars ( urlencode ( $row[0] ) ) ?>">Logs</a> | <a href="//commons.wikimedia.org/w/index.php?title=Special%3AGlobalUsage&limit=50&target=<?= htmlspecialchars ( urlencode ( $row[0] ) ) ?>">Usage</a>)<br>
  Last edited by <a href="//commons.wikimedia.org/w/index.php?title=User:<?= htmlspecialchars ( urlencode ( $row[1] ) ) ?>"><?= htmlspecialchars ( $row[1] ) ?></a> at <?= htmlspecialchars ( $row[2] ) ?>
  </td>
</tr>
<?php endwhile; ?>

</table>
</div>
</div>
</body>
</html>
