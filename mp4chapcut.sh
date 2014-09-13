#!/bin/bash
# requires ffmpeg, MP4Box, AtomicParsley
# usage: mp4chapcut.sh /path/to/input.mp4
ffmpeg -i "$1" 2>/tmp/mp4info.txt
cat /tmp/mp4info.txt | sed -n -e '/Chapter/,$p' | sed -n -e '/\(Chapter\|title\)/p' | sed -n -e 'n;p' | sed -e 's/      title           : //g' > /tmp/mp4titles.txt
cat /tmp/mp4info.txt | sed -n -e '/Chapter/,$p' | sed -n -e '/\(Chapter\|title\)/p' | sed -n -e 'p;n' | sed -e 's/.*://g' -e 's/[^0-9,\.]//g' -e 's/,/:/g' > /tmp/mp4times.txt
paste /tmp/mp4times.txt /tmp/mp4titles.txt > /tmp/mp4chapters.txt

trackno=01
artist=`cat /tmp/mp4info.txt | sed -n -e '/Metadata/,$p' | sed -n -e '1,/Duration/p' | grep 'artist          :' | sed -e 's/.*artist          : //g'`
album=`cat /tmp/mp4info.txt | sed -n -e '/Metadata/,$p' | sed -n -e '1,/Duration/p' | grep 'album           :' | sed -e 's/.*album           : //g'`
genre=`cat /tmp/mp4info.txt | sed -n -e '/Metadata/,$p' | sed -n -e '1,/Duration/p' | grep 'genre           :' | sed -e 's/.*genre           : //g'`
year=`cat /tmp/mp4info.txt | sed -n -e '/Metadata/,$p' | sed -n -e '1,/Duration/p' | grep 'date            :' | sed -e 's/.*date            : //g'`
while read line
do
	time=$(echo "$line" | cut -f1)
	name=$(echo "$line" | cut -f2)
	echo $time $trackno $name
	MP4Box -splitx "$time" -out "$(echo $(echo "$1" | sed 's/\..*//g') - "$trackno" - "$(echo $name | sed 's/\//-/g')".mp4)" "$1"
	mv "$(echo "$1" | sed 's/\..*//g')"_* "$(echo $(echo "$1" | sed 's/\..*//g') - "$trackno" - "$(echo $name | sed 's/\//-/g')".mp4)" #fix for -out bug
	AtomicParsley "$(echo $(echo "$1" | sed 's/\..*//g') - "$trackno" - "$(echo $name | sed 's/\//-/g')".mp4)" --artist "$artist" --title "$name" --album "$album" --genre "$genre" --tracknum "$trackno" --year "$year" --overWrite
	trackno=`printf "%02d" $(($(echo $trackno | sed 's/^0*//')+1))`
done < /tmp/mp4chapters.txt

#rm /tmp/mp4info.txt /tmp/mp4titles.txt /tmp/mp4times.txt /tmp/mp4chapters.txt
