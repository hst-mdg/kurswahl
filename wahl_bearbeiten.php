<?php
if (!isset($_SESSION)) session_start();
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
    $abfrage="SELECT kurs_id,prioritaet FROM schueler_wahl JOIN schueler ON schueler.name='".$_SESSION['schuelername']."' AND schueler_id=schueler.id AND schueler_wahl.block=$block";
    $ergebnis = mysql_query($abfrage) or die (mysql_error());
    while($row = mysql_fetch_object($ergebnis)) {
      $selected[$row->kurs_id][$row->prioritaet]=true;
    }
  }
  
  if (isset($_SESSION['lehrername'])) {
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
  $zusaetze=zusatz_abfrage($wahl_id);
  $zusaetze=array_map(function($a){ return join("/",$a); },$zusaetze);
  $tmp=kuerzeljahr_abfrage($wahl_id);
  foreach ($tmp as $id=>$a) {
    $kuerzel[$id]=join("<br>",array_keys($a));
    foreach(array_keys($a) as $k) {
        if (!isset($jahre[$id])) $jahre[$id]=""; else $jahre[$id].="<br>";
        $jahre[$id].=join(",",$a[$k]);
      }
  }
  $abfrage = <<<END
SELECT kurs_beschreibungen.id as kursid, kurs_beschreibungen.titel, kurs_beschreibungen.beschreibung
FROM kurs_beschreibungen
LEFT JOIN kurse ON kurs_beschreibungen.id=kurse.beschr_id
LEFT JOIN kurs_jahrgang ON kurse.id=kurs_jahrgang.kurs_id
WHERE kurs_beschreibungen.wahl_id='$wahl_id'
AND $jahrgang_cond
GROUP BY kursid
END;
  $ergebnis = mysql_query($abfrage) or die (mysql_error());
  if (isset($_SESSION['lehrername'])) {
    $col1="<th>&nbsp;</th>";
  } elseif (isset($_SESSION['schuelername'])) {
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
<table border='1'>
  <tr>$col1
    <th>Titel</th>
    <th>Kuerzel</th>
    <th>Jahre</th>
    <th>Beschreibung</th>
    <th>Bereich</th>
  </tr>
END;
  if ($lehrer) {
    $ret.="<td><button type='submit' name='add' value='-1'><image src='img/add.png'></button></td></button></td>"
      ."<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>";
  }
  while($row = mysql_fetch_object($ergebnis)) {
    if (!isset($zusaetze[$row->kursid])) $zusaetze[$row->kursid]="---";
    if ($lehrer) {
      $button="<td><button type='submit' name='edit' value='".$row->kursid."'><image src='img/edit.png'></button>"
        ."<button type='submit' name='delete' value='".$row->kursid."'><image src='img/remove.png'></button></td>";
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
    <td>$row->titel</td>
    <td>{$kuerzel[$row->kursid]}&nbsp;</td>
    <td>{$jahre[$row->kursid]}&nbsp;</td>
    <td>$row->beschreibung &nbsp;</td>
    <td>{$zusaetze[$row->kursid]} &nbsp;</td>
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
 * Speichert alle Kurs-Zusätze (z.B. sprachlich/mathematisch) in einem Array.
 * @param wahl_id ID der zur Teilnahme ausgewählten Wahl
 * @return Array mit den Zusätzen (kurs_id => Bereich)
 */
function zusatz_abfrage($wahl_id) {
  $cmd=<<<END
  SELECT kb.id, zw.wert FROM kurs_beschreibungen AS kb
  JOIN kurs_zusaetze AS kz ON kz.kurs_id=kb.id
  JOIN zusatz_werte AS zw ON zw.id=kz.zusatz_wert_id
  JOIN zusatz ON zw.zusatz_id=zusatz.id
  WHERE zusatz.name='Bereiche'
  AND kb.wahl_id='$wahl_id'
END;
  $ergebnis = mysql_query($cmd) or die (mysql_error());
  $ret=array();
  while($row = mysql_fetch_object($ergebnis)) {
    $ret[$row->id][]=$row->wert;
  }
  return $ret;
}

/**
 * Speichert alle Kurs-Jahrgangs-Zuordnungen und Kürzel in einem Array
 * @param wahl_id ID der zur Teilnahme ausgewählten Wahl
 * @return 3D Array mit den Kuerzeln und Jahrgängen (kurs_id => (Kuerzel => Jahrgang)
 */
function kuerzeljahr_abfrage($wahl_id) {
  $cmd=<<<END
  SELECT kb.id, kj.jahrgang, k.kuerzel FROM kurs_beschreibungen AS kb
  JOIN kurse AS k ON k.beschr_id=kb.id
  JOIN kurs_jahrgang AS kj ON kj.kurs_id=k.id
  WHERE kb.wahl_id='$wahl_id'
END;
  $ergebnis = mysql_query($cmd) or die (mysql_error());
  $ret=array();
  while($row = mysql_fetch_object($ergebnis)) {
    if (!isset($ret[$row->id])) $ret[$row->id]=array();
    $ret[$row->id][$row->kuerzel][]=$row->jahrgang;
  }
  return $ret;
}

/**
 * Diese Funktion zeigt alle wählbaren Kurse an und speichert die Eingaben des Schülers
 * @param wahl_id ID der zur Teilnahme ausgewählten Wahl
 */
function wahl_teilnahme($wahl_id) {
  $schuelername=$_SESSION['schuelername'];
  $wahlname="???"; $enddatum="???";
  $block=1;
  if (isset($_SESSION['block']))
    $block=$_SESSION['block'];
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
    mysql_query($cmd) or die (mysql_error());
    echo "Die ge&auml;nderten Einstellungen wurden gespeichert.<br>";
  }
  $cmd="SELECT startdatum, enddatum,name, bloecke FROM wahl_einstellungen WHERE id='$wahl_id'";
  $ergebnis = mysql_query($cmd) or die (mysql_error());
  if ($row = mysql_fetch_object($ergebnis)) {
    echo <<<END
<form action="wahl_bearbeiten.php" id="einstellungen" method="post">
  <fieldset>
    <legend>Wahleinstellungen</legend>
    <label>Bezeichnung: <input type="text" name="name" placeholder="$row->name"> <label> <br>
    <label>Startdatum: <input type="text" name="startdatum" placeholder="$row->startdatum"> </label> <br>
    <label>Enddatum:   <input type="text" name="enddatum" placeholder="$row->enddatum"> </label> <br>
    <label>Anzahl Bloecke (z.B. 4 Quartale): <input type="number" name="bloecke" min="1" max="4" size="1" value="$row->bloecke"> </label> <br>
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

unset($_SESSION['kurs_id']);
if (isset($_POST['wahl_id']))
  $_SESSION['wahl_id']=$_POST['wahl_id'];
if (!isset($_SESSION['wahl_id']))
  header("Location: wahl_festlegen.php");
$wahl_id=$_SESSION['wahl_id'];

include 'db_connect.php';

if (isset($_SESSION['lehrername'])) {  // Bearbeitung durch Lehrer
  wahl_einstellungen($wahl_id);
} else {                            // Bearbeitung durch Schüler
  wahl_teilnahme($wahl_id);
}

?>