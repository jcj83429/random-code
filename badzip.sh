#!/bin/bash
# try to make partially downloaded multipart (or single) zip extractable
# usage: badzip.sh <zip/z01 path>
name=`basename "$1" | sed 's/\.z[0-9][0-9]$/\.z/g'`
dir=`readlink -f "$1"`
dir=`dirname "$dir"`
cat "$dir"/"$name"* > /tmp/temp1.zip
yes | zip -FF /tmp/temp1.zip --out /tmp/temp2.zip
rm /tmp/temp1.zip
yes | zip -FF /tmp/temp2.zip --out /tmp/temp3.zip
rm /tmp/temp2.zip
unzip /tmp/temp3.zip -d "$dir"
rm /tmp/temp3.zip
