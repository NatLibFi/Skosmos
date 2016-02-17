#!/bin/bash

. config.sh

set=${1:-\*}

for dsfile in urls/rest-$set.txt.gz; do
  bn=`basename $dsfile .txt.gz`
  dataset=${bn##*-}
  sampletime=`wc -l $dsfile | cut -d ' ' -f 1 | awk '{ print int((length+1)/2) }'`
  echo "Measuring '$dataset' for $sampletime minutes..."
  echo "BASEURL=$RESTBASEURL" | zcat -f - $dsfile | siege -c 1 -H "Pragma: no-cache" --log=$LOGDIR/rest-responsetime.log -t ${sampletime}M -i -f /dev/stdin -m D$dataset
done
