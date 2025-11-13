#!/bin/bash
# Script to restart Laravel Queue Workers
# Use this after code changes or when workers get stuck

set -e

echo "🔄 Restarting Laravel Queue Workers..."
echo ""

cd /home/address3/addresses_dashboard

# Restart all worker processes
pm2 restart queue-worker-default
pm2 restart queue-worker-reports
pm2 restart queue-worker-messages

echo ""
echo "✅ Workers restarted successfully!"
echo ""
pm2 list

