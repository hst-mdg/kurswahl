<?php
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
?>