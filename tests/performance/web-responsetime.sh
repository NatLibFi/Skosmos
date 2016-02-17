#!/bin/bash

. config.sh

set=${1:-\*}

for dsfile in urls/web-$set.txt.gz; do
  bn=`basename $dsfile .txt.gz`
  dataset=${bn##*-}
  sampletime=`wc -l $dsfile | cut -d ' ' -f 1 | awk '{ print int((length+1)/2) }'`
  echo "Measuring '$dataset' for $sampletime minutes..."
  echo "BASEURL=$WEBBASEURL" | zcat -f - $dsfile | siege -c 1 -H "Pragma: no-cache" --log=$LOGDIR/web-responsetime.log -t ${sampletime}M -i -f /dev/stdin -m D$dataset
done
