<?php
if (isset($_POST["lp"])) {
header("Content-type: text/plain");
header("Content-Disposition: attachment; filename=lp_solve_input.txt");
header("Pragma: no-cache");
header("Expires: 0");
echo $_POST["lp"];
} else {
  die("Fehler.");
}
?>