#!/bin/zsh
# Copies the nightly database backups (storage/app/private/database_*.sqlite.gz) and all card
# attachments (storage/app/private/attachments) from the server to this Mac (default ~/Backups/stackr),
# so there is a copy outside Hostpoint. Attachments are mirrored: files deleted on the server are
# deleted here too. Run from your Mac whenever you like: ./scripts/pull-backups.sh
set -euo pipefail
SOURCE=pumutuxu@pumutuxu.myhostpoint.ch:/home/pumutuxu/www/stackr.kimiyu.ch/storage/app/private
TARGET=${BACKUP_DIR:-$HOME/Backups/stackr}
mkdir -p "$TARGET"
rsync -az --include 'database_*.sqlite.gz' --exclude '*' "$SOURCE/" "$TARGET/"
# Filtered from the parent folder, so a missing attachments folder isn't an error; excluded files
# (the database backups) are never deleted by --delete.
rsync -az --delete --include '/attachments/***' --exclude '*' "$SOURCE/" "$TARGET/"
echo "Backups in $TARGET:"
ls -1t "$TARGET" | grep database_ | head -5
[[ -d $TARGET/attachments ]] && du -sh "$TARGET/attachments" || echo "No attachments yet."
