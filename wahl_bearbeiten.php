<?php

/**
 * Es werden alle Kursbeschreibungen zur gewählten wahl_id angezeigt.
 * @param $wahl_id Index der Wahl
 * @param $lehrer true: Lehrerformular false: Schülerformular
 * @param $action Folgeskript
 * @return String Formular-Code
 */
function kurs_anzeige($wahl_id, $lehrer, $action) {

  if (!$lehrer) { // Gewählte Kurse des Schülers abfragen und in $selected speichern.
    $selected=array();
    $abfrage="SELECT kurs_id,prioritaet FROM schueler_wahl JOIN schueler ON schueler.name='".$_POST['schuelername']."' AND schueler_id=schueler.id";
    $ergebnis = mysql_query($abfrage) or die (mysql_error());
    while($row = mysql_fetch_object($ergebnis)) {
      $selected[$row->kurs_id]=$row->prioritaet;
    }
  }
  
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
  if (isset($_POST['lehrername']))
    $hidden="<input type='hidden' name='lehrername' value='".$_POST['lehrername']."'>";
  if (isset($_POST['schuelername']))
    $hidden="<input type='hidden' name='schuelername' value='".$_POST['schuelername']."'>";
  $ret.=$hidden;  
  while($row = mysql_fetch_object($ergebnis)) {
    if ($lehrer) {
      $button="<button type='submit' name='kurs_id' value='".$row->kursid."'>Edit</button>";
    } else {
      $checked=isset($selected[$row->kursid])?"checked":"";
      $button="<input type='radio' name='kurs_id' value='".$row->kursid."' $checked>";
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

if (isset($_POST['lehrername'])) {  // Bearbeitung durch Lehrer
  $cmd="SELECT startdatum,enddatum,name,bloecke FROM wahl_einstellungen WHERE id='$wahl_id'";
  $ergebnis = mysql_query($cmd) or die (mysql_error());
  if ($row = mysql_fetch_object($ergebnis)) {
    echo <<<END
Sie bearbeiten die Wahl "$row->name".<br>
Die Teilnahme an der Wahl ist moeglich von $row->startdatum bis $row->enddatum.<br>
Sie umfasst $row->bloecke Teile (z.B. Quartale/Halbjahre).<br>
END;
  } else {
    die("Fehler: Die Wahl $wahl_id wurde nicht angelegt.<br>");
  }
  if ($row = mysql_fetch_object($ergebnis)) {
    echo "Fehler: Zur Wahl $wahl_id gibt es mehrere Eintraege!<br>";
  }
  echo "Folgende Kurse koennen gewaehlt werden:<br>";
  echo kurs_anzeige($wahl_id,true,"kurs_bearbeiten.php");
} else {                            // Bearbeitung durch Schüler
  $schuelername=$_POST['schuelername'];
  if (!isset($_POST['kurs_speichern'])) {
    echo "Du hast bis ... Zeit, an der Wahl Nr. '$wahl_id' teilzunehmen.<br>\n";
    echo kurs_anzeige($wahl_id,false,"wahl_bearbeiten.php");
  } else {
    $cmd="DELETE schueler_wahl FROM schueler_wahl JOIN schueler WHERE schueler_wahl.schueler_id=schueler.id and schueler.name='$schuelername'";
    mysql_query($cmd) or die (mysql_error());
    $cmd="INSERT INTO schueler (name) VALUES ('$schuelername')";
    mysql_query($cmd);
    if (mysql_errno()!=1062) die (mysql_error()); // 1062: Duplicate entry
    $cmd="INSERT INTO schueler_wahl (schueler_id,kurs_id,prioritaet) SELECT id,".$_POST['kurs_id'].",1 FROM schueler WHERE schueler.name='$schuelername'";
    mysql_query($cmd) or die (mysql_error());
    echo "Deine Wahl wurde gespeichert.<br>";
    echo "Du kannst bis ... die Wahl noch aendern.<br>";
    echo kurs_anzeige($wahl_id,false,"wahl_bearbeiten.php");
  }
}

?>
