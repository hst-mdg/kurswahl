<?php
/**
 * Speichert alle Kurs-Zusätze (z.B. sprachlich/mathematisch) in einem Array.
 * @param $wahl_id ID der zur Teilnahme ausgewählten Wahl
 * @param $alle TRUE: gibt ein array mit allen Werten zurück, sonst ein array mit den zugeordneten Werten zu jedem Kurs
 * @return Array mit den Zusätzen der einzelnen Kurse (zusatz_name=>(kurs_id => (zusatz_id=>zusatz_wert,...))) bzw aller möglichen Werte
 */
function zusatz_abfrage($wahl_id, $alle) {
  if ($alle) { // Alle möglichen Zusatzwerte
    $cmd="SELECT zw.id, zw.wert, z.name FROM zusatz_werte as zw JOIN zusatz AS z ON zw.zusatz_id=z.id WHERE z.wahl_id='$wahl_id'";
    $ergebnis = mysql_query($cmd) or die ("$cmd: ".mysql_error());
    $ret=array();
    while($row = mysql_fetch_object($ergebnis)) {
      if (!isset($ret[$row->name])) $ret[$row->name]=array();
      $ret[$row->name][$row->id]=$row->wert;
    }
    return $ret;
  }
  $cmd=<<<END
  SELECT kb.id as kid, zw.id as zwid, zw.wert, zusatz.name FROM kurs_beschreibungen AS kb
  LEFT JOIN kurs_zusaetze AS kz ON kz.kurs_id=kb.id
  LEFT JOIN zusatz_werte AS zw ON zw.id=kz.zusatz_wert_id
  LEFT JOIN zusatz ON zw.zusatz_id=zusatz.id
  WHERE kb.wahl_id='$wahl_id'
END;
  $ergebnis = mysql_query($cmd) or die (mysql_error());
  $ret=array();
  while($row = mysql_fetch_object($ergebnis)) {
    if (!$row->name) continue;
    $ret[$row->name][$row->kid][$row->zwid]=$row->wert;
  }
  return $ret;
}


/**
 * Gibt die Anzahl der Blöcke zurück
 * @param wahl_id ID der Wahl
 * @return Anzahl der Blöcke
 */
function block_anzahl($wahl_id) {
  $cmd="SELECT bloecke FROM wahl_einstellungen WHERE id='$wahl_id'";
  $ergebnis = mysql_query($cmd) or die (mysql_error());
  if ($row = mysql_fetch_object($ergebnis)) {
    return $row->bloecke;
  } else return 0;
}

/**
 * Speichert alle Kurs-Jahrgangs-Zuordnungen und Kürzel in einem Array
 * @param wahl_id ID der zur Teilnahme ausgewählten Wahl
 * @return 3D Array mit den Kuerzeln und Jahrgängen (kurs_id => (Kuerzel => Jahrgang)
 */
function kuerzeljahr_abfrage($wahl_id) {
  $cmd=<<<END
  SELECT kb.id, kj.jahrgang, k.kuerzel FROM kurs_beschreibungen AS kb
  LEFT JOIN kurse AS k ON k.beschr_id=kb.id
  LEFT JOIN kurs_jahrgang AS kj ON kj.kurs_id=k.id
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
 * Lösche Einträge in den Tabellen zusatz, zusatz_werte und kurs_zusaetze
 * @param $beschr_id ID der Kurs-Beschreibung, zu der alle Zusätze gelscht werden sollen */
function zusaetze_loeschen($beschr_id) {
  $wahl_id=$_SESSION["wahl_id"];
  $cmd="DELETE kz FROM kurs_zusaetze AS kz JOIN zusatz_werte AS zw ON kz.zusatz_wert_id=zw.id JOIN zusatz as z ON zw.zusatz_id=z.id JOIN kurs_beschreibungen AS kb ON kb.wahl_id=z.wahl_id WHERE kb.id LIKE '$beschr_id' AND kb.wahl_id='$wahl_id'";
  mysql_query($cmd) or die ($cmd.": ".mysql_error());
  //echo mysql_affected_rows()." Kurs-Zusaetze geloescht.<br>";
  $cmd="DELETE zw FROM zusatz_werte AS zw JOIN zusatz AS z ON zw.zusatz_id=z.id JOIN kurs_beschreibungen AS kb ON kb.wahl_id=z.wahl_id WHERE kb.id LIKE '$beschr_id' AND kb.wahl_id='$wahl_id'";
  mysql_query($cmd) or die ($cmd.": ".mysql_error());
  //echo mysql_affected_rows()." Zusatzwerte geloescht.<br>";
  $cmd="DELETE z FROM zusatz AS z JOIN kurs_beschreibungen AS kb ON kb.wahl_id=z.wahl_id WHERE kb.id LIKE '$beschr_id' AND kb.wahl_id='$wahl_id'";
  mysql_query($cmd) or die ($cmd.": ".mysql_error());
  //echo mysql_affected_rows()." Zusaetze geloescht.<br>";
}

/**
 * Lösche Einträge in den Tabellen kurse und kurs_jahrgang
 * @param $beschr_id ID der Kurs-Beschreibung, zu der alle Kurse gelöscht werden.
 */
function kurse_loeschen($beschr_id,$auch_zusaetze=FALSE) {
  if ($auch_zusaetze) {
    zusaetze_loeschen($beschr_id);
    $cmd="DELETE sw FROM schueler_wahl AS sw JOIN kurse AS k ON sw.kurs_id=k.id JOIN kurs_beschreibungen AS kb ON kb.id=k.beschr_id WHERE k.beschr_id LIKE '{$beschr_id}' AND kb.wahl_id='".$_SESSION['wahl_id']."'";
    mysql_query($cmd) or die ($cmd.": ".mysql_error());
    echo mysql_affected_rows()." Sch&uuml;lerwahlen wurden gel&ouml;scht.<br>";
  }
  $cmd="DELETE z FROM zuteilungen AS z JOIN kurse AS k ON z.kurs_id=k.id JOIN kurs_beschreibungen AS kb ON k.beschr_id=kb.id WHERE kb.id LIKE '$beschr_id' AND wahl_id='".$_SESSION['wahl_id']."'";
  mysql_query($cmd) or die ($cmd.": ".mysql_error());
  $cmd="DELETE kj FROM kurs_jahrgang AS kj JOIN kurse AS k ON kj.kurs_id=k.id JOIN kurs_beschreibungen AS kb ON k.beschr_id=kb.id WHERE kb.id LIKE '$beschr_id' AND wahl_id='".$_SESSION['wahl_id']."'";
  mysql_query($cmd) or die ($cmd.": ".mysql_error());
  //echo mysql_affected_rows()." Jahrgangseintraege geloescht $cmd.<br>";
  $cmd="DELETE k FROM kurse AS k JOIN kurs_beschreibungen AS kb ON k.beschr_id=kb.id WHERE kb.id LIKE '$beschr_id' AND kb.wahl_id='".$_SESSION['wahl_id']."'";
  mysql_query($cmd) or die ($cmd.": ".mysql_error());
  //echo mysql_affected_rows()." Kurseintraege geloescht $cmd.<br>";
  if ($auch_zusaetze) {
    $cmd="DELETE FROM kurs_beschreibungen WHERE id LIKE '{$beschr_id}' AND wahl_id='".$_SESSION['wahl_id']."'";
    mysql_query($cmd) or die ($cmd.": ".mysql_error());
    echo mysql_affected_rows()." Kurse geloescht.<br>";
  }
}
?>