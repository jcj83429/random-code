#!/usr/bin/env python
# this script complements mp4chapcut.sh
# it is not very accurate and metadata is not supported
# requires MP4Box.
#
# usage: mp4chapjoin.py <common prefix>
# common prefix is what all the input files' file names begin with
# for example, the common prefix of 
# "/tmp/album - 01 - test1.mp4"  and 
# "/tmp/album - 02 - test2.mp4"  is
# "/tmp/album - "
# input files must be named like above example (<common prefix> - <2 digit track no> - <track name>)

import sys,os,re,datetime,shutil
from subprocess import call,check_output
wd=check_output(["dirname",sys.argv[1]])
wd=os.path.dirname(sys.argv[1])
bn=os.path.basename(sys.argv[1])

if wd:
    os.chdir(wd)

files=[]
for (dirpath,dirnames,filenames) in os.walk("."):
    for filename in filenames:
        if bn in filename:
            files.extend([filename])
    break
files.sort()

lengths=[0]
for file in files:
    lengths.extend([check_output(['mediainfo','--Output=General;%Duration%',file]).strip()])
for i in range(1,len(lengths)):
    lengths[i]=float(lengths[i])/1000+lengths[i-1]

names=["" for x in range(0,len(files))]

for i in range(0,len(files)):
    names[i]=check_output(['mediainfo','--Output=General;%Track%',files[i]]).strip()

print "00:00:00.000  "+names[0]
for i in range(1,len(files)):
    print '0'+str(datetime.timedelta(seconds=lengths[i])).rstrip('0')+'  '+names[i]

chapfile=open('chap.txt','w')
chapfile.write("00:00:00.000  "+names[0])
for i in range(1,len(files)):
    chapfile.write('\n0'+str(datetime.timedelta(seconds=lengths[i])).rstrip('0')+'  '+names[i])
chapfile.close()

for file in files:
    params=['MP4Box']
    params.extend([file])
    params.extend(['-raw','1'])
    call(params)

p=re.compile("(.*)\\..+$")

with open('join.aac', 'wb') as outfile:
    for i in range(0,len(files)):
        with open(p.findall(files[i])[0]+'_track1.aac') as readfile:
            shutil.copyfileobj(readfile, outfile)
            readfile.close()
        os.remove(p.findall(files[i])[0]+'_track1.aac')
    outfile.close()

params=['MP4Box -add join.aac -chap chap.txt -new out.mp4']
call(params, shell=True)
os.remove('join.aac')
os.remove('chap.txt')
