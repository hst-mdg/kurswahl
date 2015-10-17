<?php
session_start();

include 'db_connect.php';

function klassenformular() {
  echo <<<END
<form action='login.php' method='post'>
<fieldset> <legend>Schueler Login</legend>
Klasse: <select name='klasse'>
END;
  $abfrage="SELECT klasse FROM schueler GROUP BY klasse ORDER BY klasse";
  $ergebnis = mysql_query($abfrage) or die (mysql_error());
  while ($row = mysql_fetch_object($ergebnis)) {
    echo "<option value='".$row->klasse."'>$row->klasse</option>\n";
  }
  echo <<<END
</select>
<input type="submit" value="Schueler auswaehlen">
</fieldset>
</form>
END;
}

function schuelerformular() {
  $_SESSION['klasse']=$_POST['klasse'];
  echo <<<END
<form action='wahl_festlegen.php' method='post'>
<fieldset> <legend>Schueler Login</legend>
Sch&uuml;ler: <select name='schuelername'>
END;
  $abfrage="SELECT name FROM schueler WHERE klasse='".$_SESSION['klasse']."' ORDER BY name";
  $ergebnis = mysql_query($abfrage) or die (mysql_error());
  while ($row = mysql_fetch_object($ergebnis)) {
    echo "<option value='".$row->name."'>$row->name</option>\n";
  }
  echo <<<END
<input type='submit' value='Schueler Login'>
</fieldset>
</form>
END;
}

function lehrerformular() {
  echo <<<END
<form action='./wahl_festlegen.php' method='post'>
<fieldset> <legend>Lehrer Login</legend>
Name: <input type='text' name='lehrername'>
<input type='submit' value='Lehrer Login'>
</fieldset>
</form>
END;
}

echo "<h1>Einloggen</h1>\n";
session_unset();
if (isset($_POST['klasse'])) {
  schuelerformular();
} else {
  klassenformular();
}
lehrerformular();
?>
