<?php
session_start();
include 'db_connect.php';
include 'abfragen.php';

function wahlen_anzeigen($klasse, $nbloecke) {
  $wahl123_header="";
  for ($i=1; $i<=$nbloecke; $i++) $wahl123_header.="<th>I</th><th>II</th><th>III</th>";
  $cmd="SELECT s.name, LTRIM(RIGHT(s.name,LENGTH(s.name) - LOCATE('.',s.name))) AS nachname, k.block, sw.prioritaet, k.kuerzel"
    ." FROM schueler AS s JOIN schueler_wahl AS sw ON sw.schueler_id=s.id JOIN kurse as k ON sw.kurs_id=k.id "
    ." JOIN kurs_beschreibungen AS kb ON kb.id=k.beschr_id WHERE s.klasse='$klasse' AND kb.wahl_id=".$_SESSION['wahl_id']." ORDER BY nachname";
  $ergebnis = mysql_query($cmd) or die (mysql_error());
  $wahl=array();
  while($row = mysql_fetch_object($ergebnis)) {
    if (!isset($wahl[$row->name][$row->block][$row->prioritaet])) $wahl[$row->name][$row->block][$row->prioritaet]=array();
    $wahl[$row->name][$row->block][$row->prioritaet][]=$row->kuerzel;
  }
  if (sizeof($wahl)<=0) {
    echo "Keine Eintraege fuer Klasse $klasse.<br>";
    return;
  }
  echo <<<END
<fieldset><legend>Klasse $klasse:</legend>
<table border='1'>
  <tr><th>Name</th>$wahl123_header</tr>
END;
  foreach ($wahl as $schueler=>$w) {
    $wahl123="";
    for ($b=1; $b<=$nbloecke; $b++)
      for ($prior=1; $prior<=3; $prior++) 
        $wahl123.="<td>".(isset($w[$b][$prior])?join("<br>",$w[$b][$prior]):"-")."</td>";
    echo "  <tr><td>$schueler</th>$wahl123</tr>\n";
  }
  echo "</table></fieldset>\n";
}

function zufaellig_setzen($klasse, $nbloecke) {
  // Wahlen der Klasse erst löschen
  $cmd="DELETE schueler_wahl FROM schueler_wahl JOIN schueler ON schueler_wahl.schueler_id=schueler.id WHERE schueler.klasse='$klasse'";
  mysql_query($cmd) or die (mysql_error());
  // Wählbare Kurse abfragen
  $auswahl="('$klasse'";
  if (preg_match("/([0-9]+)[a-z]/",$klasse,$matches)) $auswahl.=",'$matches[1]'";
  $auswahl.=")";
  $cmd="SELECT DISTINCT k.id,k.block FROM kurse AS k JOIN kurs_jahrgang as kj ON kj.kurs_id=k.id "
    ." JOIN kurs_beschreibungen AS kb ON kb.id=k.beschr_id WHERE kj.jahrgang IN $auswahl AND kb.wahl_id=".$_SESSION['wahl_id'];
  $ergebnis=mysql_query($cmd) or die (mysql_error());
  $kurse=array();
  while($row = mysql_fetch_object($ergebnis)) $kurse[$row->block][]=$row->id;
  // Alle IDs der Schueler aus $klasse abfragen
  $schueler_ids=array();
  $cmd="SELECT id FROM schueler WHERE klasse='$klasse'";
  $ergebnis=mysql_query($cmd) or die (mysql_error());
  while($row = mysql_fetch_object($ergebnis)) $schueler_ids[]=$row->id;  
  $values="";
  foreach ($kurse as $k) {
    if (sizeof($k)<3) {
      echo "Nicht genug w&auml;hlbare Kurse f&uuml;r Klasse $klasse.<br>";
      return;
    }
  }
  foreach ($schueler_ids as $id) {
    $weg=array();
    for ($block=1; $block<=$nbloecke; $block++) {
      $gewaehlt=array_rand($kurse[$block],3);
      shuffle($gewaehlt);
      for ($wahl=1; $wahl<=3; $wahl++) {
        if (!in_array($kurse[$block][$gewaehlt[$wahl-1]],$weg)) {
          $values.="($id,".$kurse[$block][$gewaehlt[$wahl-1]].",$wahl),";
          $weg[]=$kurse[$block][$gewaehlt[$wahl-1]];
        } else {
          // Kein Eintrag, da Kurs mit 2 Kürzeln gewählt.
        }
      }
    }
  }
  $values=substr($values,0,-1);
  $cmd="INSERT INTO schueler_wahl (schueler_id,kurs_id,prioritaet) VALUES $values";
  mysql_query($cmd) or die (mysql_error());
}

$nbloecke=block_anzahl($_SESSION['wahl_id']);
$klassen=$_POST['klassen'];
foreach($klassen as $klasse) {
  if (isset($_POST["klassen_simulation"])) zufaellig_setzen($klasse, $nbloecke);
  if (isset($_POST["klassen_simulation"]) || isset($_POST["klassen_anzeigen"])) wahlen_anzeigen($klasse, $nbloecke);
}


?>