#!/bin/bash
docker stop $(docker ps -a -q)
docker rm $(docker ps -a -q)
docker build -t sturmey1966/simpleiptv .
docker run -d -p 8080:8080 -p 9000:9000 sturmey1966/simpleiptv
docker logs $(docker ps -a -q)
docker exec -it $(docker ps -a -q) sh
