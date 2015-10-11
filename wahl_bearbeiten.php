<?php

function kurs_anzeige($wahl_id) {
  $abfrage = <<<END
SELECT kurs_beschreibungen.id as kursid, GROUP_CONCAT(kurse.kuerzel) as kuerzel, kurs_beschreibungen.titel, kurs_beschreibungen.beschreibung
FROM kurs_beschreibungen JOIN
kurse ON kurs_beschreibungen.wahl_id='$wahl_id' AND kurs_beschreibungen.id=kurse.beschr_id
GROUP BY kursid
END;
  
  $ergebnis = mysql_query($abfrage) or die (mysql_error());
  $ret=<<<EOF
<form action='kurs_bearbeiten.php' method='post'>
<input type='hidden' name='wahl_id' value='$wahl_id'>
<table border='1'>
  <tr><th>&nbsp;</th>
    <th>Titel</th>
    <th>Beschreibung</th>
  </tr>\n
EOF;
  
  while($row = mysql_fetch_object($ergebnis)) {
    $ret.=<<<EOF
  <tr>
    <td><button type='submit' name='kurs_id' value='$row->kursid'>Edit</button></td>
    <td>$row->titel ($row->kuerzel)</td>
    <td>$row->beschreibung &nbsp;</td>
  </tr>\n
EOF;
  }
  $ret.="</table>\n</form>";
  return $ret;
}

if (!isset($_POST['wahl_id']))
  die("Es wurde keine Wahl festgelegt.");
$wahl_id=$_POST['wahl_id'];
include 'db_connect.php';

echo "Sie bearbeiten die Wahl Nr. '$wahl_id'<br>\n";
echo kurs_anzeige($wahl_id);
?>
