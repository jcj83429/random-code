#! /bin/bash
# MKV2MP4 Xpress
# requires ffmpeg with fdkaac, MP4Box
# converts a x264 mkv to mp4, converting the audio to stereo AAC if needed
# does not handle file splitting for XBOX360
fps=`mediainfo --Output="Video;%FrameRate%" "$1"`
ad=` mediainfo --Output="Audio;%Delay%" "$1"`
vt=`mediainfo --Output="Video;%ID%" "$1"`
ac=`mediainfo --Output="Audio;%Format%" "$1"`
name=`basename "$1" .mkv`
echo -e "~~~~AUTO MKV -> MP4~~~~\nFRAMERATE = "$fps"fps\nAUDIO DELAY = "$ad"ms\nTARGET = ./""$name"".mp4\n"
if [ "$ac" == "AAC" ]; then # assume all AAC sources are stereo. Multichannel AAC is rarely seen in the wild
	ffmpeg -i "$1" -an -c:v copy -vbsf h264_mp4toannexb /tmp/v.h264 -vn -ac 2 -c:a copy /tmp/a.mp4
else
	ffmpeg -i "$1" -an -c:v copy -vbsf h264_mp4toannexb /tmp/v.h264 -vn -ac 2 -c:a libfdk_aac -flags +qscale -global_quality 5 -afterburner 1 -cutoff 17k /tmp/a.mp4
fi
MP4Box -add /tmp/v.h264:fps=$fps -add /tmp/a.mp4:delay=$ad "$name".mp4 # ffmpeg's MP4 output doesn't play well on XBOX so I use MP4Box for muxing
rm /tmp/v.h264 /tmp/a.mp4
