<?php
session_start();
include 'db_connect.php';
include_once('abfragen.php');

/**
 * Es wird getestet, ob die eingegebene Kuerzelliste 
 * - nicht leer ist und
 * - keine Kürzel enthält, die bei anderen Kursen verwendet werden.
 * @param beschr_id ID des bearbeiteten Kurses
 * @param $eingabe Array mit keys "kuerzel" und "jahrgaenge"
 * @return array ("krz"=>("bloecke"=>..., "jahre"=>)) bzw. NULL bei Fehler.
 */
 
function eingabe_check($beschr_id, $eingabe) {
  $kuerzel=array_map('trim',$eingabe["kuerzel"]);
  $jahre=$eingabe["jahrgaenge"];
  $bloecke=$eingabe["bloecke"];
  $ok=TRUE;
  $ret=array();
  // Mindestens ein Kuerzel vorhanden?
  $n=0;
  foreach ($kuerzel as $k) {
    $ret[$k]=array();
    $ret[$k]["jahre"]=array();
    $ret[$k]["bloecke"]="";
    if ($k!="") ++$n;
  }
  if ($n<=0) {
    echo "Es muss mindestens ein K&uuml;rzel eingegeben werden.<br>";
    $ok=FALSE;
  }
  // Jahrgänge im richtigen Format?
  foreach($jahre as $i=>$jstring) if ($jstring) {
    $jarr=array_map('trim',split(",",$jstring));
    foreach($jarr as $j) {
      if (!preg_match("/^[0-9]+([a-z]|-[0-9]+)?$/",$j,$matches)) {
        echo "unzulaessige Jahrgangsangabe $j in $jstring<br>";
        $ok=FALSE;
      }
      if (preg_match("/^([0-9]+)-([0-9]+)$/",$j,$matches)) {
        foreach (range($matches[1],$matches[2]) as $j)
          $ret[$kuerzel[$i]]["jahre"][]=$j;
      } else {
        $ret[$kuerzel[$i]]["jahre"][]=$j;
      }
    }
  }
  // Blöcke von 1-4? (TODO: tatsächliche Block-Anzahl verwenden!)
  foreach($bloecke as $i=>$b) if ($b) {
    $b=trim($b);
    if ($b!="" && !preg_match("/^[1-4]$/",$b)) {
      echo "unzulaessige Block-Angabe '$b' in Zeile $i<br>";
      $ok=FALSE;
    }
    $ret[$kuerzel[$i]]["bloecke"]=$b;
  }
  // Kürzel aus anderen Kursen verwendet?
  $cmd="SELECT kuerzel,titel,beschr_id FROM kurse JOIN kurs_beschreibungen ON kurse.beschr_id=kurs_beschreibungen.id AND kurs_beschreibungen.wahl_id='".$_SESSION['wahl_id']."'";
  $ergebnis = mysql_query($cmd) or die (mysql_error());
  while ($row = mysql_fetch_object($ergebnis)) {
    foreach ($kuerzel as $k) {
      if ($row->kuerzel==$k && $row->beschr_id!=$beschr_id) {
        echo "Kuerzel '$k' existiert schon im Kurs '".$row->titel."'! <br>";
        $ok=FALSE;
      }
    }
  }
  unset($ret[""]);
  return $ok?$ret : NULL;
}

function zusaetze_anzeigen($beschr_id, $eingabe) {
  $zusaetze=zusatz_abfrage($_SESSION["wahl_id"],TRUE);
  $gewaehlt=zusatz_abfrage($_SESSION["wahl_id"],FALSE);
  $ret="<table border='0'><tr>\n";
  foreach ($zusaetze as $n=>$wa) {
    $ret.="  <td><fieldset><legend>Zusatz $n:</legend>\n";
    foreach ($wa as $id=>$w) {
      if ($eingabe) $checked=(isset($eingabe["zusatz_$id"]) && in_array($id,$eingabe["zusatz_$id"]))?"checked":"";
      else $checked=isset($gewaehlt[$n][$beschr_id][$id])?"checked":"";
      $ret.="    <input type='checkbox' name='zusatz_{$id}[]' value='$id' $checked>$w<br>\n";
    }
    $ret.="  </fieldset></td>\n";
  }
  $ret.="</tr></table>\n";
  return $ret;
}

/**
 * Ein existierender oder (falls $jurs_id==-1) nu anzulegender Kurs wird zur Bearbeitung angezeigt.
 * @param $beschr_id ID aus der Tabelle kurs_beschreibungen
 * @param $eingabe Array ggf. mit vorheriger Eingabe für Titel, Beschreibung und Kuerzel.
 */

function kurs_anzeigen($beschr_id, $eingabe) {
  $nbloecke=0;
  $cmd="SELECT bloecke FROM wahl_einstellungen WHERE id=".$_SESSION['wahl_id'];
  $ergebnis = mysql_query($cmd);
  if ($row = mysql_fetch_object($ergebnis)) {
    $nbloecke=$row->bloecke;
  } else {
    die ("Block-Anzahl unbekannt bei Kurs Nr. $beschr_id<br>");
  }
  if ($beschr_id==-1 && !$eingabe)
    $eingabe=array("titel"=>"", "beschr"=>"","kuerzel"=>array(),"bloecke"=>array(),"jahrgaenge"=>array());
  if ($eingabe) { // neuen Kurs eingeben oder vorherige falsche Eingabe überarbeiten
    $titel=$eingabe["titel"];
    $beschr=$eingabe["beschr"];
    foreach($eingabe["kuerzel"] as $i=>$k) {
      $jahrgaenge[$k]=$eingabe["jahrgaenge"][$i];
      $bloecke[$k]=$eingabe["bloecke"][$i];
    }
  } else { // Kurse aus DB anzeigen
    $abfrage = <<<END
SELECT kuerzel, block, GROUP_CONCAT(kurs_jahrgang.jahrgang) AS jahrgaenge, kurs_beschreibungen.titel, kurs_beschreibungen.beschreibung,
kurs_beschreibungen.wahl_id
FROM kurs_beschreibungen JOIN kurse ON kurs_beschreibungen.id=kurse.beschr_id
LEFT JOIN kurs_jahrgang ON kurs_jahrgang.kurs_id=kurse.id
WHERE kurse.beschr_id='$beschr_id'
GROUP BY kuerzel
END;
  $jahrgaenge=array();
  $ergebnis = mysql_query($abfrage) or die (mysql_error());
    while ($row = mysql_fetch_object($ergebnis)) {
      $titel=$row->titel;
      $beschr=$row->beschreibung;
      $jahrgaenge[$row->kuerzel]=$row->jahrgaenge;
      $bloecke[$row->kuerzel]=$row->block;
    }
  }
  $blockheader="<th>Block</th>";
  if ($nbloecke<=1) $blockheader=""; // Block nur anzeigen wenn mehr als 1 Block vorhanden
  $kj_table="<table border='1'><tr><th>K&uuml;rzel</th>$blockheader<th>Jahrg&auml;nge</th></tr>\n";
  if (!isset($jahrgaenge[''])) {
    $jahrgaenge['']='';
    $bloecke['']='';
  }
  foreach ($jahrgaenge as $k=>$j) {
    $blockfeld="<td><input type='text' name='bloecke[]' size='2' value='".$bloecke[$k]."'></td>";
    if ($nbloecke<=1) $blockfeld="<input type='hidden' name='bloecke[]' size='2' value='1'>";
    $kj_table.=<<<END
    <tr><td><input type='text' name='kuerzel[]' size='4' value='$k'></td>
    $blockfeld
    <td><input type='text' name='jahrgaenge[]' value='$j'></td></tr>
END;
  }
  $kj_table.="</table>";
  $zusaetze=zusaetze_anzeigen($beschr_id, $eingabe);
  return <<<END
<form action='kurs_bearbeiten.php' method='post'>
  <label> Titel: <input type='text' name='titel' value='$titel'> </label><br>
  <label> Beschreibung: <textarea name='beschr' rows='4' cols='80'>$beschr</textarea></label><br>
  $kj_table
  $zusaetze
  <input type='submit' name='bearbeitet' value='Speichern'>
  <input type='submit' name='bearbeitet' value='Cancel'>
</form>
END;
}

if (isset($_POST['edit']))
  $_SESSION['kurs_id']=$_POST['edit'];
if (isset($_POST['delete']))
  $_SESSION['kurs_id']=$_POST['delete'];
if (isset($_POST['add']))
  $_SESSION['kurs_id']=-1;

if (!isset($_POST['bearbeitet'])) {
  if (isset($_POST['edit'])||isset($_POST['add']))
    echo kurs_anzeigen($_SESSION['kurs_id'],NULL);
  elseif (isset($_POST['delete'])) {
    $cmd="SELECT name,klasse FROM schueler_wahl JOIN schueler WHERE schueler.id=schueler_wahl.schueler_id AND kurs_id='{$_POST['delete']}' GROUP BY name";
    $ergebnis = mysql_query($cmd) or die (mysql_error());
    $liste="";
    while ($row = mysql_fetch_object($ergebnis)) {
      $liste.=$row->name."(".$row->klasse.")<br>\n";
    }
    if ($liste!="") $liste="Der Kurs wurde von folgenden Sch&uuml;lern gew&auml;hlt:<br><i>$liste</i>";
    echo <<<END
Soll der Kurs wirklich gel&ouml;scht werden??<br>
$liste<br>
<form action="#" method='post'>
<button name="delete_confirm" value="{$_POST['delete']}">Ja</input>
<button name="delete_cancel" value="{$_POST['delete']}">Nein</input>
</form>
END;
  } elseif (isset($_POST['delete_confirm'])) {
    kurse_loeschen($_POST['delete_confirm'],TRUE);
    echo "Der Kurs wurde gel&ouml;scht.";
    include 'wahl_bearbeiten.php';
    exit;
  } elseif (isset($_POST['delete_cancel'])) {
    include 'wahl_bearbeiten.php';
    exit;
  } else {
    header("Location: login.php");
  }
} else { // Seite hat sich nach Kursbearbeitung selbst aufgerufen
  if ($_POST['bearbeitet']=="Speichern") {
    $eingabe=$_POST; //array("titel"=>$_POST["titel"], "beschreibung"=>$_POST["beschr"],"kuerzel"=>$_POST["kuerzel"],"jahrgaenge"=>$_POST["jahrgaenge"]);
    if (!$save=eingabe_check($_SESSION['kurs_id'],$eingabe)) {
      echo kurs_anzeigen($_SESSION['kurs_id'],$eingabe);
      exit;
    }
    if ($_SESSION['kurs_id']==-1) { // Neuen Kurs speichern
      $cmd=<<<END
INSERT INTO kurs_beschreibungen (wahl_id,titel,beschreibung)
VALUES('{$_SESSION['wahl_id']}','{$_POST['titel']}','{$_POST['beschr']}')
END;
      mysql_query($cmd) or die (mysql_error());
      $_SESSION['kurs_id']=mysql_insert_id();
      echo "Neuer Kurs ".$_SESSION['kurs_id']." wurde eingetragen.<br>";
    } else { // Vorhandenen Kurs aktualisieren
      $cmd=<<<END
UPDATE kurs_beschreibungen SET titel='{$_POST['titel']}', beschreibung='{$_POST['beschr']}'
WHERE id='{$_SESSION['kurs_id']}'
END;
      mysql_query($cmd) or die (mysql_error());
    }
    // Kürzel, Block,Jahrgänge für neuen oder existierenden Kurs löschen und neu eintragen
    kurse_loeschen($_SESSION['kurs_id'],FALSE);
    foreach (array_keys($save) as $k) {
      $b=$save[$k]["bloecke"];
      $cmd=<<<END
INSERT INTO kurse (beschr_id,kuerzel,block) VALUES('{$_SESSION['kurs_id']}','$k','$b')
END;
      mysql_query($cmd) or die ("$cmd: ".mysql_error());
      $kurs_id=mysql_insert_id();
      foreach (array_unique($save[$k]["jahre"]) as $j) {
        $cmd="INSERT INTO kurs_jahrgang (kurs_id,jahrgang) VALUES ('$kurs_id','$j')";
        mysql_query($cmd) or die ($cmd.": ".mysql_error());
      }
    }
    // Zusätze löschen und neu eintragen
    $cmd=<<<END
DELETE FROM kurs_zusaetze WHERE kurs_id='{$_SESSION['kurs_id']}'
END;
    mysql_query($cmd) or die ($cmd.": ".mysql_error());
    $cmd="INSERT INTO kurs_zusaetze (kurs_id,zusatz_wert_id) VALUES ";
    foreach ($eingabe as $e=>$v) if (preg_match("/^zusatz_(\d+)$/",$e,$matches)) {
      foreach ($v as $wert) $cmd.="(".$_SESSION['kurs_id'].",$wert),";
    }
    if (substr($cmd,-1)==",") {
      $cmd=substr($cmd,0,-1);
      mysql_query($cmd) or die ($cmd.": ".mysql_error());
    }
    echo "Der Kurs wurde gespeichert.<br>";
  } else {
    echo "Die Kursbearbeitung wurde abgebrochen.<br>";
  }
  include 'wahl_bearbeiten.php';
}
?>
