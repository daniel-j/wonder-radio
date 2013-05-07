#!/bin/bash

file=`mpc -h $1@:: listall -q | shuf -n 1`
./mpd-queue.sh $1 "$file"
echo $file