module.exports = {
  apps: [
    // Laravel API Server
    {
      name: 'addresses-dashboard-backend',
      script: 'artisan',
      args: 'serve --host=127.0.0.1 --port=8006',
      interpreter: 'php',
      cwd: '/home/address3/addresses_dashboard',
      watch: false,
      autorestart: true,
      max_restarts: 10,
      min_uptime: '10s',
      restart_delay: 4000,
      env: {
        APP_ENV: 'production'
      }
    },
    
    // Queue Worker - Default Queue (2 instances for parallel processing)
    {
      name: 'queue-worker-default',
      script: 'artisan',
      args: 'queue:work database --queue=default --sleep=3 --tries=3 --max-time=3600 --memory=512',
      interpreter: 'php',
      cwd: '/home/address3/addresses_dashboard',
      instances: 2,
      watch: false,
      autorestart: true,
      max_restarts: 10,
      min_uptime: '10s',
      restart_delay: 4000,
      env: {
        APP_ENV: 'production'
      }
    },
    
    // Queue Worker - Reports Queue (dedicated for report processing)
    {
      name: 'queue-worker-reports',
      script: 'artisan',
      args: 'queue:work database --queue=reports --sleep=3 --tries=3 --max-time=3600 --memory=512',
      interpreter: 'php',
      cwd: '/home/address3/addresses_dashboard',
      instances: 1,
      watch: false,
      autorestart: true,
      max_restarts: 10,
      min_uptime: '10s',
      restart_delay: 4000,
      env: {
        APP_ENV: 'production'
      }
    },
    
    // Queue Worker - Message Processing (for chat/notifications)
    {
      name: 'queue-worker-messages',
      script: 'artisan',
      args: 'queue:work database --queue=message_processing --sleep=3 --tries=3 --max-time=3600 --memory=512',
      interpreter: 'php',
      cwd: '/home/address3/addresses_dashboard',
      instances: 1,
      watch: false,
      autorestart: true,
      max_restarts: 10,
      min_uptime: '10s',
      restart_delay: 4000,
      env: {
        APP_ENV: 'production'
      }
    }
  ]
}

