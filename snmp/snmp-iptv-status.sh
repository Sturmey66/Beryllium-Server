#!/bin/bash
# Returns 1 if ffmpeg Supervisor service is running, 0 if not
if pgrep -f ffmpeg >/dev/null; then
    echo 1
else
    echo 0
fi
