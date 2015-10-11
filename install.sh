#!/bin/bash

echo "Dies ist das Installationsskript zur Kurswahl."
read -p "MySQL host: " dbhost
read -p "MySQL user: " dbuser
read -s -p "MySQL password: " dbpasswd
echo
cat >setup.php <<EOF
<?php
  \$dbhost='$dbhost';   \$dbuser='$dbuser';   \$dbpasswd='$dbpasswd';
?>
EOF

echo "setup.php wurde angelegt."

mysql -h $dbhost -u $dbuser --password="$dbpasswd" <kurswahl.sql \
|| { echo "$0: Fehler beim Anlegen der Datenbank" >&2; exit -1; }

mysql -h $dbhost -u $dbuser -D kurswahl --password="$dbpasswd" <kurswahl_daten.sql \
|| { echo "$0: Fehler beim Eintragen der Daten" >&2; exit -1; }
