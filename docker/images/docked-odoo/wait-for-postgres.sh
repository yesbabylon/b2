#!/bin/sh
# wait-for-postgres.sh

host="$1"
shift
cmd="$@"

for count in 1 2 3 4 5
do
      echo "Pinging postgresql database attempt $count"
      if $(nc -z $host 5432) ; then
        >&2 echo "Postgres is up - executing command"
        exec $cmd
        exit 0
      fi
      sleep 3
done

>&2 echo "Postgres unavailable : stopping"