#!/bin/bash

# Define the variables
meta3="#EXTINF:-1 group-title=\"EdenTV\","
meta4="http://localhost:8080/VOD/"

# Directory path
directory="/IPTV/VOD/"

# Output file path
output_file="/IPTV/live/eden.m3u"

#Initialize the M3U file
echo "#EXTM3U" > "$output_file"

# Counter starting at 100
counter=100

# List filenames in the directory, echo them to the output file along with the variable contents and incremented number
for filepath in "$directory"*
do
	filename=$(basename "$filepath" | sed -e 's/^[ \t]*//' -e 's/[ \t]*$//')  # Remove leading and trailing whitespace
    echo "${meta3}${filename}">> "$output_file"
	echo "${meta4}${filename}">> "$output_file"
    ((counter++))
done

# Display a message indicating completion
echo "File listing and variable echoed to $output_file"

