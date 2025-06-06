#!/bin/sh

# Ensure permissions for IPTV data
echo "Setting permissions on /IPTV..."

if [ -f /IPTV/channels.xml ]; then
  chown 82:82 /IPTV
  chmod 664 /IPTV/channels.xml
  echo "✔ Set write permissions on /IPTV/channels.xml"
else
  echo "⚠ channels.xml not found in /IPTV"
fi

exec "$@"
