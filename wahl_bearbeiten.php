<?php
if (!isset($_SESSION)) session_start();

include_once('abfragen.php');

/**
 * Es werden alle Kursbeschreibungen zur gewählten wahl_id angezeigt.
 * @param $wahl_id Index der Wahl
 * @param $block Nr des Blocks (z.B. Quartal); bei Lehrer-Einstellungen irrelevant.
 * @param $lehrer true: Lehrerformular false: Schülerformular
 * @param $action Folgeskript
 * @return String Formular-Code
 */
function kurs_anzeige($wahl_id, $block, $lehrer, $action) {

  $nbloecke=block_anzahl($wahl_id);
  
  if (!$lehrer) { // Gewählte Kurse des Schülers abfragen und in $selected speichern.
    $selected=array();
    $abfrage="SELECT kurs_id,prioritaet FROM schueler_wahl JOIN user ON user.name='".$_SESSION['user']
      ."' AND schueler_id=user.id AND schueler_wahl.block=$block";
    $ergebnis = mysql_query($abfrage) or die (mysql_error());
    while($row = mysql_fetch_object($ergebnis)) {
      $selected[$row->kurs_id][$row->prioritaet]=true; // kurs_id aus 'kurse', nicht aus 'kurs_beschreibungen' !
    }
  }
  
  if (lehrer_angemeldet()) {
    $jahrgang_cond="1";
  } else {
    $klasse=$_SESSION['klasse'];
    if (preg_match("/^(\d+)[a-zA-Z]+$/",$klasse, $matches)) {
      $jahr=$matches[1];
    }
    $klasse="'".$klasse."'";
    if (isset($jahr)) $klasse.=",'$jahr'";
    $jahrgang_cond="(jahrgang IN ($klasse, '') OR ISNULL(jahrgang))";
  }
  $zusaetze=zusatz_abfrage($wahl_id,FALSE);
  $zusatz_header="";
  $zusatz_add="";
  $zusatz_eintraege=array();
  foreach ($zusaetze as $name=>$z_arr) {
    $zusatz_header.="<th>$name</th>";
    $zusatz_add.="<td>&nbsp;</td>";
    $z_arr=array_map(function($a){ return join("/",$a); },$z_arr);
    foreach ($z_arr as $kursid=>$z) {
      $zusatz_eintraege[$name][$kursid]="<td>$z</td>";
    }
  }
  $tmp=kuerzeljahr_abfrage($wahl_id);
  foreach ($tmp as $id=>$a) {
    $kuerzel[$id]=join("<br>",array_keys($a));
    foreach(array_keys($a) as $k) {
        if (!isset($jahre[$id])) $jahre[$id]=""; else $jahre[$id].="<br>";
        $jahre[$id].=join(",",$a[$k]);
      }
  }
  $abfrage = <<<END
SELECT kurse.beschr_id, kurse.id AS kursid, kurse.block, kurs_beschreibungen.titel, kurs_beschreibungen.beschreibung
FROM kurs_beschreibungen
LEFT JOIN kurse ON kurs_beschreibungen.id=kurse.beschr_id
LEFT JOIN kurs_jahrgang ON kurse.id=kurs_jahrgang.kurs_id
WHERE kurs_beschreibungen.wahl_id='$wahl_id'
AND $jahrgang_cond
GROUP BY kurse.beschr_id
END;
  $ergebnis = mysql_query($abfrage) or die (mysql_error());
  if (lehrer_angemeldet()) {
    $col1="<th>&nbsp;</th>";
  } else { // Schüler
    $col1="<th>I</th><th>II</th><th>III</th>";
  }
  $_SESSION['wahl_id']=$wahl_id;
  $_SESSION['block']=$block;
  $ret=<<<END
<form action='$action' method='post'>
END;
  if (!$lehrer && $nbloecke>1) {
    for ($b=1; $b<=$nbloecke; $b++) {
      $style="";
      if ($b==$block) $style="style='background-color:black;color:white'";
      $ret.="<input type='submit' name='kurs_speichern' $style value='Block $b'>";
    }
  }
  $ret.="<br>";
$ret.=<<<END
<table style="border-collapse: collapse; border: 1px solid black;" border="1">
  <tr>$col1
    <th>Titel</th>
    <th>K&uuml;rzel</th>
    <th>Jahrg&auml;nge</th>
    <th>Beschreibung</th>
    $zusatz_header
  </tr>
END;
  if ($lehrer) {
    $ret.="<td><button type='submit' name='add' value='-1'><image src='img/add.png'></button></td></button></td>"
      ."<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>$zusatz_add";
  }
  while($row = mysql_fetch_object($ergebnis)) {
    $zusatz_eintrag="";
    foreach (array_keys($zusaetze) as $name)
      $zusatz_eintrag.=isset($zusatz_eintraege[$name][$row->beschr_id])?$zusatz_eintraege[$name][$row->beschr_id]:"<td>&nbsp;</td>";
    if ($lehrer) {
      $button="<td><button type='submit' name='edit' value='".$row->beschr_id."'><image src='img/edit.png'></button>"
        ."<button type='submit' name='delete' value='".$row->beschr_id."'><image src='img/remove.png'></button></td>";
    } else {
      $button="";
      for ($nr=1; $nr<=3; $nr++) {
        $checked=isset($selected[$row->kursid][$nr])?"checked":"";
        $button.="<td><input type='radio' name='kurswahl_id$nr' value='".$row->kursid."' $checked></td>";
      }
    }
    if ($lehrer || ($row->block == $block)) $ret.=<<<EOF
  <tr>
    $button
    <td>$row->titel</td>
    <td>{$kuerzel[$row->beschr_id]}&nbsp;</td>
    <td>{$jahre[$row->beschr_id]}&nbsp;</td>
    <td style="font-size: 0.7em">$row->beschreibung &nbsp;</td>
    $zusatz_eintrag
  </tr>\n
EOF;
  }
  $ret.="</table><br>\n";
  if (!$lehrer) {
    $ret.="<input type='submit' name='kurs_speichern' value='Speichern'><br>";
  } else {
    $ret.="<button type='submit' name='delete' value='%' disabled><image src='img/remove.png'>ALLES LOESCHEN</button></td>";
  }
  $ret.="</form>";
  return $ret;
}

/**
 * Diese Funktion zeigt alle wählbaren Kurse an und speichert die Eingaben des Schülers
 * @param wahl_id ID der zur Teilnahme ausgewählten Wahl
 */
function wahl_teilnahme($wahl_id) {
  $schuelername=$_SESSION['user'];
  $wahlname=""; $enddatum="";
  $block=1;
  if (isset($_SESSION['block']))
    $block=$_SESSION['block'];
  $cmd="SELECT enddatum, name FROM wahl_einstellungen WHERE id='$wahl_id'";
  $ergebnis = mysql_query($cmd) or die (mysql_error());
  if ($row = mysql_fetch_object($ergebnis)) {
    $wahlname=$row->name;
    $enddatum=$row->enddatum;
  }
  if (!isset($_POST['kurs_speichern'])) { // Noch keine Eingabe
    echo "Du hast bis $enddatum Zeit, an der Wahl '$wahlname' teilzunehmen.<br>\n";
  } else { // Speichern der Eingabe
    $cmd="DELETE schueler_wahl FROM schueler_wahl JOIN user WHERE schueler_wahl.schueler_id=user.id and user.name='$schuelername' AND block='$block'";
    if (!($ok=mysql_query($cmd)))  if (mysql_errno()==1001) echo "<font color='red'>".mysql_error()."</font><br>"; else die(mysql_error());
    for ($wahl123=1; $ok&&($wahl123<=3); $wahl123++) {
      if (!isset($_POST['kurswahl_id'.$wahl123])) continue;
      $cmd="INSERT INTO schueler_wahl (schueler_id,kurs_id,prioritaet,block) SELECT id,".$_POST['kurswahl_id'.$wahl123].",$wahl123,$block FROM user WHERE user.name='$schuelername'";
      if (!($ok=mysql_query($cmd)))  if (mysql_errno()==1001) echo "<font color='red'>".mysql_error()."</font><br>"; else die(mysql_error());
    }
    if ($ok) {
      echo "Deine Wahl wurde gespeichert in Block $block.<br>"; 
      echo "Du kannst bis $enddatum die Wahl noch &auml;ndern.<br>";
    }
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
    if ($wahl_id>0) {
      $cmd="UPDATE  wahl_einstellungen SET ";
      if ($_POST['name']!="") $cmd.=" name='".$_POST['name']."',";
      if ($_POST['startdatum']!="") $cmd.=" startdatum='".$_POST['startdatum']."',";
      if ($_POST['enddatum']!="") $cmd.=" enddatum='".$_POST['enddatum']."',";
      if ($_POST['bloecke']!="") $cmd.=" bloecke='".$_POST['bloecke']."',";
      if ($_POST['min_tn']!="") $cmd.=" min_teilnehmer='".$_POST['min_tn']."',";
      if ($_POST['max_tn']!="") $cmd.=" max_teilnehmer='".$_POST['max_tn']."',";
      $cmd=substr($cmd,0,-1);
      $cmd.=" WHERE id='$wahl_id'";
    } else { // Neue Wahl wird angelegt
      $cmd=<<<END
      INSERT INTO wahl_einstellungen (name,startdatum,enddatum,bloecke)
      VALUES ('{$_POST['name']}','{$_POST['startdatum']}','{$_POST['enddatum']}','{$_POST['bloecke']}')
END;
    }
    mysql_query($cmd) or die ("$cmd: ".mysql_error());
    if ($wahl_id<0) {
      $wahl_id=mysql_insert_id();
      $_SESSION["wahl_id"]=$wahl_id;
    }
    echo "Die ge&auml;nderten Einstellungen wurden gespeichert.<br>";
  } else if (isset($_POST['wahl_loeschen'])) {
    echo "Wirklich l&ouml;schen?!?";
    echo "<form action='#' method='post'>"
    ."<input type='submit' name='wahl_loeschen_ok' value='Ja'>"
    ."<button name='wahl_id' value='".$_SESSION['wahl_id']."'>Nein</button></form>";
    exit;
  } else if (isset($_POST['wahl_loeschen_ok'])) {
    kurse_loeschen('%',TRUE);
    $cmd="DELETE FROM wahl_einstellungen WHERE id=$wahl_id";
    mysql_query($cmd) or die ("$cmd: ".mysql_error());
    include_once("wahl_festlegen.php");
    exit;
  }
  $klassen_options="";
  foreach(klassen_namen() as $id=>$klasse)
    $klassen_options.="<option value='".$klasse."'>".$klasse."</option>\n";
  $cmd="SELECT startdatum, enddatum,name, bloecke, min_teilnehmer, max_teilnehmer FROM wahl_einstellungen WHERE id='$wahl_id'";
  $ergebnis = mysql_query($cmd) or die (mysql_error());
  if (!$row = mysql_fetch_object($ergebnis)) {
    $row=(object)array("name"=>"","startdatum"=>"","enddatum"=>"", "bloecke"=>1);
  }
  echo <<<END
<form action="wahl_bearbeiten.php" method="post">
  <fieldset>
    <legend>Wahleinstellungen</legend>
    <label for="name">Bezeichnung: <input type="text" id="name" name="name" value="$row->name"> </label> <br>
    <label for="start">Startdatum: <input type="text" id="start" name="startdatum" value="$row->startdatum"> </label> <br>
    <label for="end">Enddatum:   <input type="text" id="end" name="enddatum" value="$row->enddatum"> </label> <br>
    <label for="blk">Anzahl Bl&ouml;cke (z.B. 4 Quartale): <input type="number" id="blk" name="bloecke" min="1" max="4" size="1" value="$row->bloecke"> </label> <br>
    <label for="min">Kursgr&ouml;&szlig;e: <input type="text" id="min" size="4" name="min_tn" value="$row->min_teilnehmer"> </label>
        <label for="max">bis  <input type="text" id="max" size="4" name="max_tn" value="$row->max_teilnehmer"> </label> <br>
    <input type="submit" name="wahleinstellungen_speichern" value="&Auml;nderungen speichern">
    <input type="reset" name="wahleinstellungen_reset" value="Verwerfen">
    <input type="submit" name="wahl_loeschen" value="Wahl l&ouml;schen?!?" disabled>
  </fieldset>
</form>
<form action="wahl_ergebnisse.php" method="post">
  <fieldset>
    <legend>Sch&uuml;ler-Eingaben</legend>
    <label>Auswahl der Klasse(n): <select name='klassen[]' multiple> $klassen_options </select> <label> <br>
    <label>Zum Testen: Eingaben aller Sch&uuml;ler aus den gew&auml;hlten Klassen zuf&auml;llig setzen<label> <input type="submit" name="klassen_simulation" value="OK"><br>
    <label>Zum Testen: Eingaben aller Sch&uuml;ler aus den gew&auml;hlten Klassen l&ouml;schen<label> <input type="submit" name="klassen_loeschen" value="OK"><br>
    <label>Eingaben der gew&auml;hlten Klassen anzeigen: <input type="submit" name="klassen_anzeigen" value="OK"><br>
  </fieldset>
</form>
<form action="kurs_zuteilung.php" method="post">
<input type="submit" value="Kurse zuteilen">
</form>
<form action="wahl_verpflichtungen.php" method="post">
<input type="submit" value="Wahleinschr&auml;nkungen eingeben">
</form>
END;
  if ($row = mysql_fetch_object($ergebnis)) {
    echo "Fehler: Zur Wahl $wahl_id gibt es mehrere Eintr&auml;ge!<br>";
  }
  if ($wahl_id>=0) {
    echo "Folgende Kurse k&ouml;nnen gew&auml;hlt werden:<br>";
    echo kurs_anzeige($wahl_id,-1,true,"kurs_bearbeiten.php");
    echo "<form action='kurse_einlesen.php' method='post'><input type='submit' name='einlesen' value='Text-Datei einlesen'></form>";
    //echo "<form action='odt_output.php' method='post'><input type='submit' name='download' value='Download'></form>";
  }
}

unset($_SESSION['kurs_id']);
if (isset($_POST['wahl_id']))
  $_SESSION['wahl_id']=$_POST['wahl_id'];
if (!isset($_SESSION['wahl_id']))
  header("Location: wahl_festlegen.php");
$wahl_id=$_SESSION['wahl_id'];

include 'db_connect.php';
check_login();

if (lehrer_angemeldet()) {  // Bearbeitung durch Lehrer
  wahl_einstellungen($wahl_id);
} else {                            // Bearbeitung durch Schüler
  wahl_teilnahme($wahl_id);
}
echo "<form action='wahl_festlegen.php' method='post'><input type='submit' value='Andere Wahl bearbeiten'></form>";

?>