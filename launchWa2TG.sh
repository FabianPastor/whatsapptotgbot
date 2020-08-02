#!/bin/bash
while [ 1 -eq 1 ]; do
    ./wa2tg.php 2>&1 |tee ./logs/terminal-$(date +%s).log
    sleep 1
done
