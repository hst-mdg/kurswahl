<?php
  include 'setup.php';
/**
 * Die Datei "setup.php" muss mit folgendem Inhalt angelegt werden:
 * $dbhost='localhost oder http://domain.de';
 * $dbuser='Name des admin accounts';
 * $dbpasswd='Passwort des admins';
 * $dbname='Name der Kurswahl-Datenbank';
 */
  if (!isset($dbhost)) die("Die Datei setup.php mit den MySQL-Verbindungs-Daten fehlt.");
  mysql_connect($dbhost,$dbuser,$dbpasswd) or die ("MySQL Verbindung zu $dbhost als $dbuser nicht moeglich.");
  mysql_select_db($dbname) or die ("Die Datenbank '$dbname' ist auf dem Host '$dbhost' nicht verf&uumlgbar.");
?>
