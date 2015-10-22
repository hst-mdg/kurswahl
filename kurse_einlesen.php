<?php
session_start();
include 'db_connect.php';

function info() {
  echo <<<END
<h1>Kurse aus Textdatei einlesen</h1>
Zum Einlesen eines vorhandenen Kurs-Verzeichnisses muss dieses in folgendem Format (als TXT-Datei) vorliegen:<br>
<pre><table border='0'>
<tr><td>1) sprachlich </td><td><i>(oder fremdsprachlich,mathematisch-naturwissenschaftlich,...)</i></td></tr>
<tr><td>Titel des 1. Kurses</td><td></td></tr>
<tr><td>K </td><td><i>(oder B oder K/B)</i></td></tr>
<tr><td>1. Quartal Axy </td><td><i>(Quartals-Angabe und K&uuml;rzel.)</i></td></tr>
<tr><td>9-10 </td><td><i>(Jahrg&auml;nge oder einzelne Klassen zB.: 6a/6b/7-9)</i></td></tr>
<tr><td>2. Quartal Bxy </td><td><i>(Ggf. weitere Quartals-Angabe und K&uuml;rzel.)</i></td></tr>
<tr><td>7-8 </td><td><i>(und Klassen zum 2. K&uuml;rzel)</i></td></tr>
<tr><td>Beschreibung des Kurses,</td><td></td></tr>
<tr><td>die mehrere Zeilen aber</td><td></td></tr>
<tr><td>keine Leerzeile enthalten darf.</td><td></td></tr>
<tr><td></td>&nbsp;<td><i>Leerzeile kennzeichnet Ende der Beschreibung!</i></td></tr>
<tr><td>Titel des 2. Kurses</td><td></td></tr>
<tr><td>...</td><td></td></tr>
<tr><td></td>&nbsp;<td>&nbsp;</td></tr>
<tr><td>2) fremdsprachlich<td></td></tr>
<tr><td>Titel ...</td><td></td></tr>
<tr><td>...</td><td></td></tr>
</table>
</pre>
END;
}

function bereiche($wahl_id, $name) {
  $ret=array();
  $cmd="SELECT zusatz_werte.wert AS wert, zusatz_werte.id AS id FROM zusatz_werte JOIN zusatz ON zusatz_werte.zusatz_id=zusatz.id AND zusatz.wahl_id='$wahl_id' AND zusatz.name='$name'";
  $ergebnis=mysql_query($cmd) or die ("$cmd<br>Fehler beim Abfragen der Bereiche: ".mysql_error());
  while($row=mysql_fetch_object($ergebnis)) {
    $ret[$row->wert]=$row->id;
  }
  return $ret;
}

function upload() {
  if(isset($_POST["submit"])) {
    $check = $_FILES["fileToUpload"]["size"];
    if($check <=0 || $check>100000) {
      echo "Die Datei " . $check["mime"] . " ist leer oder zu gro&szlig;";
      return NULL;
    }
    return $_FILES["fileToUpload"]["tmp_name"];
  } else {
    return NULL;
  }
}

function db_zusatzfelder_anlegen() { // KB und Bereiche bei neuer Modulwahl eintragen
  global $wahl_id;
  global $bereich_id, $kb_id;
  define("SPRACHLICH","sprachlich");
  define("FREMDSPR","fremdsprachlich");
  define("MATNAW","mathematisch-naturwissenschaftlich");
  define("GESELLSCHAFT","gesellschaftlich");
  define("LERNEN","lernorganisatorisch");
    
  $bereich_id=array();
  $kb_id=array();
  $cmd="SELECT name FROM zusatz WHERE wahl_id='$wahl_id'";
  $ergebnis=mysql_query($cmd);
  $namen=array();
  while($row = mysql_fetch_object($ergebnis)) {
    $namen[]=$row->name;
  }
  if (!in_array("KB",$namen)) {
    $cmd="INSERT INTO zusatz (wahl_id,name) VALUES('$wahl_id','KB')";
    mysql_query($cmd) or die ("Konnte KB-Feld nicht anlegen: ".mysql_error());
    $kb_id=mysql_insert_id();
    if ($kb_id<1) die ("falsche kb_id=$kb_id");
    $cmd="INSERT INTO zusatz_werte (zusatz_id,wert) VALUES('$kb_id','K')";
    mysql_query($cmd) or die ("Fehler bei KB-Werten: $cmd: ".mysql_error());
    $cmd="INSERT INTO zusatz_werte (zusatz_id,wert) VALUES('$kb_id','B')";
    mysql_query($cmd) or die ("Fehler bei KB-Werten: $cmd: ".mysql_error());
  }
  if (!in_array("Bereiche",$namen)) {
    $cmd="INSERT INTO zusatz (wahl_id,name) VALUES('$wahl_id','Bereiche')";
    mysql_query($cmd) or die ("Konnte Bereiche-Feld nicht anlegen: $cmd: ".mysql_error());
    $ber_id=mysql_insert_id();
    if ($ber_id<1) die ("falsche ber_id=$ber_id");
    foreach ([SPRACHLICH,FREMDSPR,MATNAW,GESELLSCHAFT,LERNEN] as $ber) {
      $cmd="INSERT INTO zusatz_werte (zusatz_id,wert) VALUES('$ber_id','$ber')";
      mysql_query($cmd) or die ("$cmd Fehler bei Bereich-Werten: ".mysql_error());
    }
  }
  // ids der Zusatzfelder speichern:
  $cmd="SELECT zusatz_werte.id,wert FROM zusatz_werte JOIN zusatz ON zusatz.name='KB' AND zusatz.id=zusatz_werte.zusatz_id AND zusatz.wahl_id='$wahl_id'";
  $res=mysql_query($cmd) or die ("Konnte KB-Werte nicht abfragen: ".mysql_error());
  $kb_id=array();
  while ($row=mysql_fetch_object($res)) {
    $kb_id[$row->wert]=$row->id;
  }
  mysql_free_result($res);
  $cmd="SELECT zusatz_werte.id,wert FROM zusatz_werte JOIN zusatz ON zusatz.name='Bereiche' AND zusatz.id=zusatz_werte.zusatz_id AND zusatz.wahl_id='$wahl_id'";
  $res=mysql_query($cmd) or die ("Konnte Bereiche-Werte nicht abfragen: ".mysql_error());
  while ($row=mysql_fetch_object($res)) {
    $bereich_id[$row->wert]=$row->id;
  }
  mysql_free_result($res);
}

function db_eintrag($titel,$beschr,$bereich, $quartal_kuerzel,$kb) { // Modul mit Kuerzel(n) usw. eintragen
  global $wahl_id, $bereich_id, $kb_id, $eingetragen;
  if (!isset($eingetragen)) $eingetragen=array();
  $titel=htmlentities($titel);
  $beschr=htmlentities($beschr);
  $titeleingetragen=FALSE;
  // Kurs mit Quartal, Kürzel und (ggf. mehreren) Jahrgängen eintragen:
  foreach ($quartal_kuerzel as $j=>$qk) {
    foreach ($qk as $k=>$q) {
      if (!isset($eingetragen[$k])) {
        // Kurs-Beschreibung eintragen:
        if (!$titeleingetragen) {
          $cmd=<<<END
INSERT INTO kurs_beschreibungen (wahl_id,titel,beschreibung)
VALUES('$wahl_id','$titel','$beschr')
END;
          mysql_query($cmd) or die("Kann die Kurs-Beschreibung nicht in die DB eintragen: ".mysql_error());
          $beschr_id=mysql_insert_id();
          $titeleingetragen=TRUE;
        }
        $cmd=<<<END
INSERT INTO kurse (block,kuerzel,beschr_id) VALUES('$q','$k','$beschr_id')
END;
        if (!mysql_query($cmd)) {
          echo "Kann den Kurs nicht in die DB eintragen: ".mysql_error()." ($titel)<br>";
          continue;
        }
        $kurs_id=mysql_insert_id();
        $eingetragen[$k]=array("kurs"=>$kurs_id,"beschr_id"=>$beschr_id,"jahre"=>array(),"zusaetze"=>array());
      } else {
        $beschr_id=$eingetragen[$k]["beschr_id"];
        $kurs_id=$eingetragen[$k]["kurs"];
      }
      if (in_array($j,$eingetragen[$k]["jahre"])) continue; // Kürzel/Jahrgang schon vorher eingetragen
      $cmd="INSERT INTO kurs_jahrgang (kurs_id,jahrgang) VALUES('$kurs_id','$j')";
      if (!mysql_query($cmd)) {
        echo "Kann die Klasse/Jahrgang nicht in die DB eintragen: ".mysql_error()." ($titel)<br>";
        continue;
      }
      $eingetragen[$k]["jahre"][]=$j;
    }
  }
  // Bereich eintragen:
  if (!in_array($bereich_id[$bereich],$eingetragen[$k]["zusaetze"])) {
    $cmd="INSERT INTO kurs_zusaetze (kurs_id,zusatz_wert_id) VALUES('$beschr_id','$bereich_id[$bereich]')";
    mysql_query($cmd) or die("Kann den Bereich nicht setzen: $cmd: ".mysql_error());
    $eingetragen[$k]["zusaetze"][]=$bereich_id[$bereich];
  }
  // K/B eintragen:
  if ($kb==NULL || sizeof($kb)==0) {
    echo "Kein K/B in $titel<br>";
  } else foreach ($kb as $kb_eintrag) {
    if (!in_array($kb_id[$kb_eintrag],$eingetragen[$k]["zusaetze"])) {
      $cmd="INSERT INTO kurs_zusaetze (kurs_id,zusatz_wert_id) VALUES('$beschr_id','$kb_id[$kb_eintrag]')";
      mysql_query($cmd) or die("Kann den Bereich nicht setzen: ".mysql_error());
      $eingetragen[$k]["zusaetze"][]=$kb_id[$kb_eintrag];
    }
  }
}

/* Eine Textdatei wird in die Datenbank übertragen. Sie muss folgendes Format haben:
1) Sprachlich
Titel
K/B
6-7
1. Quartal Axy
2. Quartal Bxy
8a / 8b
1. Quartal Azy
Beschreibung Zeile 1
Beschreibung Zeile 2
...

Titel
...

2) Fremdsprachlich
...
*/
function einlesen($fname) {
  $f = file($fname);
  $titel="";
  $beschr="";
  $bereich="";
  foreach($f as $zeile) {
    if (preg_match("/^\s*$/",$zeile)) { // Leerzeile -> Eintrag beenden
      //echo "LEERZEILE: $zeile<br>";
      if ($titel=="") continue;
      db_eintrag($titel,$beschr,$bereich,$quartal_kuerzel,$kb);
      echo "Kurs eingetragen: '".htmlentities($titel)."'<br>\n";
      $titel="";
      $beschr="";
      continue;
    }
    if ($beschr!="") { // Wenn Beschreibung begonnen hat, gehren alle weiteren Zeilen bis zur Leerzeile zur Beschreibung.
      $beschr.=$zeile;
      continue;
    }
    if (preg_match("/^[0-9]+\) (.*)/",$zeile,$matches)) { // Z.B. "1) Sprachliche Kompetenzen
      if (preg_match("/fremdsprachl/i",$matches[0])) {
        $bereich=FREMDSPR;
      } elseif (preg_match("/sprachl/i",$matches[0])) {
        $bereich=SPRACHLICH;
      } elseif (preg_match("/mathe.*naturw/i",$matches[0])) {
        $bereich=MATNAW;
      } elseif (preg_match("/gesellsch/i",$matches[0])) {
        $bereich=GESELLSCHAFT;
      } elseif (preg_match("/lernorga/i",$matches[0])) {
        $bereich=LERNEN;
      }
      //echo "!!! Bereich: $bereich<br>";
      $titel="";
      continue;
    }
    if ($titel=="") { // Vorher Leerzeile oder zB. "1)Sprachliche Kompetenzen" -> Neuer Titel erwartet
      $titel=$zeile;
      //echo "!!! Titel: $titel<br>";
      $beschr="";
      $klassen=array();
      $jmin="";
      $jmax="";
      $kb="";
      $quartal_kuerzel=array();
      continue;
    }
    if ($titel!="" && preg_match("/^([0-9]+)(-([0-9]+))*$/",$zeile,$matches)) { // z.B. 7-10
      $jmin=$matches[1];
      $jmax=$jmin;
      if (sizeof($matches)>=3) {
        $jmax=substr($matches[2],1);
      }
      //echo "!!! Jahre: $jmin-$jmax <br>";
      continue;
    }
    if ($titel!="" && preg_match("/^[0-9]+[a-f](\s*\/\s*[0-9]+[a-f])*/",$zeile)) { // z.B. 9a / 9b / 10d
      $klassen=preg_split("/\s*\/\s*/",$zeile);
      //echo "!!! Klassen: "; print_r($klassen); echo "<br>";
      continue;
    }
    if ($titel!="" && preg_match("/^[KB](\s*\/\s*[KB])*$/",$zeile,$matches)) { // K/B
      if ($kb!="") die("mehrfache K/B Einträge in $titel!");
      $kb=preg_split("/\s*\/\s*/",$matches[0]);
      //echo "!!! KB: ".join("/",$kb)."<br>";
      continue;
    }
    if ($titel!="" && preg_match("/^([1-4]). Quartal ([A-D][a-z0-9][a-z0-9])$/",$zeile,$matches)) { // z.B. 4. Quartal Dxs
      $q=$matches[1];
      $k=$matches[2];
      for ($j=$jmin; $j<=$jmax; $j++) { 
        $quartal_kuerzel[$j][$k]=$q;
      }
      foreach ($klassen as $kl) {
        $quartal_kuerzel[$kl][$k]=$q;
      }
      //echo "!!!! Kuerzel/Quartale: "; print_r($quartal_kuerzel); echo "<br>";
      continue;
    }
    $beschr=$zeile; // Kein besonderes Format -> Beschreibung fängt an.
    //echo "!!! Beschreibung: $beschr<br>";
  }
}

$wahl_id=$_SESSION['wahl_id'];
if (isset($_POST['einlesen'])) {
  if (isset($_FILES["fileToUpload"]["name"])) {
echo <<<END
<form action="wahl_bearbeiten.php" method="post">
    <input type="hidden" name="wahl_id" value="$wahl_id"/>
    <!--textarea cols="80" rows="20"-->
END;
    $tmp=upload();
    if ($tmp!=NULL) {
      db_zusatzfelder_anlegen();
      einlesen($tmp);
    }
    echo "<!--/textarea-->";
    echo '<input type="submit" value="Zur&uuml;ck"/><br> </form>';
  } else {
    info();
    echo <<<END
    <form action="kurse_einlesen.php" method="post" enctype="multipart/form-data">
      Textdatei ausw&auml;hlen:
      <input type="hidden" name="einlesen"/>
      <input type="hidden" name="wahl_id" value="$wahl_id"/>
      <input type="file" name="fileToUpload" id="fileToUpload">
      <input type="submit" value="Hochladen" name="submit">
  </form>
END;
  }
} elseif (isset($_POST['uebernehmen'])) {
  echo "Module aus Wahl ".$_POST['uebernehmen']." uebernehmen.<br>Noch nicht moeglich.<br>";
}
?>