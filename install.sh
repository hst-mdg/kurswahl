#!/bin/bash

dbhost="localhost"
dbuser="root"
dbpasswd=""
dbname="kurswahl"

echo "Dies ist das Installationsskript zur Kurswahl."
read -p "MySQL host [$dbhost]:" ans
if [ "$ans" ]; then  dbhost=$ans; fi
read -p "MySQL user [$dbuser]: " ans
if [ "$ans" ]; then  dbuser=$ans; fi
read -p "Datenbank-Name [$dbname]: " ans
if [ "$ans" ]; then  dbname=$ans; fi
read -s -p "MySQL password: " dbpasswd
echo

cat >setup.php <<EOF
<?php
  \$dbhost='$dbhost';   \$dbuser='$dbuser';   \$dbpasswd='$dbpasswd';
  \$dbname='$dbname';
?>
EOF

echo "setup.php wurde angelegt."

sed s/\`kurswahl\`/\`$dbname\`/ <kurswahl.sql >kurswahl.$dbname.sql
sed s/\`kurswahl\`/\`$dbname\`/ <kurswahl_daten.sql >kurswahl_daten.$dbname.sql

mysql -h $dbhost -u $dbuser --password="$dbpasswd" <kurswahl.sql \
|| { echo "$0: Fehler beim Anlegen der Datenbank" >&2; exit -1; }

mysql -h $dbhost -u $dbuser -D kurswahl --password="$dbpasswd" <kurswahl_daten.sql \
|| { echo "$0: Fehler beim Eintragen der Daten" >&2; exit -1; }
