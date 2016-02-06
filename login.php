<?php
session_start();

include 'db_connect.php';
include 'abfragen.php';

session_unset();

function aktivierung_verschicken() {
  if ($_POST["password"]!=$_POST["password2"]||strlen($_POST["password"])<8) {
    echo "Die Passw&ouml;rter m&uuml;ssen &uuml;bereinstimmen und mindestens 8 Zeichen lang sein!";
    echo <<<END
<form action='login.php' method='post'><input type='hidden' name='user' value='{$_POST["user"]}'/>
<input type='submit' value='OK' name='register'>
</form>
END;
  } else {
      $user=strtolower($_POST['user']);
      if (!preg_match("/^[a-z.]{3,}$/",$user)) {
        echo "Unzulaessiger Name: '{$_POST['user']}' (nur Buchstaben und Punkte, nicht zu kurz!)<br>";
        echo "<form action='login.php' method='post'><input type='submit' value='OK' name='register'>";
        die();
      }
      $code=substr(md5(rand()),0,10);
      $msg=<<<END
Liebe(r) {$_POST['user']},\n
diese Email wurde automatisch verschickt, weil du dich für die Kurswahl registriert hast
bzw. dein Passwort ändern willst. Falls du das nicht selbst warst, ignoriere diese Email einfach!\n
\n
Um die Registrierung/Passwort-Änderung abzuschließen, öffne folgenden Link im Internet-Browser:\n
<a href="{$_SERVER['HTTP_REFERER']}?_=$code">{$_SERVER['HTTP_REFERER']}?_=$code</a>
END;
    
      // TODO: Ausgabe entfernen!!!
//      echo <<<END
//<a href="{$_SERVER['HTTP_REFERER']}?_=$code">{$_SERVER['HTTP_REFERER']}?_=$code</a><br>
//END;
    
      // TODO: Email verschicken!
      mail($_POST['user'],"Kurswahl-Aktivierung",$msg) || die("Email kann nicht verschickt werden.");
      $user=mysql_real_escape_string($_POST["user"]);
      $datum = date("Y-m-d H:i:s");
      $ip=$_SERVER["REMOTE_ADDR"];
      $pw=md5($_POST["password"]);
      $klasse=$_POST["klassen"];
      $lehrer=$_POST["lehrer"]=="on";
      // TODO: Prüfen ob wirklich Lehrer!
      echo "<br>pw: ".$pw."<br>";
      $cmd="INSERT INTO aktivierung (user,datum,passwort,code,ip,klassen_id,lehrer) VALUES('$user','$datum','$pw','$code','$ip','$klasse','$lehrer')";
      mysql_query($cmd) or die (mysql_error());
      
      echo "<h1>Best&auml;tigungs-Email verschickt</h1>\n";
      echo <<<END
Eine Email wurde an {$_POST['user']}@mdg-hamburg.de geschickt.<br>
Du musst den darin enthaltenen Link aufrufen, um dich zu registrieren
bzw. um das Passwort f&uuml;r dieses Kurswahl-System wie vorher eingegeben
zu &auml;ndern.<br>
Wenn du die Email nicht bekommen hast, &uuml;berpr&uuml;fe noch einmal deinen Benutzernamen.
<br>
<a href="login.php">Zum Login</a>
END;
  }
}

function aktivieren($code) {
  echo "<h1>Aktivierungsbest&auml;tigung</h1>";
  $code=mysql_real_escape_string($code);
  $cmd="SELECT user,passwort,klassen_id,lehrer FROM aktivierung WHERE code='$code'";
  $res=mysql_query($cmd) or die(mysql_error());
  if ($row = mysql_fetch_object($res)) {
    $cmd="INSERT INTO user (name,passwort,klassen_id,lehrer) VALUES('{$row->user}','{$row->passwort}','{$row->klassen_id}','{$row->lehrer}')"
    ."ON DUPLICATE KEY UPDATE name=name,passwort='{$row->passwort}',klassen_id='{$row->klassen_id}',lehrer='{$row->lehrer}'";
    mysql_query($cmd) or die(mysql_error());
    echo "Die Aktivierung war erfolgreich. Du kannst dich jetzt einloggen.<br>";
    echo <<<END
<form action='login.php' method='post'><input type='hidden' name='user' value='{$row->user}'/>
<input type='submit' value='OK'>
</form>
END;
  } else {
    echo "Der Link ist ung&uuml;ltig. Versuche es noch einmal.<br>";
    echo "<form action='login.php' method='post'><input type='submit' value='OK'>";
  }
}

function registrieren() {
  $user="";
  $pw="";
  if (isset($_POST['user'])) $user=$_POST['user'];
  if (isset($_POST['password'])) $pw=$_POST['password'];
  echo "<h1>Registrierung/Passwort-&Auml;nderung</h1>\n";
  $klassen_options="<option value='-1' selected>---</option>";
  foreach (klassen_namen() as $id=>$n) {
    $klassen_options.="<option value='$id'>$n</option>\n";
  }
  echo <<<END
Der Benutzername muss der gleiche sein wie bei deinem Iserv-Account (also Vorname.Nachname).<br>
Es wird dann eine Nachricht an deine Iserv-Adresse geschickt.<br>
Du musst den darin enthaltenen Link aufrufen, um dich zu registrieren
bzw. um das Passwort f&uuml;r dieses Kurswahl-System wie eingegeben
zu &auml;ndern.<br>
&Uuml;berpr&uuml;fe noch einmal Benutzernamen und Passwort (merken!) und best&auml;tige dann mit "OK",
wenn alles richtig ist.
<form action='./login.php' method='post'>
<legend for="user">Benutzer-Name: <input type="text" name="user" value="$user"></input>@mdg-hamburg.de </legend><br>
<legend for="klasse">Meine Klasse: <select name="klassen">$klassen_options</select>
<legend for="lehrer">Lehrer: <input type="checkbox" name="lehrer"/>
<legend for="password">Passwort: <input type="password" name="password" value="$pw"/> </legend>
<legend for="password">Passwort best&auml;tigen: <input type="password" name="password2"/> </legend>
<input type="submit" name="register_OK" value="OK">
<input type="submit" name="register_Cancel" value="Abbruch">
</form>
END;
}

if (isset($_GET["_"])) { // Aktivierungslink
  aktivieren($_GET["_"]);
} else if (isset($_POST['login'])) { // Passwort prüfen
  $user=strtolower(mysql_real_escape_string($_POST['user']));
  $pw=substr(md5($_POST['password']),0,20);
  $cmd="SELECT id FROM user WHERE name='$user' and passwort='$pw'";
  $res=mysql_query($cmd) or die(mysql_error());
  if ($row = mysql_fetch_object($res)) { // Login OK
    $_SESSION['user']=mysql_real_escape_string($user);
    $_SESSION['password']=mysql_real_escape_string($pw);
    $cmd="SELECT klassen.name FROM klassen JOIN user ON klassen.id=user.klassen_id WHERE user.name='{$_SESSION['user']}'";
    $res=mysql_query($cmd) or die(mysql_error());
    if ($row1 = mysql_fetch_object($res))
      $_SESSION['klasse']=$row1->name;
    echo "KLASSE: ".$_SESSION['klasse']."<br>";
    $cmd="INSERT INTO logins (userid,datum,ip) VALUES ('{$row->id}',NOW(),'{$_SERVER['REMOTE_ADDR']}')";
    mysql_query($cmd) or die(mysql_error());
    include 'wahl_festlegen.php';
  } else { // Fehler
    echo "Falsches Passwort oder falscher Name!<br>";
    echo <<<END
<form action='login.php' method='post'><input type='hidden' name='user' value='{$_POST["user"]}'/>
<input type='submit' value='OK'>
</form>
END;
  }
} else if (isset($_POST['register'])) {
  registrieren();
} else if (isset($_POST['register_OK'])) {
  aktivierung_verschicken();
} else {
  $user="placeholder='Benutzername'";
  if (isset($_POST['user']))
    $user="value='{$_POST['user']}'";
  echo "<h1>Einloggen</h1>\n";
  echo <<<END
<form action='./login.php' method='post'>
<legend for="user">Benutzer-Name: <input type="text" name="user" $user></input> </legend>
<legend for="password">Passwort: <input type="password" name="password"/> </legend>
<input type="submit" name="login" value="Login">
<input type="submit" name="register" value="Neu registrieren / Passwort vergessen">
</form>
END;
}

?>