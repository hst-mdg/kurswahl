<?php
  include 'setup.php';
/**
 * Die Datei "setup.php" muss mit folgendem Inhalt angelegt werden:
 * $dbhost='localhost oder http://domain.de';
 * $dbuser='Name des admin accounts';
 * $dbpasswd='passwort des admins';
 */
  if (!isset($dbhost)) die("Die Datei setup.php mit den MySQL-Verbindungs-Daten fehlt.");
  mysql_connect($dbhost,$dbuser,$dbpasswd) or die ("MySQL Verbindung zu $dbhost als $dbuser nicht moeglich.");
  mysql_select_db('kurswahl') or die ("DB nicht vorhanden");
?>
