<?php
include 'db_connect.php';

echo <<<END
<h1>Einloggen</h1>
<form action='./wahl_festlegen.php' method='post'>
Sch&uuml;ler: <input type='text' name='schuelername'>
Lehrer: <input type='text' name='lehrername'><br>
<input type='submit'>
</form>
END
?>
