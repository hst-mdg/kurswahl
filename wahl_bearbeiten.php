<?php

/**
 * Es werden alle Kursbeschreibungen zur gewählten wahl_id angezeigt.
 * @param $wahl_id Index der Wahl
 * @param $lehrer true: Lehrerformular false: Schülerformular
 * @param $action Folgeskript
 * @return String Formular-Code
 */
function kurs_anzeige($wahl_id, $lehrer, $action) {
  // TODO: falls Schüler - gewählten Kurs selektieren.
  $abfrage = <<<END
SELECT kurs_beschreibungen.id as kursid, GROUP_CONCAT(kurse.kuerzel) as kuerzel, kurs_beschreibungen.titel, kurs_beschreibungen.beschreibung
FROM kurs_beschreibungen JOIN
kurse ON kurs_beschreibungen.wahl_id='$wahl_id' AND kurs_beschreibungen.id=kurse.beschr_id
GROUP BY kursid
END;
  
  $ergebnis = mysql_query($abfrage) or die (mysql_error());
  $ret=<<<EOF
<form action='$action' method='post'>
<input type='hidden' name='wahl_id' value='$wahl_id'>
<table border='1'>
  <tr><th>&nbsp;</th>
    <th>Titel</th>
    <th>Beschreibung</th>
  </tr>\n
EOF;
  
  while($row = mysql_fetch_object($ergebnis)) {
    if ($lehrer) {
      $button="<button type='submit' name='kurs_id' value='".$row->kursid."'>Edit</button>";
    } else {
      $button="<input type='radio' name='kurs_id' value='".$row->kursid."'>";
    }
    $ret.=<<<EOF
  <tr>
    <td>$button</td>
    <td>$row->titel ($row->kuerzel)</td>
    <td>$row->beschreibung &nbsp;</td>
  </tr>\n
EOF;
  }
  $ret.="</table><br>\n";
  if (!$lehrer) {
    $ret.="<input type='submit' name='kurs_speichern' value='Speichern'><br>";
  }
  $ret.="</form>";
  return $ret;
}

if (!isset($_POST['wahl_id']))
  die("Es wurde keine Wahl festgelegt.");
$wahl_id=$_POST['wahl_id'];
include 'db_connect.php';

if (isset($_POST['lehrername'])) {
  echo "Sie bearbeiten die Wahl Nr. '$wahl_id'<br>\n";
  echo kurs_anzeige($wahl_id,true,"kurs_bearbeiten.php");
} else {
  if (!isset($_POST['kurs_speichern'])) {
    echo "Du hast bis ... Zeit, an der Wahl Nr. '$wahl_id' teilzunehmen.<br>\n";
    echo kurs_anzeige($wahl_id,false,"wahl_bearbeiten.php");
  } else {
    echo "TODO: Speichern.<br>";
    echo "Du kannst bis ... die Wahl noch aendern.<br>";
    echo kurs_anzeige($wahl_id,false,"wahl_bearbeiten.php");
  }
}

?>
