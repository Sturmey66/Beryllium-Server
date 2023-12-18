# This script should delete a channel from channels.xml file based on the "id".
#
#
#
id=$1

 if [ -z "$1" ]
   then
     echo "The syntax of the command should be: delete.sh 'id'"
 	exit 1
   else
 xmlstarlet ed -L -d "//channel[@id=\"$id\"]" /IPTV/channels.xml
 fi
 
 
