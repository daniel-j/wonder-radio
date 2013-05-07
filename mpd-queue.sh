#!/bin/bash

mpc -h $1@:: crop -q
mpc -h $1@:: add "$2" -q
mpc -h $1@:: play -q