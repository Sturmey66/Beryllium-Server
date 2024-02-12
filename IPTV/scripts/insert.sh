# This script shoud take three inputs and place them in the channels.xml file. The format of the command is the URL of the logo,
# The title of the channel, and the URL of the media element.
# tvg-logo="https://dashradio-files.s3.amazonaws.com/development/icon_logos/229/logos/ec3bfac0-18e4-488b-b2f5-42451fb589e6.png" 
# title="Dash Radio | Smooth Jazz Hits"
# URL="https://ice55.securenetsystems.net/DASH44"
#
#
#
id=$(xmlstarlet select -t -v '//camera/@id' /IPTV/channels.xml | sort -n | tail -1)
idplus=$(($id + 1))
# logo=$1
name=$1
url=$2

echo $id is the id
echo $idplus is the id plus one
# echo $logo is the logo
echo $name is the name 
echo $url is the url


 if [ -z "$2" ]
   then
     echo "The syntax of the command should be: insert.sh 'name' 'url'"
 	exit 1
   else
 /usr/bin/xmlstarlet ed -L -s /ChannelList -t elem -n channel -v "" \
  -i "//channel[not(@id)]" -t attr -n id -v $idplus \
  -s "//channel[@id='$idplus']" -t elem -n "name"    -v "$name"\
  -s "//channel[@id='$idplus']" -t elem -n "url" -v "$url"\
  /IPTV/channels.xml
 fi
 echo "end of script"
 