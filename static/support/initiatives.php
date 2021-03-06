<html>
<head><meta http-equiv="content-type" content="text/html; charset=UTF-8"></head>
<pre>
<?
require("/opt/liquid_feedback_core/constants.php");

$l = 0;
if (isset($_GET["l"]))
  $l = 1;
if (isset($_GET["t"]))
  $l = 2;

$dbconn = pg_connect("dbname=liquid_feedback")
  or die('Verbindungsaufbau fehlgeschlagen: ' . pg_last_error());

$query = "SELECT issue_id,initiative.id,name FROM initiative LEFT JOIN issue ON initiative.issue_id = issue.id WHERE issue.area_id IN (1,2,3,4,5,6,7,9,10,11,12,23,32) AND issue.policy_id IN (17,7,9,21,4) AND issue_id IN (895,894,856,857,883,902,866,877,886,887,899,900,882,897,885,894,852,853,880,882,859,896,903,904,905,906,888,889,890,892,833,873,891,855) ORDER BY issue_id ASC, id ASC;";
$result = pg_query($query) or die('Abfrage fehlgeschlagen: ' . pg_last_error());
$last_issue_id = "";
while ($line = pg_fetch_array($result, null, PGSQL_ASSOC)) {
  $initiators = "";
  $issue_id = $line["issue_id"];
  if ($l == 2 && $issue_id == $last_issue_id)
    continue;
  $id = $line["id"];

  $query2 = "SELECT member_id,name FROM initiator LEFT JOIN member ON member_id = id WHERE accepted = true AND initiative_id = $id";
  $result2 = pg_query($query2) or die('Abfrage fehlgeschlagen: ' . pg_last_error());
  while ($line2 = pg_fetch_array($result2, null, PGSQL_ASSOC)) {
    $initiators .= $line2["name"] . ",";
  }
  pg_free_result($result2);

  $query2 = "SELECT rendered_draft.content FROM rendered_draft LEFT JOIN current_draft ON rendered_draft.draft_id = current_draft.id WHERE current_draft.initiative_id = $id";
  $result2 = pg_query($query2) or die('Abfrage fehlgeschlagen: ' . pg_last_error());
  $line2 = pg_fetch_array($result2, null, PGSQL_ASSOC) or die('Abfrage fehlgeschlagen: ' . pg_last_error());
  pg_free_result($result2);

  $text = $line2["content"];
  $text = preg_replace('/<h1>(.*?)<\/h1>/', '= $1 =', $text);
  $text = preg_replace('/<h2>(.*?)<\/h2>/', '== $1 ==', $text);
  $text = preg_replace('/<h3>(.*?)<\/h3>/', '=== $1 ===', $text);
  $text = preg_replace('/<h4>(.*?)<\/h4>/', '==== $1 ====', $text);
  $text = preg_replace('/<\/?p>/', "\n", $text);
  $text = preg_replace('/<\/?i>/', "''", $text);
  $text = preg_replace('/<\/?b>/', "'''", $text);
  $text = preg_replace('/<hr ?\/? ?>/',"---",$text);
  $text = preg_replace('/(\n|\r)(\n|\r)+/', "\n\n", $text);
  $text = preg_replace('/<a href="(.*?)">(.*?)<\/a>/', "[$1 $2]", $text);
  $tarray = preg_split('/==?=?=? ?Begründung ?=?=?=?=/', $text, 2, PREG_SPLIT_DELIM_CAPTURE);
  $text = $tarray[0];
  $reason = $tarray[1];

  $pv = $line["positive_votes"];
  $nv = $line["negative_votes"];
  $vc = $line["voter_count"];
  $neutral = $vc - $pv - $nv;
  if ($l != 1 && $l != 2 && $issue_id != $last_issue_id)
  {
echo '
}}
<!-- spacing











-->
{{Thema
|Nummer  = '.$issue_id.'
|Name    = TODO
|Typ     = Programm
|Anträge =
';
  }
  $last_issue_id = $issue_id;
if ($l == 1)
{
    echo "http://lqfb.piratenpartei.at/i?$id\n";
}
else if ($l == 2)
{
    echo "http://lqfb.piratenpartei.at/t?$issue_id\n";
}
else
{
    echo '
{{Antrag
|Nummer          = '.$id.'
|Antragssteller  = '.substr($initiators,0,-1).'
|Text            = '.$text.'
|Begründung      = '.$reason.' 
|Ergebnis        = '. ($pv + 0) .' / '. ($neutral + 0) .' / '. ($nv + 0) .'
|Farbe           = '. ((($pv + 0) / ($nv + 0)) > 0.5 ? "0f0" : "f00") . '
|Link            = http://lqfb.piratenpartei.at/i?'.$id.'
}}';
}
}
pg_free_result($result);

pg_close($dbconn);


?>
</pre>
</html>
