<?php
session_start();
include 'db_connect.php';

function model_download() {
$cmd="SELECT min_teilnehmer, max_teilnehmer,bloecke FROM wahl_einstellungen WHERE id='".$_SESSION['wahl_id']."'";
$res=mysql_query($cmd) or die("$cmd: ".mysql_error());
$bloecke=0;
if ($row=mysql_fetch_object($res)) {
  $min=$row->min_teilnehmer;
  $max=$row->max_teilnehmer;
  $bloecke=$row->bloecke;
}
if ($bloecke!=1) {
  echo("<font color='red'>Die automatische Zuteilung ist nur bei Wahlen mit einem einzelnen Block (nicht bei 4 Quartalen) m&ouml;glich.</font><br>");
  include('wahl_bearbeiten.php');
  exit;
}

$cmd=<<<END
SELECT s.name, k.kuerzel, sw.prioritaet FROM schueler AS s
JOIN schueler_wahl AS sw ON sw.schueler_id=s.id
JOIN kurse AS k ON k.id=sw.kurs_id
JOIN kurs_beschreibungen as kb ON kb.id=k.beschr_id
WHERE kb.wahl_id='{$_SESSION['wahl_id']}'
END;
$res=mysql_query($cmd) or die("$cmd: ".mysql_error());
while ($row=mysql_fetch_object($res)) {
  $krz[$row->kuerzel]=preg_replace("/[-+;\\s><=:]/","_",$row->kuerzel);
  $wahl[$row->name][$row->prioritaet]=$krz[$row->kuerzel];
}
//print_r($wahl);
foreach ($krz as $k=>$r) {
  foreach ($krz as $k1=>$r1) {
    if ($k!=$k1 && $r==$r1) {
      die("Die Kuerzel $k und $k1 sind nicht zul&auml;ssig.<br>");
    }
  }
}
// Ausgabe der lpsolve-Datei
$minstr="";
$summe1_bedingungen="";
$teilnehmerzahl_bedingungen="";
$definitionen="";
$tn_bed=array();
$schueler_anzahl=0;
foreach ($wahl as $s=>$w) {
  if (sizeof($w)<3) {
    echo "$s hat nicht I/II/III.Wahl angegeben -- wird ignoriert.<br>";
    continue;
  }
  $minstr.="+{$s}_".$w[1]."+10 {$s}_".$w[2]."+100 {$s}_".$w[3];
  ++$schueler_anzahl;
  $summe1_bedingungen.="{$s}_".$w[1]."+{$s}_".$w[2]."+{$s}_".$w[3]."=1;\n";
  foreach ($w as $k) {
    if (!isset($tn_bed[$k]))
      $tn_bed[$k]="{$s}_$k";
    else
      $tn_bed[$k].="+{$s}_$k";
    $definitionen.="int {$s}_$k;\n";
  }
}
$kurs_anzahl=sizeof($tn_bed);
$mittel=round($schueler_anzahl*1.0/$kurs_anzahl,1);
$fehler="";
if ($mittel>$max) $fehler=", <font color='red'>das sind mehr als die maximale Anzahl!</font>";
if ($mittel<$min) $fehler=", <font color='red'>das sind weniger als die minimale Anzahl!</font>";
foreach ($tn_bed as $k=>$summe) {
  $teilnehmerzahl_bedingungen.="Anzahl_$k: $min<=$summe<=$max;\n";
}

$lp="min: $minstr;\n$summe1_bedingungen\n$teilnehmerzahl_bedingungen\n$definitionen";
$html_lp=htmlentities($lp);
echo <<<END
<h1>Kurs-Zuteilung</h1>
<h2>Eingabe f&uuml;r lp_solve</h2>
Es wird das 'lp_solve' Programm (<a href="http://sourceforge.net/projects/lpsolve/">http://sourceforge.net/projects/lpsolve/</a>) ben&ouml;tigt, um die Zuteilung zu ermitteln.
Laden Sie folgende Eingabe-Datei herunter und starten Sie lp_solve damit:
<form action="lp_download.php" method="post">
<input type="hidden" name="lp" value="$lp">
<input type="submit" value="Download">
</form>
<textarea cols="120" rows="10" style="font-size: 0.7em;"> $html_lp </textarea>

<h2>Ausgabe von lp_solve</h2>
Wenn lp_solve keine L&ouml;sung findet, versuchen Sie, die maximale Teilnehmerzahl
zu erh&ouml;hen (bzw. die minimale zu verringern oder bei einzelnen kaum gew&auml;hlten
Kursen max=min=0 zu setzen, um sie ganz von der Wahl auszuschlie&szlig;en).<br>
Im Moment werden $schueler_anzahl Teilnehmer auf $kurs_anzahl Kurse verteilt (d.h. im Mittel $mittel pro Kurs$fehler), wobei jeder Kurs mindestens
$min und hoechstens $max Teilnehmer erhalten soll.<br>
<br>
Die Ausgabe erfolgt in der Form:
<pre>
Schueler.Name_Krz1     0
Schueler.Name_Krz2     1
Schueler.Name_Krz3     0
...
</pre>
Die Zeilen mit 1 am Ende geben jeweils den zugeteilten Kurs an, im Beispiel haette
Schueler.Name den Kurs "Krz2" bekommen.
<br>
END;

solution_upload();
}

function solution_upload() {
echo <<<END
<h2>Ergebnis speichern</h2>
Um die Ausgabe zu uebernehmen, speichern Sie diese in einer Text-Datei und laden Sie diese hoch:
<form action="kurs_zuteilung.php" method="post" enctype="multipart/form-data">
<input type="file" name="fileToUpload" id="fileToUpload">
<input type="submit" value="Hochladen" name="submit">
</form>
END;
}

function zuteilung_speichern($fname) {
  $cmd="SELECT id,name FROM schueler";
  $res=mysql_query($cmd) or die ("cmd: ".mysql_error());
  while ($row=mysql_fetch_object($res)) {
    $schueler_id[$row->name]=$row->id;
  }
  $cmd="SELECT kuerzel, kurse.id FROM kurse JOIN kurs_beschreibungen AS kb ON kurse.beschr_id=kb.id WHERE wahl_id='".$_SESSION['wahl_id']."'";
  $res=mysql_query($cmd) or die ("cmd: ".mysql_error());
  while ($row=mysql_fetch_object($res)) {
    $kurs_id[$row->kuerzel]=$row->id;
  }
  
  $f=file($fname) or die("Kann $fname nicht oeffnen.");
  echo "&Uuml;bertragene Datei:<br><textarea cols='100' rows='10'>\n";
  $variable=FALSE;
  $values="";
  foreach ($f as $l) {
    echo htmlentities($l);
    if (preg_match("/Actual.*variables/",$l))
      $variable=TRUE;
    if (preg_match("/Actual.*constraints/",$l))
      $variable=FALSE;
    if ($variable) {
      if (preg_match("/([^\\s]+)_([^\\s]+)\\s+1$/",$l,$m)) {
        $zuteilung[$m[1]]=$m[2];
        $tn[$m[2]][]=$m[1];
        $values.="(".$schueler_id[$m[1]].",".$kurs_id[$m[2]].",1),";
      }
    }
  }
  echo "</textarea><br>";
  echo "Ergebnis: <br><table border='1' style='border-collapse:collapse;'><tr><th>Kurs</th><th>Teilnehmer</th></tr>";
  foreach ($tn as $k=>$s) {
    echo "<tr><td><b>$k</b> (".sizeof($s).")</td><td><font size='0.8em'>";
    echo join(", ",$s)."</font></td></tr>";
  }
  echo "</table><br>";
  if ($values!="") {
    $values=substr($values,0,-1);
    $cmd="INSERT INTO zuteilungen (schueler_id,kurs_id,block) VALUES $values ON DUPLICATE KEY UPDATE kurs_id=VALUES(kurs_id)";
    mysql_query($cmd) or die ("$cmd: ".mysql_error());
  }
  
}

if (!isset($_FILES["fileToUpload"])) {
  model_download();
} else {
  if ($_FILES["fileToUpload"]["size"]<=0) {
    echo "<font color='red'>Es wurde keine gueltige Datei hochgeladen.</font><br>";
    model_download();
  } else {
    zuteilung_speichern($_FILES["fileToUpload"]["tmp_name"]);
  }
}
?>