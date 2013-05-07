#!/bin/bash

mpc -h $1@:: clear -q
#mpc -h $1@:: current -f "%file%@@%title%@@%artist%" -q

while : ; do
	mpc -h $1@:: current --wait -f "%file%@@%title%@@%artist%" -q
done
