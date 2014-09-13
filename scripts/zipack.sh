#! /bin/bash
# packs folder to zip with no compression
# output goes in the folder's parent folder if no output file specified
# usage: zipack.sh <in folder> [out file.zip]
if [ -n "$2" ]; then
	outfile="$2"
else
	outfile="$1".zip
fi

pushd `dirname "$1"`
zip -0 -r "$outfile" "`basename "$1"`"
popd
