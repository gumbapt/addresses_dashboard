#!/bin/bash
# Script to check queue status and pending jobs

echo "📊 Queue Status Check"
echo "===================="
echo ""

cd /home/address3/addresses_dashboard

echo "🔍 Checking pending jobs in database..."
php artisan queue:monitor

echo ""
echo "📋 Failed jobs:"
php artisan queue:failed

echo ""
echo "💻 PM2 Workers Status:"
pm2 list | grep -E "(queue-worker|name)"

echo ""
echo "📝 Recent worker logs (last 20 lines):"
pm2 logs queue-worker-default --lines 20 --nostream

echo ""
echo "💡 Tips:"
echo "  - Retry failed jobs:    php artisan queue:retry all"
echo "  - Clear failed jobs:    php artisan queue:flush"
echo "  - Monitor live:         pm2 monit"

