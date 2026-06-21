/**
 * PM2 process manager config — keeps the WS relay alive on Cloudways/VPS.
 * Usage: pm2 start ecosystem.config.cjs && pm2 save
 */
module.exports = {
  apps: [
    {
      name: 'insight-hub-ws',
      script: 'server.js',
      cwd: __dirname,
      instances: 1,
      autorestart: true,
      watch: false,
      max_memory_restart: '128M',
      env: {
        NODE_ENV: 'production',
        HOST: '127.0.0.1',
        PORT: '8080',
      },
    },
  ],
};
