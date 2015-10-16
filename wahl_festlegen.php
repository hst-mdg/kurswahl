<?php

include 'db_connect.php';

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
  $hidden="";
  if (isset($_POST['lehrername']) && $_POST['lehrername']!="")
    $hidden="<input type='hidden' name='lehrername' value='".$_POST['lehrername']."'>";
  if (isset($_POST['schuelername']) && $_POST['schuelername']!="")
    $hidden="<input type='hidden' name='schuelername' value='".$_POST['schuelername']."'>";
  $form.=<<<END
  </select><br>
  $hidden
  <input type='submit' value='Weiter'><br>
</form>
END;
  return $form;
}

// Wahlmöglichkeiten aus DB holen:
$wahlen=wahlen();

if ($_POST['lehrername']) {
  $wahlen[-1]="Neue Wahl";
  echo "W&auml;hlen Sie aus, ob Sie eine neue Wahl anlegen bzw. welche Wahl
 Sie verwalten m&ouml;chten:<br>\n"
    .form($wahlen);
} elseif ($_POST['schuelername']) {
  echo "W&auml;hle aus, an welcher Wahl du teilnehmen m&ouml;chtest:<br>\n"
    .form($wahlen);
}

?>
