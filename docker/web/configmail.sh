#!/bin/bash

echo "127.0.0.1 localhost localhost.localdomain $(hostname)" >> /etc/hosts
yes Y | /usr/sbin/sendmailconfig
