#!/bin/bash
# Returns last time services were restarted
# Assume you store timestamp in a file /tmp/iptv_last_restart.txt
if [[ -f /tmp/iptv_last_restart.txt ]]; then
    cat /tmp/iptv_last_restart.txt
else
    echo "never"
fi
