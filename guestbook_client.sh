#!/bin/bash

echo -n "What would you like to post: "
read -r BODY

curl --get \
    --data-urlencode "body=$BODY" \
    http://localhost:38143/guestbook.php
