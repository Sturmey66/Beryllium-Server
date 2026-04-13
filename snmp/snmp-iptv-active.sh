#!/bin/bash
# Count active ffmpeg streaming processes
count=$(pgrep -f "ffmpeg.*-i" | wc -l)
echo $count
