#!/bin/bash

set -e

if [[ $EUID -ne 0 ]]; then
  echo "This script must be run as root." >&2
  exit 1
fi

OLDUSER="$1"
NEWUSER="$2"
if [[ -z "$OLDUSER" || -z "$NEWUSER" ]]; then
  echo "Usage: $0 oldusername newusername"
  exit 1
fi
systemctl stop user@$(id -u $OLDUSER).service
/home/nest-internal/nest/quetzal/src/os/scripts/setup.sh $NEWUSER
/home/nest-internal/nest/quetzal/src/os/scripts/create_home.sh $NEWUSER
nest caddy add $NEWUSER.hackclub.app --user $NEWUSER
if [ ! -f "/home/$NEWUSER/.ssh/authorized_keys" ]; then
  if [ ! -d "/home/$NEWUSER/.ssh" ]; then
    mkdir /home/$NEWUSER/.ssh
  fi

  chmod -R 700 /home/$NEWUSER/.ssh
  chown -R $NEWUSER:$NEWUSER /home/$NEWUSER/.ssh
fi
cp /home/$OLDUSER/.ssh/authorized_keys /home/$NEWUSER/.ssh/authorized_keys
chmod -R 700 /home/$NEWUSER/.ssh
chmod -R 600 /home/$NEWUSER/.ssh/authorized_keys
chown -R $NEWUSER:$NEWUSER /home/$NEWUSER/.ssh
chown -R $NEWUSER:$NEWUSER /home/$NEWUSER


OLDHOME="/home/$OLDUSER"
NEWHOME="/home/$NEWUSER"

if [[ ! -d "$OLDHOME" ]]; then
  echo "Old home directory $OLDHOME does not exist."
  exit 1
fi

if [[ ! -d "$NEWHOME" ]]; then
  echo "New home directory $NEWHOME does not exist."
  exit 1
fi

echo "Starting migration from $OLDUSER to $NEWUSER..."

cd "$OLDHOME"
find . -mindepth 1 | while read -r RELPATH; do
  SRC="$OLDHOME/$RELPATH"
  DEST="$NEWHOME/$RELPATH"

  if [[ ! -e "$DEST" ]]; then

    mkdir -p "$(dirname "$DEST")"
    cp -a "$SRC" "$DEST"
    echo "Copied $RELPATH"
  else
    DEST_OLD="$NEWHOME/${RELPATH}.old"
    mkdir -p "$(dirname "$DEST_OLD")"
    cp -a "$SRC" "$DEST_OLD"
    echo "Conflict: $RELPATH exists, saved as ${RELPATH}.old"
  fi
done

sss_cache -E

DBS=$(sudo -u postgres psql -Atc "SELECT datname FROM pg_database WHERE datname LIKE '${OLDUSER}_%';")

if [[ -z "$DBS" ]]; then
  echo "No databases found for user $OLDUSER."
fi

for DB in $DBS; do
  SUFFIX="${DB#${OLDUSER}_}"
  NEWDB="${NEWUSER}_${SUFFIX}"

  echo "Dumping $DB to Creating $NEWDB for $NEWUSER..."
  sudo -u postgres createdb -O "$NEWUSER" "$NEWDB"
  sudo -u postgres pg_dump "$DB" | sudo -u postgres psql "$NEWDB"

  echo "Migrated $DB -to $NEWDB"
done
systemctl start user@$(id -u $OLDUSER).service
systemctl enable user@$(id -u $OLDUSER).service
echo "Migration complete. Make sure you've updated the username in Authentik."
