# This script shoud take three inputs and place them in the channels.xml file. The format of the command is the URL of the logo,
# The title of the channel, and the URL of the media element.
# tvg-logo="https://dashradio-files.s3.amazonaws.com/development/icon_logos/229/logos/ec3bfac0-18e4-488b-b2f5-42451fb589e6.png" 
# title="Dash Radio | Smooth Jazz Hits"
# URL="https://ice55.securenetsystems.net/DASH44"
#
#
#
if [ -z "$3" ]
  then
    echo "The syntax of the command should be: insert.sh 'logo' 'title' 'url'"
	exit 1
  else
xmlstarlet ed -L -s /ChannelList -t elem -n channel -v ""\
 -s "/ChannelList/channel[last()]" -t elem -n "tvg-logo" -v "$1"\
 -s "/ChannelList/channel[last()]" -t elem -n "title"    -v "$2"\
 -s "/ChannelList/channel[last()]" -t elem -n "url" -v "$3"\
 ../channels.xml
fi