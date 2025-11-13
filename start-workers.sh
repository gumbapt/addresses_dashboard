#!/bin/bash
# Script to start Laravel Queue Workers with PM2
# This replaces the need for Docker workers

set -e

echo "🚀 Starting Laravel Queue Workers with PM2..."
echo ""

# Navigate to project directory
cd /home/address3/addresses_dashboard

# Stop existing PM2 processes (if any)
echo "📦 Stopping existing backend processes..."
pm2 stop addresses-dashboard-backend 2>/dev/null || true
pm2 delete addresses-dashboard-backend 2>/dev/null || true

# Start all processes from ecosystem file
echo "🔄 Starting backend and workers from ecosystem.config.cjs..."
pm2 start ecosystem.config.cjs

# Save PM2 process list
echo "💾 Saving PM2 process list..."
pm2 save

# Show status
echo ""
echo "✅ Workers started successfully!"
echo ""
echo "📊 Current PM2 processes:"
pm2 list

echo ""
echo "📝 Useful commands:"
echo "  - View all logs:        pm2 logs"
echo "  - View worker logs:     pm2 logs queue-worker-default"
echo "  - View report logs:     pm2 logs queue-worker-reports"
echo "  - Monitor workers:      pm2 monit"
echo "  - Restart workers:      pm2 restart all"
echo "  - Stop all:             pm2 stop all"
echo ""

