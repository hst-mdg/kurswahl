<?php
include 'db_connect.php';

if (!isset($_POST['wahl_id']) || !isset($_POST['kurs_id']))
  die ("Die Wahl oder der Kurs wurden nicht festgelegt.");
  
$wahl_id=$_POST['wahl_id'];
$kurs_id=$_POST['kurs_id'];

echo "Sie bearbeiten den Kurs $kurs_id zu der Wahl $wahl_id.";
?>
