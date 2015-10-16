<?php

/**
 * Es werden alle Kursbeschreibungen zur gewählten wahl_id angezeigt.
 * @param $wahl_id Index der Wahl
 * @param $block Nr des Blocks (z.B. Quartal); bei Lehrer-Einstellungen irrelevant.
 * @param $lehrer true: Lehrerformular false: Schülerformular
 * @param $action Folgeskript
 * @return String Formular-Code
 */
function kurs_anzeige($wahl_id, $block, $lehrer, $action) {

  $abfrage="SELECT bloecke FROM wahl_einstellungen WHERE id='$wahl_id'";
  $ergebnis = mysql_query($abfrage) or die (mysql_error());
  if ($row = mysql_fetch_object($ergebnis)) {
    $nbloecke=$row->bloecke;
  }

  if (!$lehrer) { // Gewählte Kurse des Schülers abfragen und in $selected speichern.
    $selected=array();
    $abfrage="SELECT kurs_id,prioritaet FROM schueler_wahl JOIN schueler ON schueler.name='".$_POST['schuelername']."' AND schueler_id=schueler.id AND schueler_wahl.block=$block";
    $ergebnis = mysql_query($abfrage) or die (mysql_error());
    while($row = mysql_fetch_object($ergebnis)) {
      $selected[$row->kurs_id][$row->prioritaet]=true;
    }
  }
  
  $abfrage = <<<END
SELECT kurs_beschreibungen.id as kursid, GROUP_CONCAT(kurse.kuerzel) as kuerzel, kurs_beschreibungen.titel, kurs_beschreibungen.beschreibung
FROM kurs_beschreibungen JOIN
kurse ON kurs_beschreibungen.wahl_id='$wahl_id' AND kurs_beschreibungen.id=kurse.beschr_id
GROUP BY kursid
END;
  
  $ergebnis = mysql_query($abfrage) or die (mysql_error());
  if (isset($_POST['lehrername'])) {
    $hidden="<input type='hidden' name='lehrername' value='".$_POST['lehrername']."'>";
    $col1="<th>&nbsp;</th>";
  } elseif (isset($_POST['schuelername'])) {
    $hidden="<input type='hidden' name='schuelername' value='".$_POST['schuelername']."'>";
    $col1="<th>I</th><th>II</th><th>III</th>";
  }
  $ret=<<<END
<form action='$action' method='post'>
<input type='hidden' name='wahl_id' value='$wahl_id'>
<input type='hidden' name='block' value='$block'>
END;
  if ($nbloecke>1) {
    for ($b=1; $b<=$nbloecke; $b++) {
      $style="";
      if ($b==$block) $style="style='background-color:black;color:white'";
      $ret.="<input type='submit' name='kurs_speichern' $style value='Block $b'>";
    }
  }
  $ret.="<br>";
$ret.=<<<END
<table border='1'>
  <tr>$col1
    <th>Titel</th>
    <th>Beschreibung</th>
  </tr>
  $hidden
END;
  while($row = mysql_fetch_object($ergebnis)) {
    if ($lehrer) {
      $button="<td><button type='submit' name='kurs_id' value='".$row->kursid."'>Edit</button></td>";
    } else {
      $button="";
      for ($nr=1; $nr<=3; $nr++) {
        $checked=isset($selected[$row->kursid][$nr])?"checked":"";
        $button.="<td><input type='radio' name='kurswahl_id$nr' value='".$row->kursid."' $checked></td>";
      }
    }
    $ret.=<<<EOF
  <tr>
    $button
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

/**
 * Diese Funktion zeigt alle wählbaren Kurse an und speichert die Eingaben des Schülers
 * @param wahl_id ID der zur Teilnahme ausgewählten Wahl
 */
function wahl_teilnahme($wahl_id) {
  $schuelername=$_POST['schuelername'];
  $wahlname="???"; $enddatum="???";
  $block=1;
  if (isset($_POST['block']))
    $block=$_POST['block'];
  $cmd="SELECT enddatum, name FROM wahl_einstellungen WHERE id='$wahl_id'";
  $ergebnis = mysql_query($cmd) or die (mysql_error());
  if ($row = mysql_fetch_object($ergebnis)) {
    $wahlname=$row->name;
    $enddatum=$row->enddatum;
  } else {
    die ("Fehler: Die Wahl Nr. $wahl_id wurde noch nicht angelegt.");
  }
  if (!isset($_POST['kurs_speichern'])) { // Noch keine Eingabe
    echo "Du hast bis $enddatum Zeit, an der Wahl '$wahlname' teilzunehmen.<br>\n";
  } else { // Speichern der Eingabe
    $cmd="DELETE schueler_wahl FROM schueler_wahl JOIN schueler WHERE schueler_wahl.schueler_id=schueler.id and schueler.name='$schuelername' AND block='$block'";
    mysql_query($cmd) or die (mysql_error());
    $cmd="INSERT INTO schueler (name) VALUES ('$schuelername')";
    mysql_query($cmd);
    if (mysql_errno()!=1062 && mysql_errno()!=0) die (mysql_error()); // 1062: Duplicate entry
    for ($wahl123=1; $wahl123<=3; $wahl123++) {
      if (!isset($_POST['kurswahl_id'.$wahl123])) continue;
      $cmd="INSERT INTO schueler_wahl (schueler_id,kurs_id,prioritaet,block) SELECT id,".$_POST['kurswahl_id'.$wahl123].",$wahl123,$block FROM schueler WHERE schueler.name='$schuelername'";
      mysql_query($cmd) or die (mysql_error());
    }
    echo "Deine Wahl wurde gespeichert in Block $block.<br>"; 
    echo "Du kannst bis $enddatum die Wahl noch &auml;ndern.<br>";
    if (preg_match("/^Block ([0-9]+)$/",$_POST['kurs_speichern'],$matches)) {
      $block=$matches[1];
    }
  }
  echo kurs_anzeige($wahl_id,$block,false,"wahl_bearbeiten.php");
}

/**
 * Diese Funktion zeigt das Formular mit den Wahl-Einstellungen (Name, Zeitraum...) an und speichert diese Einstellungen nach Änderung.
 * @param wahl_id ID der zur Bearbeitung ausgewählten Wahl
 */
function wahl_einstellungen($wahl_id) {
  if (isset($_POST['wahleinstellungen_speichern'])) {
    $cmd="UPDATE  wahl_einstellungen SET ";
    if ($_POST['name']!="") $cmd.=" name='".$_POST['name']."',";
    if ($_POST['startdatum']!="") $cmd.=" startdatum='".$_POST['startdatum']."',";
    if ($_POST['enddatum']!="") $cmd.=" enddatum='".$_POST['enddatum']."',";
    if ($_POST['bloecke']!="") $cmd.=" bloecke='".$_POST['bloecke']."'";
    $cmd.=" WHERE id='$wahl_id'";
    //echo $cmd."<br>";
    mysql_query($cmd) or die (mysql_error());
    echo "Die ge&auml;nderten Einstellungen wurden gespeichert.<br>";
  }
  //$cmd="SELECT DATE_FORMAT(startdatum,'%d.%m.%y %k:%i') as startdatum,DATE_FORMAT(SUBTIME(enddatum,'00:01'),'%d.%m.%y %k:%i') AS enddatum,name,bloecke FROM wahl_einstellungen WHERE id='$wahl_id'";
  $cmd="SELECT startdatum, enddatum,name, bloecke FROM wahl_einstellungen WHERE id='$wahl_id'";
  $ergebnis = mysql_query($cmd) or die (mysql_error());
  if ($row = mysql_fetch_object($ergebnis)) {
    echo <<<END
<form action="#" id="einstellungen" method="post">
  <fieldset>
    <legend>Wahleinstellungen</legend>
    <input type="hidden" name="lehrername" value="{$_POST['lehrername']}">
    <input type="hidden" name="wahl_id" value="$wahl_id">
    <label>Bezeichnung: <input type="text" name="name" placeholder="$row->name"> <label> <br>
    <label>Startdatum: <input type="text" name="startdatum" placeholder="$row->startdatum"> </label> <br>
    <label>Enddatum:   <input type="text" name="enddatum" placeholder="$row->enddatum"> </label> <br>
    <label>Anzahl Bloecke (z.B. 4 Quartale): <input type="number" name="bloecke" value="$row->bloecke"> </label> <br>
    <input type="submit" name="wahleinstellungen_speichern" value="&Auml;nderungen speichern">
    <input type="reset" name="wahleinstellungen_reset" value="Verwerfen">
  </fieldset>
</form>
END;
  } else {
    die("Fehler: Die Wahl $wahl_id wurde nicht angelegt.<br>");
  }
  if ($row = mysql_fetch_object($ergebnis)) {
    echo "Fehler: Zur Wahl $wahl_id gibt es mehrere Eintr&auml;ge!<br>";
  }
  echo "Folgende Kurse koennen gew&auml;hlt werden:<br>";
  echo kurs_anzeige($wahl_id,-1,true,"kurs_bearbeiten.php");
}

if (!isset($_POST['wahl_id']))
  die("Es wurde keine Wahl festgelegt.");
$wahl_id=$_POST['wahl_id'];
include 'db_connect.php';

if (isset($_POST['lehrername'])) {  // Bearbeitung durch Lehrer
  wahl_einstellungen($wahl_id);
} else {                            // Bearbeitung durch Schüler
  wahl_teilnahme($wahl_id);
}

?>
