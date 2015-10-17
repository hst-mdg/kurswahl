<?php
session_start();
include 'db_connect.php';

/**
 * Es wird getestet, ob die Kuerzelliste 
 * - nicht leer ist und
 * - keine Kürzel enthält, die bei anderen Kursen verwendet werden.
 * @param beschr_id ID des bearbeiteten Kurses
 * @param kuerzelliste Durch Komma getrennte Liste von Kürzeln
 * @return array aus einzelnen Kürzeln bzw. NULL bei Fehler.
 */
 
function kuerzel_abfrage($beschr_id, $kuerzelliste) {
  $kuerzel=array_map('trim',split(",",$kuerzelliste));
  if ($kuerzelliste=="" || sizeof($kuerzel)<=0) {
    echo "Es muss mindestens ein K&uuml;rzel eingegeben werden.<br>";
    return FALSE;
  }
  $cmd="SELECT kuerzel,beschr_id FROM kurse JOIN kurs_beschreibungen ON kurse.beschr_id=kurs_beschreibungen.id AND kurs_beschreibungen.wahl_id='".$_SESSION['wahl_id']."'";
  $ergebnis = mysql_query($cmd) or die (mysql_error());
  $ok=TRUE;
  while ($row = mysql_fetch_object($ergebnis)) {
    foreach ($kuerzel as $k) {
      if ($row->kuerzel==$k && $row->beschr_id!=$beschr_id) {
        echo "Kuerzel $k existiert schon! ".$row->beschr_id." ".$beschr_id."<br>";
        $ok=FALSE;
      }
    }
  }
  return $ok?$kuerzel : NULL;
}

/**
 * Ein existierender oder (falls $jurs_id==-1) nu anzulegender Kurs wird zur Bearbeitung angezeigt.
 * @param $beschr_id ID aus der Tabelle kurs_beschreibungen
 * @param $eingabe Array ggf. mit vorheriger Eingabe für Titel, Beschreibung und Kuerzel.
 */

function kurs_anzeigen($beschr_id, $eingabe) {
  $abfrage = <<<END
SELECT GROUP_CONCAT(kurse.kuerzel) as kuerzel, kurs_beschreibungen.titel, kurs_beschreibungen.beschreibung,
kurs_beschreibungen.wahl_id
FROM kurs_beschreibungen JOIN kurse
ON kurs_beschreibungen.id=kurse.beschr_id
AND kurse.beschr_id='$beschr_id'
GROUP BY kurse.beschr_id
END;
  if ($beschr_id==-1 && !$eingabe)
    $eingabe=array("titel"=>"", "beschreibung"=>"","kuerzel"=>"");
  if ($eingabe) { // neuen Kurs eingeben
    $row=(object) $eingabe;
  } else {
    $ergebnis = mysql_query($abfrage) or die (mysql_error());
    if (!$row = mysql_fetch_object($ergebnis)) {
      die (mysql_error());
    }
  }
  return <<<END
<form action='kurs_bearbeiten.php' method='post'>
  <label> Titel: <input type='text' name='titel' value='$row->titel'> </label><br>
  <label> Beschreibung: <textarea name='beschr' rows='4' cols='80'>$row->beschreibung</textarea></label><br>
  <label> K&uuml;rzel: <input type='text' name='kuerzel' value='$row->kuerzel'></label><br>
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
    $cmd="DELETE FROM schueler_wahl WHERE kurs_id='{$_POST['delete_confirm']}'";
    mysql_query($cmd) or die ($cmd.": ".mysql_error());
    $cmd="DELETE FROM kurse WHERE beschr_id='{$_POST['delete_confirm']}'";
    mysql_query($cmd) or die ($cmd.": ".mysql_error());
    $cmd="DELETE FROM kurs_beschreibungen WHERE id='{$_POST['delete_confirm']}'";
    mysql_query($cmd) or die ($cmd.": ".mysql_error());
    echo "Der Kurs wurde gel&ouml;scht.";
    include 'wahl_bearbeiten.php';
    exit;
  } elseif (isset($_POST['delete_cancel'])) {
    print_r($_SESSION);
    include 'wahl_bearbeiten.php';
    exit;
  } else {
    header("Location: login.php");
  }
} else { // Seite hat sich nach Kursbearbeitung selbst aufgerufen
  if ($_POST['bearbeitet']=="Speichern") {
    $eingabe=array("titel"=>$_POST["titel"], "beschreibung"=>$_POST["beschr"],"kuerzel"=>$_POST["kuerzel"]);
    if (!$kuerzelarray=kuerzel_abfrage($_SESSION['kurs_id'],$_POST['kuerzel'])) {
      echo kurs_anzeigen($_SESSION['kurs_id'],$eingabe);
      exit;
    }
    if ($_SESSION['kurs_id']==-1) {
      $cmd=<<<END
INSERT INTO kurs_beschreibungen (wahl_id,titel,beschreibung)
VALUES('{$_SESSION['wahl_id']}','{$_POST['titel']}','{$_POST['beschr']}')
END;
      mysql_query($cmd) or die (mysql_error());
      $_SESSION['kurs_id']=mysql_insert_id();
    } else {
      $cmd=<<<END
UPDATE kurs_beschreibungen SET titel='{$_POST['titel']}', beschreibung='{$_POST['beschr']}'
WHERE id='{$_SESSION['kurs_id']}'
END;
    }
    mysql_query($cmd) or die (mysql_error());
    $cmd="DELETE FROM kurse WHERE beschr_id = '".$_SESSION['kurs_id']."'";
    mysql_query($cmd) or die (mysql_error());
    foreach ($kuerzelarray as $k) {
      $cmd=<<<END
INSERT INTO kurse (beschr_id,kuerzel,block) VALUES('{$_SESSION['kurs_id']}','$k','1')
END;
      // TODO: block Eingabemöglichkeit + Speichern
      mysql_query($cmd) or die (mysql_error());
    }
    echo "Der Kurs wurde gespeichert.<br>";
  } else {
    echo "Die Kursbearbeitung wurde abgebrochen.<br>";
  }
  include 'wahl_bearbeiten.php';
}
?>
