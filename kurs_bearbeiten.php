<?php
include 'db_connect.php';

function kurs_anzeigen($kurs_id) {
  $abfrage = <<<END
SELECT GROUP_CONCAT(kurse.kuerzel) as kuerzel, kurs_beschreibungen.titel, kurs_beschreibungen.beschreibung,
kurs_beschreibungen.wahl_id
FROM kurs_beschreibungen JOIN kurse
ON kurs_beschreibungen.id=kurse.beschr_id
AND kurse.beschr_id='$kurs_id'
GROUP BY kurse.beschr_id
END;
  $ergebnis = mysql_query($abfrage) or die (mysql_error());
  $form="<form action='kurs_bearbeiten.php' method='post'>\n";
  $form.="<input type='hidden' name='kurs_id' value='$kurs_id'>\n";
  while($row = mysql_fetch_object($ergebnis)) {
    $form.=<<<END
  <input type='hidden' name='wahl_id' value='$row->wahl_id'>
  Titel: <input type='text' name='titel' value='$row->titel'><br>
  Beschreibung: <textarea name='beschr' rows='4' cols='80'>$row->beschreibung</textarea><br>
  Kuerzel: <input type='text' name='kuerzel' value='$row->kuerzel'><br>
  <input type='submit' name='bearbeitet' value='Speichern'>
  <input type='submit' name='bearbeitet' value='Cancel'>
END;
    $form.="</form>";
  }
  return $form;
}

if (!isset($_POST['wahl_id']) || !isset($_POST['kurs_id']))
  die ("Die Wahl oder der Kurs wurden nicht festgelegt.");
$wahl_id=$_POST['wahl_id'];
$kurs_id=$_POST['kurs_id'];
if (!isset($_POST['bearbeitet'])) {
  echo kurs_anzeigen($kurs_id);
} else { // Seite hat sich Nach Kursbearbeitung selbst aufgerufen
  if ($_POST['bearbeitet']=="Speichern") {
    echo '<body onload=\'alert("TODO: Speichern");\'>';
  } else {
    echo '<body onload=\'alert("Kursbearbeitung wurde abgebrochen.");\'>';
  }
  include 'wahl_bearbeiten.php';
  echo "</body>";
}
?>
