#!/bin/sh
PHAN_DISABLE_XDEBUG_WARN=1
export PHAN_DISABLE_XDEBUG_WARN
time php ./vendor/bin/phan -S --analyze-twice >phan.txt
wc -l phan.txt
