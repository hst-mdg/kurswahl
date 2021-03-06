<?php
if (!isset($_SESSION)) session_start();
include 'db_connect.php';
include_once 'abfragen.php';

/**
 * Holt alle in der DB eingetragenen Wahlen.
 * @return Array (Id => Name)
 */
function wahlen() {
  $abfrage = "SELECT * FROM wahl_einstellungen;";
  $ergebnis = mysql_query($abfrage) or die (mysql_error());
  $w=array();
  while($row = mysql_fetch_object($ergebnis)) {
    $w[$row->id]=$row->name;
  }
  return $w;
}

/**
 * Erstellt ein Formular mit einer Drop-Down-Liste der Wahlen.
 * @param $wahlen Array wie von wahlen() erzeugt
 * @return String mit Formular-HTML-Code
 */
function form($wahlen) {
  $form=<<<END
<form action='wahl_bearbeiten.php' method='post'>
  <select name='wahl_id'>
END;
  foreach($wahlen as $id => $w) {
    $form.="    <option value='$id'>$w</option>\n";
  }
  $form.=<<<END
  </select><br>
  <input type='submit' value='Weiter'><br>
</form>
END;
  return $form;
}

unset($_SESSION['wahl_id']);
unset($_SESSION['kurs_id']);

check_login();

// Wahlmöglichkeiten aus DB holen:
$wahlen=wahlen();

if (lehrer_angemeldet()) {
  $wahlen[-1]="Neue Wahl";
  echo "W&auml;hlen Sie aus, ob Sie eine neue Wahl anlegen bzw. welche Wahl
 Sie verwalten m&ouml;chten:<br>\n"
    .form($wahlen);
} else { // Schüler
  echo "W&auml;hle aus, an welcher Wahl du teilnehmen m&ouml;chtest:<br>\n"
    .form($wahlen);
}
echo "<form action='login.php' method='post'><input type='submit' value='Logout'></form>"; 

?>